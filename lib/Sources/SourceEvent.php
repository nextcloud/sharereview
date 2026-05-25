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
	/** @var \Closure[] */
	protected $collections = [];

	/**
	 * @param string $datasource
	 * @since 9.1.0
	 */
	public function registerSource(string $datasource)
	{
		$this->collections[] = $datasource;
	}

	/**
	 * @return \Closure[]
	 * @since 9.1.0
	 */
	public function getSources()
	{
		return $this->collections;
	}
}