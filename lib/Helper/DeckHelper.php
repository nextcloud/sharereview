<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Helper;

use Psr\Log\LoggerInterface;

class DeckHelper {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function getDeckDisplayName(string $deckId): string {
		return 'Deck card ' . $deckId;
	}
}