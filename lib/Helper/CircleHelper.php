<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Helper;

use Psr\Log\LoggerInterface;

class CircleHelper {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function getCircleDisplayName(string $circleId): string {
		if (!class_exists('OCA\\Circles\\CirclesManager')) {
			$this->logger->error('CirclesManager is not installed');
			return $circleId . ' (*)';
		}

		try {
			$circlesManager = \OC::$server->query('OCA\\Circles\\CirclesManager');
			$circlesManager->startSuperSession();
			$circle = $circlesManager->getCircle($circleId);
			if ($circle && method_exists($circle, 'getName')) {
				return $circle->getName() ?: $circleId;
			}
		} catch (\Throwable $e) {
			$this->logger->info('no circle given, will return circleId');
		}

		return $circleId . ' (*)';
	}
}
