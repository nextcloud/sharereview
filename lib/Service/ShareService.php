<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Service;

use OCA\ShareReview\Helper\UserHelper;
use OCA\ShareReview\Helper\GroupHelper;
use OCA\ShareReview\Helper\TalkHelper;
use OCA\ShareReview\Helper\DeckHelper;
use OCA\ShareReview\Db\ShareMapper;
use OCA\ShareReview\Helper\CircleHelper;
use OCA\ShareReview\Sources\SourceEvent;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\PreConditionNotMetException;
use OCP\Share\Exceptions\ShareNotFound;
use Psr\Log\LoggerInterface;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;

class ShareService {

	private ?array $dataSources = null;
	private static array $displayNameCache = [];
	private array $dateTimeCache = [];

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		private readonly ShareManager $shareManager,
		private readonly ShareMapper $shareMapper,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly UserHelper $userHelper,
		private readonly GroupHelper $groupHelper,
		private readonly TalkHelper $talkHelper,
		private readonly DeckHelper $deckHelper,
		private readonly CircleHelper $circleHelper,
		private readonly IEventDispatcher $dispatcher,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * get all shares
	 */
	public function read(bool $onlyNew, ?string $backgroundUserId = null): array {
		$user = $backgroundUserId ?? $this->userSession->getUser()->getUID();
		$userTimestamp = (int)$this->config->getUserValue($user, 'sharereview', 'reviewTimestamp', 0);
		$showTalk = $this->config->getUserValue($user, 'sharereview', 'showTalk', 'true') !== 'false';

		$fileShares = $this->getFileShares($showTalk);
		$appShares = $this->getAppShares();

		// Pre-warm display name cache for relevant shares only
		$this->preloadDisplayNames(array_merge($fileShares, $appShares), $userTimestamp, $onlyNew);

		$formated = [];
		foreach (array_merge($fileShares, $appShares) as $share) {
			if ($onlyNew && (int)($share['time'] ?? 0) <= $userTimestamp) {
				continue;
			}

			$formatedShare = $this->formatShare($share);
			if (!empty($formatedShare)) {
				$formated[] = $formatedShare;
			}
		}

		return $formated;
	}

	/**
	 * delete a share
	 */
	public function delete(string $shareId): bool {
		[$app, $shareString] = explode('_', $shareId, 2);
		$shareString = rawurldecode($shareString);

		if ($app === 'files') {
			$this->logger->info('deleting files share: ' . $shareString);
			$share = $this->shareManager->getShareById($shareString);
			return $this->shareManager->deleteShare($share);
		}

		$this->logger->info('deleting App share');
		return $this->deleteAppShare($app, $shareString);
	}

	/**
	 * confirm current shares by setting the current timestamp
	 */
	public function confirm(string $timestamp): string {
		$user = $this->userSession->getUser();
		$this->config->setUserValue($user->getUID(), 'sharereview', 'reviewTimestamp', $timestamp);
		return $timestamp;
	}

	/**
	 * persist showTalk selection
	 */
	public function showTalk(bool $state): bool {
		$user = $this->userSession->getUser();
		$this->config->setUserValue($user->getUID(), 'sharereview', 'showTalk', $state ? 'true' : 'false');
		return $state;
	}

	/**
	 * app can only be used when it is restricted to at least one group for security reasons
	 */
	public function isSecured(): bool {
		return $this->appConfig->getFilteredValues('sharereview')['enabled'] !== 'yes';
	}

	private function formatShare(array $share): array {
		$type = (int)$share['type'];
		$recipientId = $share['recipient'] ?? '';

		$recipientDisplay = $recipientId ? $this->getCachedDisplayName($type, $recipientId) : '';
		$initiatorDisplay = ($share['initiator'] ?? '') ? $this->getCachedDisplayName(IShare::TYPE_USER, $share['initiator']) : '';

		return [
			'app' => $share['app'],
			'object' => $share['object'],
			'initiator' => $initiatorDisplay,
			'type' => $type . ';' . $recipientDisplay,
			'permissions' => $this->buildPermissions($share),
			'time' => $share['time'],
			'action' => $this->buildAction($share),
		];
	}

	private function getCachedDisplayName(int $type, string $id): string {
		$key = $type . ':' . $id;
		return self::$displayNameCache[$key] ??= match($type) {
			IShare::TYPE_GROUP => $this->groupHelper->getGroupDisplayName($id),
			IShare::TYPE_ROOM => $this->talkHelper->getRoomDisplayName($id),
			IShare::TYPE_DECK => $this->deckHelper->getDeckDisplayName($id),
			IShare::TYPE_CIRCLE => $this->circleHelper->getCircleDisplayName($id),
			IShare::TYPE_USER => $this->userHelper->getUserDisplayName($id),
			IShare::TYPE_EMAIL => $id,
			default => $id	// Fallback to raw ID
		};
	}

	private function getFormattedTime(int $unixTime): string {
		return $this->dateTimeCache[$unixTime] ??= (new \DateTime('@' . $unixTime))->format(\DATE_ATOM);
	}

	private function buildPermissions(array $share): string {
		$password = ($share['password'] ?? '') !== '' ? $share['password'] : '';
		$expiration = ($share['expiration'] ?? '') !== '' ? $share['expiration'] : '';
		return $share['permissions'] . ';' . $password . ';' . $expiration;
	}

	private function buildAction(array $share): string {
		$app = $share['appId'] ?? $share['app'] ?? 'files';
		$action = $share['action'] ?: (string)$share['id'];
		return $app . '_' . $action;
	}

	private function preloadDisplayNames(array $allShares, int $userTimestamp, bool $onlyNew): void {
		$relevantShares = $onlyNew ? array_filter($allShares, fn($s) => (int)($s['time'] ?? 0) > $userTimestamp) : $allShares;

		// Preload initiators (most common)
		$userIds = array_unique(array_column($relevantShares, 'uid_initiator', 0) ?: []);
		foreach ($userIds as $uid) {
			if ($uid) {
				$this->getCachedDisplayName(IShare::TYPE_USER, $uid);
			}
		}
	}

	private function getFileShares(bool $showTalk = true): array {
		$shares = $this->shareMapper->findAll();
		$formated = [];

		$sharesByOwner = [];
		foreach ($shares as $share) {
			if (!$showTalk && (int)$share['share_type'] === IShare::TYPE_ROOM) {
				continue;
			}
			$sharesByOwner[$share['uid_initiator']][] = $share;
		}

		foreach ($sharesByOwner as $uid => $ownerShares) {
			if (!$this->userHelper->isValidOwner($uid)) {
				foreach ($ownerShares as $share) {
					$this->processShare($formated, $share, 'invalid share (*) ');
				}
				continue;
			}

			try {
				$userFolder = $this->rootFolder->getUserFolder($uid);
			} catch (\Exception $e) {
				$this->logger->error('Error accessing folder for ' . $uid . ': ' . $e->getMessage());
				foreach ($ownerShares as $share) {
					$this->processShare($formated, $share, 'invalid share (*) ');
				}
				continue;
			}

			$filePaths = [];
			$fileIds = array_unique(array_column($ownerShares, 'file_source'));
			foreach ($fileIds as $fileId) {
				try {
					$files = $userFolder->getById($fileId);
					$filePaths[$fileId] = !empty($files) ? $files[0]->getPath() . ';' . $files[0]->getName() : '';
				} catch (\Exception $e) {
					$this->logger->error('File error ' . $fileId . ': ' . $e->getMessage());
					$filePaths[$fileId] = '';
				}
			}

			foreach ($ownerShares as $share) {
				$path = $filePaths[$share['file_source']] ?? '';
				$this->processShare($formated, $share, $path);
			}
		}

		return $formated;
	}

	private function processShare(array &$formated, array $share, string $path): void {
		$recipient = $share['share_with'];
		$action = match ((int)$share['share_type']) {
			IShare::TYPE_EMAIL => 'ocMailShare:' . $share['id'],
			IShare::TYPE_REMOTE => 'ocFederatedSharing:' . $share['id'],
			IShare::TYPE_ROOM => 'ocRoomShare:' . $share['id'],
			IShare::TYPE_CIRCLE => 'ocCircleShare:' . $share['id'],
			IShare::TYPE_DECK => 'deck:' . $share['id'],
			default => 'ocinternal:' . $share['id']
		};

		if ((int)$share['share_type'] === IShare::TYPE_LINK) {
			$recipient = $share['token'];
		}

		$formated[] = [
			'id' => $share['id'],
			'app' => $this->l10n->t('Files'),
			'appId' => 'files',
			'object' => $path,
			'initiator' => $share['uid_initiator'],
			'type' => $share['share_type'],
			'recipient' => $recipient,
			'permissions' => $share['permissions'],
			'password' => ($share['password'] ?? '') !== '',
			'expiration' => $share['expiration'],
			'time' => $this->getFormattedTime((int)$share['stime']),
			'action' => rawurlencode($action),
		];
	}

	private function getAppShares(): array {
		$formated = [];
		foreach ($this->getRegisteredSources() as $appId => $app) {
			foreach ($app->getShares() as $share) {
				$formated[] = $share + ['app' => $appId];
			}
		}
		return $formated;
	}

	private function getRegisteredSources(): array {
		if ($this->dataSources !== null) {
			return $this->dataSources;
		}

		$dataSources = [];
		$event = new SourceEvent();
		$this->dispatcher->dispatchTyped($event);

		foreach ($event->getSources() as $class) {
			try {
				$uniqueId = \OC::$server->get($class)->getName();
				if (isset($dataSources[$uniqueId])) {
					$this->logger->error('Data source with the same ID already registered: ' . $uniqueId);
					continue;
				}
				$dataSources[$uniqueId] = \OC::$server->get($class);
			} catch (\Error $e) {
				$this->logger->error('Can not initialize data source: ' . json_encode($class));
				$this->logger->error($e->getMessage());
			}
		}
		return $this->dataSources = $dataSources;
	}

	private function deleteAppShare(string $app, string $shareId): bool {
		$registeredSources = $this->getRegisteredSources();
		if (isset($registeredSources[$app])) {
			return $registeredSources[$app]->deleteShare($shareId);
		}

		$this->logger->info('Can not delete app share: ' . $app);
		return false;
	}
}
