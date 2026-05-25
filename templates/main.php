<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\Util;
Util::addStyle('sharereview', 'style');
Util::addStyle('sharereview', '3rdParty/datatables.min');
Util::addScript('sharereview', '3rdParty/jquery.min');
Util::addScript('sharereview', '3rdParty/datatables.min');
Util::addScript('sharereview', 'app');
Util::addScript('sharereview', 'visualization');
Util::addScript('sharereview', 'userGuidance');
?>

<div id="app-navigation">
    <?php print_unescaped($this->inc('part.navigation')); ?>
</div>

<div id="app-content">
    <div id="loading">
        <i class="ioc-spinner ioc-spin"></i>
    </div>
    <?php print_unescaped($this->inc('part.content')); ?>
</div>
<div>
    <?php print_unescaped($this->inc('part.templates')); ?>
</div>
