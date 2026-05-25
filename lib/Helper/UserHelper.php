<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Helper;

use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UserHelper {
	private IUserManager $userManager;
	private LoggerInterface $logger;

	public function __construct(IUserManager $userManager, LoggerInterface $logger) {
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	public function isValidOwner(string $userId): bool {
		$user = $this->userManager->get($userId);
		if ($user instanceof IUser) {
			return true;
		} else {
			return false;
		}
	}

	public function getUserDisplayName(string $userId): string {
		$user = $this->userManager->get($userId);
		if ($user instanceof IUser) {
			return $user->getDisplayName() ? $user->getDisplayName() : $userId;
		} else {
			return $userId . ' (*)';
		}
	}
}
