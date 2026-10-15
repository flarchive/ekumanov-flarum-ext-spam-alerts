<?php

/*
 * This file is part of ekumanov/flarum-ext-spam-alerts.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Ekumanov\SpamAlerts\Console;

use Ekumanov\SpamAlerts\Job\SendSpamAlertJob;
use Ekumanov\SpamAlerts\Recipients;
use Flarum\Console\AbstractCommand;
use Flarum\Post\Post;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Queues an alert for an existing post without anyone having to post spam,
 * so the mail path can be checked after installing or changing the settings.
 */
class SendTestAlertCommand extends AbstractCommand
{
    public function __construct(
        protected Queue $queue,
        protected Recipients $recipients
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('spam-alerts:test')
            ->setDescription('Queue a test spam alert about an existing post.')
            ->addArgument('post', InputArgument::REQUIRED, 'ID of the post to describe in the alert');
    }

    protected function fire(): int
    {
        $post = Post::find((int) $this->input->getArgument('post'));

        if (! $post) {
            $this->error('No such post.');

            return 1;
        }

        $users = $this->recipients->for($post);

        if ($users->isEmpty()) {
            $this->info('Nobody would be emailed about this post: check the configured groups and users, and that they can view flags.');

            return 0;
        }

        $this->queue->push(new SendSpamAlertJob($post->id, 0, 'Test alert sent with spam-alerts:test. Nothing was flagged.'));

        $this->info('Queued an alert to: '.$users->pluck('username')->implode(', '));

        return 0;
    }
}
