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
		return $this->getDeckBoardDisplayName($deckId);
	}

	public function getDeckBoardDisplayName(string $deckId): string {
		if (!class_exists('OCA\\Deck\\Db\\CardMapper') || !class_exists('OCA\\Deck\\Db\\BoardMapper')) {
			$this->logger->info('Deck mappers are not installed');
			return $deckId . ' (*)';
		}

		try {
			$cardMapper = \OC::$server->query('OCA\\Deck\\Db\\CardMapper');
			$boardMapper = \OC::$server->query('OCA\\Deck\\Db\\BoardMapper');

			$boardId = $cardMapper->findBoardId((int)$deckId);
			if ($boardId === null) {
				return $deckId . ' (*)';
			}

			$board = $boardMapper->find($boardId);
			return $board->getTitle() ?: (string)$boardId;
		} catch (\Throwable $e) {
			$this->logger->info('Deck board name could not be resolved', ['exception' => $e]);
			return $deckId . ' (*)';
		}
	}

}
