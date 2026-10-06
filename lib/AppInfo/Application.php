<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\AppInfo;

use OCA\ShareReview\Event\ShareReviewAccessCheckListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'sharereview';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(
            ShareReviewAccessCheckEvent::class,
            ShareReviewAccessCheckListener::class
        );
    }

    public function boot(IBootContext $context): void
    {
    }
}