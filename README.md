# Spam Alerts

A [Flarum](https://flarum.org) 2.0 extension that emails your moderators whenever
[FoF Anti-Spam](https://github.com/FriendsOfFlarum/anti-spam) automatically flags
or holds a post.

Anti-Spam's content filter quietly files a flag, and often hides the post until a
moderator approves it. On a small forum nobody may look at the flags queue for
days, while a real newcomer whose first post tripped the filter waits in silence.
This sends the people who can act on it an email the moment it happens.

## What is sent

One email per flagged post, covering new discussions, replies and edits:

- **Subject:** `[Forum] Held for approval: <discussion title>`, or
  `Flagged as possible spam` when the score was high enough to flag but not to hide
- the discussion, with a link to the post
- the author, their post count and join date
- whether it is hidden or still visible, and the spam score
- Anti-Spam's own reasons (for example `[URL Detector] Contains a URL to a non-allowlisted domain`)
- an excerpt of what they wrote, and a link to the flags page

Mail goes through Flarum's configured mailer, on the queue.

## Who receives it

In **Admin → Spam Alerts**, choose any number of **groups**, and optionally list
extra **users** by username. Administrators are preselected.

These are only candidates. Each one must also be allowed to **view flags** on that
discussion and to **see the post** itself, which for a held post means being able
to approve it. That is the same test the forum applies before showing them the
flag, tag-scoped permissions included. So picking a group without moderation
rights sends that group nothing, and no one is shown held content they couldn't
already open. The author of the post is never emailed, and neither is anyone
whose email address is unconfirmed.

## Checking it works

```
php flarum spam-alerts:test <post id>
```

It lists who would be emailed and queues a real alert about that post, labelled
as a test, so you don't have to post spam to see one.

## Installation

```
composer require ekumanov/flarum-ext-spam-alerts
php flarum extension:enable ekumanov-spam-alerts
```

Requires `fof/anti-spam` and `flarum/flags`. A queue worker is recommended:
with the `sync` driver the email is sent while the flagged user's request is
still running.

## License

MIT
