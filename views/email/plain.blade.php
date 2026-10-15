{{--
    Standalone rather than wrapped in core's mail::plain layout: that layout
    passes its content through strip_tags() and then escapes it, so a title like
    "Lessons & <b>offers</b>" would arrive as "Lessons &amp; offers" — and a
    moderator deciding whether something is spam needs to see it verbatim.
--}}
{!! $title !!}

{!! $translator->trans('core.email.greeting', ['displayName' => $username]) !!}

{!! $intro !!}

{!! $labels['discussion'] !!}: {!! $discussionTitle !!}
{!! $postUrl !!}

{!! $labels['author'] !!}: {!! $authorName !!}
@if ($authorUrl)
{!! $authorHandle !!}, {!! $authorStats !!}
{!! $authorUrl !!}
@endif

{!! $labels['status'] !!}: {!! $status !!}
{!! $labels['score'] !!}: {!! $score !!}

{!! $labels['reasons'] !!}:
{!! $reasons !!}
@if ($excerpt !== '')

{!! $labels['excerpt'] !!}:
{!! $excerpt !!}
@endif

{!! $labels['review_flags'] !!}: {!! $flagsUrl !!}

{!! $translator->trans('core.email.informational.footer_plain', ['userEmail' => $userEmail, 'forumTitle' => $forumTitle]) !!}
