<?php

/*
 * This file is part of ekumanov/flarum-ext-spam-alerts.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Ekumanov\SpamAlerts\Console\SendTestAlertCommand;
use Ekumanov\SpamAlerts\Listener\QueueSpamAlert;
use Flarum\Extend;
use FoF\AntiSpam\Event\PostWasFlaggedAsSpam;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\View())
        ->namespace('ekumanov-spam-alerts', __DIR__.'/views'),

    (new Extend\Settings())
        // Administrators out of the box, so installing this does something
        // useful without first having to visit the settings page.
        ->default('ekumanov-spam-alerts.group_ids', '["1"]')
        ->default('ekumanov-spam-alerts.usernames', ''),

    // Anti-Spam announces every automatic flag, and every automatic hold is
    // also a flag (the flag threshold is below the hold threshold), so this
    // one event covers both. It fires for new discussions, replies and edits.
    (new Extend\Event())
        ->listen(PostWasFlaggedAsSpam::class, QueueSpamAlert::class),

    (new Extend\Console())
        ->command(SendTestAlertCommand::class),
];
