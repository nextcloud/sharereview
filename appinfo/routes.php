<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

		// Output
		['name' => 'output#read', 'url' => '/data', 'verb' => 'GET'],
		['name' => 'output#readNew', 'url' => '/data/new', 'verb' => 'GET'],
		['name' => 'output#delete', 'url' => '/delete/{shareId}', 'verb' => 'DELETE'],
		['name' => 'output#confirm', 'url' => '/confirm', 'verb' => 'POST'],
                ['name' => 'output#confirmReset', 'url' => '/confirmReset', 'verb' => 'POST'],
                ['name' => 'output#showTalk', 'url' => '/showTalk', 'verb' => 'POST'],

                // Report
                ['name' => 'report#export', 'url' => '/report/export', 'verb' => 'POST'],
                ['name' => 'report#saveSettings', 'url' => '/report/settings', 'verb' => 'POST'],
        ]
];
