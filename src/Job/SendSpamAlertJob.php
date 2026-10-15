<?php

/*
 * This file is part of ekumanov/flarum-ext-spam-alerts.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Ekumanov\SpamAlerts\Job;

use Ekumanov\SpamAlerts\Recipients;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class SendSpamAlertJob extends AbstractJob
{
    /**
     * One attempt only. Each recipient is sent to separately and a failure is
     * logged, so a retry could only ever mean mailing the others twice.
     */
    public int $tries = 1;

    public function __construct(
        public readonly int $postId,
        public readonly int $score,
        public readonly string $reasonDetail
    ) {
        parent::__construct();
    }

    public function handle(
        Recipients $recipients,
        Mailer $mailer,
        Factory $view,
        TranslatorInterface $translator,
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        LoggerInterface $log
    ): void {
        // Gone already (a moderator was quicker than the queue): nothing to review.
        $post = Post::find($this->postId);

        if (! $post || ! $post->discussion) {
            return;
        }

        $users = $recipients->for($post);

        if ($users->isEmpty()) {
            return;
        }

        $defaultLocale = $settings->get('default_locale');
        $previousLocale = $translator->getLocale();

        try {
            foreach ($users as $user) {
                try {
                    $translator->setLocale($user->getPreference('locale') ?? $defaultLocale);
                    $this->send($mailer, $view, $translator, $settings, $url, $post, $user);
                } catch (Throwable $e) {
                    $log->error("[spam-alerts] Could not email {$user->username} about post {$post->id}: {$e->getMessage()}");
                }
            }
        } finally {
            $translator->setLocale($previousLocale);
        }
    }

    protected function send(
        Mailer $mailer,
        Factory $view,
        TranslatorInterface $translator,
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        Post $post,
        User $user
    ): void {
        $discussion = $post->discussion;
        $author = $post->user;
        $forumTitle = (string) $settings->get('forum_title');

        // Holding a post that opens a discussion holds the discussion as well,
        // and a spammy title holds the discussion and its first post, so either
        // flag being down means members can't see it.
        $held = ! $post->is_approved || ((int) $post->number === 1 && ! $discussion->is_approved);

        $kind = match (true) {
            $post->edited_at !== null => 'edit',
            (int) $post->number === 1 => 'discussion',
            default => 'reply',
        };

        $t = fn (string $key, array $params = []) => $translator->trans("ekumanov-spam-alerts.email.$key", $params);

        $data = [
            'heading' => $t($held ? 'heading_held' : 'heading_flagged'),
            'intro' => $t("intro_{$kind}"),
            'status' => $t($held ? 'status_held' : 'status_flagged'),
            'discussionTitle' => $discussion->title,
            'postUrl' => $url->to('forum')->route('discussion', ['id' => $discussion->id, 'near' => $post->number]),
            'authorName' => $author ? $author->display_name : $t('deleted_user'),
            'authorHandle' => $author ? '@'.$author->username : null,
            'authorUrl' => $author ? $url->to('forum')->route('user', ['username' => $author->username]) : null,
            'authorStats' => $author ? $t('author_stats', [
                'count' => (int) $author->comment_count,
                'joined' => $author->joined_at?->toDateString() ?? '?',
            ]) : '',
            'score' => $this->score,
            'reasons' => trim($this->reasonDetail),
            'excerpt' => Str::limit(trim((string) $post->content), 800),
            'flagsUrl' => $url->to('forum')->route('flags'),
            'labels' => [
                'discussion' => $t('label_discussion'),
                'author' => $t('label_author'),
                'status' => $t('label_status'),
                'score' => $t('label_score'),
                'reasons' => $t('label_reasons'),
                'excerpt' => $t('label_excerpt'),
                'open_post' => $t('open_post'),
                'review_flags' => $t('review_flags'),
            ],
        ];

        $subject = $t($held ? 'subject_held' : 'subject_flagged', [
            'forum' => $forumTitle,
            'title' => $discussion->title,
        ]);

        // The shared core mail layout reads these from the shared view data
        // rather than from the component's attributes.
        $view->share([
            'forumTitle' => $forumTitle,
            'userEmail' => $user->email,
            'username' => $user->display_name,
            'title' => $data['heading'],
        ]);

        $mailer->send(
            [
                'html' => 'ekumanov-spam-alerts::email.html',
                'text' => 'ekumanov-spam-alerts::email.plain',
            ],
            $data,
            function (Message $message) use ($user, $subject) {
                $message->to($user->email, $user->display_name);
                $message->subject($subject);
            }
        );
    }
}
