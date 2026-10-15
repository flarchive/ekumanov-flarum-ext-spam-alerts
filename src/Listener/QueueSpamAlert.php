<?php

/*
 * This file is part of ekumanov/flarum-ext-spam-alerts.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Ekumanov\SpamAlerts\Listener;

use Ekumanov\SpamAlerts\Job\SendSpamAlertJob;
use FoF\AntiSpam\Event\PostWasFlaggedAsSpam;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class QueueSpamAlert
{
    public function __construct(
        protected Queue $queue,
        protected ConnectionInterface $db,
        protected LoggerInterface $log
    ) {
    }

    public function handle(PostWasFlaggedAsSpam $event): void
    {
        $job = new SendSpamAlertJob($event->post->id, $event->score, $event->reasonDetail);

        // A new discussion is created inside a transaction, and its first post
        // is flagged before that transaction commits. A fast worker could pick
        // the job up while the post is still invisible to it, so the push waits
        // for the commit (and is dropped if the discussion is rolled back).
        // Outside a transaction afterCommit() runs the callback straight away.
        $this->db->afterCommit(function () use ($job, $event) {
            // This runs inside the poster's request. A queue outage must cost
            // the moderators one email, not the poster a 500 for a post that
            // has in fact been saved.
            try {
                $this->queue->push($job);
            } catch (Throwable $e) {
                $this->log->error("[spam-alerts] Could not queue the alert for post {$event->post->id}: {$e->getMessage()}");
            }
        });
    }
}
