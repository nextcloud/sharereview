<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Sources;

interface ISource {
	/**
	 * The name of the app, used in the review table
	 */
	public function getName(): string;

	/**
	 * Return all app-specific shares.
	 *
	 * The app name is added by Share Review from getName().
	 *
	 * @return array<int, array{
	 *     id: string|int,
	 *     object: string,
	 *     initiator: string,
	 *     type: int,
	 *     recipient: string,
	 *     permissions: int,
	 *     time: string,
	 *     action: string,
	 *     timestamp?: int,
	 *     password?: bool,
	 *     expiration?: string,
	 *     parent?: string|int
	 * }> Each share contains:
	 *     id: The unique app-specific identifier for the share, passed to deleteShare().
	 *     app: The name of the application.
	 *     object: The name or title of the object, such as a file path or report name.
	 *     initiator: The user ID of the initiator.
	 *     type: The OCP\Share\IShare type of the share.
	 *     recipient: The user ID of the owner or the token of a link.
	 *     permissions: The permissions level. Use 1 as the default if not set.
	 *     time: The creation time. Use '1970-01-01 01:00:00' as the default if null.
	 *     action: Optional deletion identifier override. Use an empty string to use id.
	 *     password: Whether the share is password protected. Do not return the password itself.
	 *     expiration: Optional expiration date displayed for the share.
	 */
	public function getShares(): array;

	/**
	 * Delete an app-specific share.
	 */
	public function deleteShare(string $shareId): bool;
}
