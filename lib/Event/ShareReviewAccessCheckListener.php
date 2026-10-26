<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Event;

use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent;

/** @template-implements IEventListener<ShareReviewAccessCheckEvent> */
class ShareReviewAccessCheckListener implements IEventListener {

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly IUserSession $userSession,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof ShareReviewAccessCheckEvent) {
			return;
		}

		$user = $this->userSession->getUser();
		if ($user !== null && $this->appManager->isEnabledForUser('sharereview', $user)) {
			$event->grantAccess();
		} else {
			$event->denyAccess('User is not a share-review operator.');
		}
	}
}
