<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller class for main page.
 */
class PageController extends Controller {
	private $logger;
	/** @var IInitialState */
	protected $initialState;
	/** @var IConfig */
	protected $config;
	/** @var IUserSession */
	private $userSession;
	private IAppConfig $appConfig;

	public function __construct(
		string          $appName,
		IRequest        $request,
		LoggerInterface $logger,
		IInitialState   $initialState,
		IConfig         $config,
		IUserSession    $userSession,
		IAppConfig      $appConfig,
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->initialState = $initialState;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$user = $this->userSession->getUser();
		$this->initialState->provideInitialState('reviewTimestamp', $this->config->getUserValue($user->getUID(), 'sharereview', 'reviewTimestamp', 0));
		$this->initialState->provideInitialState('showTalk', $this->config->getUserValue($user->getUID(), 'sharereview', 'showTalk', 'false'));
		$this->initialState->provideInitialState('reportFolder', $this->appConfig->getValueString('sharereview', 'reportFolder', ''));
		$this->initialState->provideInitialState('schedule', $this->appConfig->getValueString('sharereview', 'schedule', 'none'));
		$this->initialState->provideInitialState('reportType', $this->appConfig->getValueString('sharereview', 'reportType', 'pdf'));
		$this->initialState->provideInitialState('folderOwner', $this->appConfig->getValueString('sharereview', 'folderOwner', ''));
		$params = array();
		return new TemplateResponse($this->appName, 'main', $params);
	}
}