<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Helper;

use OCA\Talk\Room;
use Psr\Log\LoggerInterface;

class TalkHelper {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function getRoomDisplayName(string $token): string {
		if (!class_exists('OCA\Talk\Manager')) {
			return $token . ' (*)';
		}

		$talkManager = \OC::$server->query('OCA\Talk\Manager');
		$room = $talkManager->getRoomByToken($token);
		if ($room instanceof Room) {
			return $room->getName() ? $room->getName() : $token;
		} else {
			return $token . ' (*)';
		}
	}
}