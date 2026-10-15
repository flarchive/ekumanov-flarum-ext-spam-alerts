<?php

/*
 * This file is part of ekumanov/flarum-ext-spam-alerts.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Ekumanov\SpamAlerts;

use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who gets an alert about a given post.
 *
 * The configured groups and users are only the candidates. An alert carries the
 * post's text and the reason it was flagged, so each candidate must also be
 * able to see the flags on that discussion and the post itself — exactly what
 * they would need to review it on the forum. Configuring a group without those
 * permissions therefore sends nothing to it rather than leaking held content.
 */
class Recipients
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return Collection<int, User>
     */
    public function for(Post $post): Collection
    {
        $groupIds = $this->groupIds();
        $usernames = $this->usernames();

        if (! $groupIds && ! $usernames) {
            return new Collection();
        }

        return User::query()
            ->where(function (Builder $query) use ($groupIds, $usernames) {
                $query
                    ->whereHas('groups', fn (Builder $groups) => $groups->whereIn('groups.id', $groupIds))
                    ->orWhereIn('username', $usernames);
            })
            ->where('is_email_confirmed', true)
            ->get()
            ->filter(fn (User $user) => $this->mayReview($user, $post))
            ->values();
    }

    /**
     * @return list<int>
     */
    public function groupIds(): array
    {
        $ids = json_decode((string) $this->settings->get('ekumanov-spam-alerts.group_ids'), true);

        return array_values(array_diff(
            array_map('intval', is_array($ids) ? $ids : []),
            // Guests and Members are everyone. Even if they were picked, the
            // permission check below would drop nearly all of them, but there is
            // no reason to load the whole user table to find that out.
            [0, Group::GUEST_ID, Group::MEMBER_ID]
        ));
    }

    /**
     * @return list<string>
     */
    public function usernames(): array
    {
        $names = preg_split('/[\s,]+/', (string) $this->settings->get('ekumanov-spam-alerts.usernames'));

        return array_values(array_filter(array_map(fn (string $name) => ltrim($name, '@'), $names)));
    }

    protected function mayReview(User $user, Post $post): bool
    {
        if ($user->id === $post->user_id) {
            return false;
        }

        if (! $user->can('viewFlags', $post->discussion)) {
            return false;
        }

        // A held post is visible only to those who may approve it.
        return Post::query()->whereVisibleTo($user)->whereKey($post->id)->exists();
    }
}
