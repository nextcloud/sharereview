<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Sources;

use OCP\EventDispatcher\Event;

/**
 * Class CommentsEntityEvent
 *
 * @since 9.1.0
 */
class SourceEvent extends Event
{

	/** @var string */
	protected $event;
	/** @var array<int, class-string<ISource>> */
	protected $collections = [];

	/**
	 * @param class-string<ISource> $datasource
	 * @since 9.1.0
	 */
	public function registerSource(string $datasource): void
	{
		$this->collections[] = $datasource;
	}

	/**
	 * @return array<int, class-string<ISource>>
	 * @since 9.1.0
	 */
	public function getSources(): array
	{
		return $this->collections;
	}
}
