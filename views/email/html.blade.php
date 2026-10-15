<x-mail::html.information>
<p>{{ $intro }}</p>

<table cellpadding="4" cellspacing="0" style="border-collapse: collapse; margin: 10px 0;">
    <tr>
        <td style="color: #777; vertical-align: top;">{{ $labels['discussion'] }}</td>
        <td><a href="{{ $postUrl }}"><strong>{{ $discussionTitle }}</strong></a></td>
    </tr>
    <tr>
        <td style="color: #777; vertical-align: top;">{{ $labels['author'] }}</td>
        <td>
            @if ($authorUrl)
                <a href="{{ $authorUrl }}">{{ $authorName }}</a> ({{ $authorHandle }})<br>
                <span style="color: #777;">{{ $authorStats }}</span>
            @else
                {{ $authorName }}
            @endif
        </td>
    </tr>
    <tr>
        <td style="color: #777; vertical-align: top;">{{ $labels['status'] }}</td>
        <td><strong>{{ $status }}</strong></td>
    </tr>
    <tr>
        <td style="color: #777; vertical-align: top;">{{ $labels['score'] }}</td>
        <td>{{ $score }}</td>
    </tr>
</table>

<p style="margin-bottom: 4px;"><strong>{{ $labels['reasons'] }}</strong></p>
<div class="content-preview" style="white-space: pre-wrap;">{{ $reasons }}</div>

@if ($excerpt !== '')
<p style="margin-bottom: 4px;"><strong>{{ $labels['excerpt'] }}</strong></p>
<div class="content-preview" style="white-space: pre-wrap;">{{ $excerpt }}</div>
@endif

<p>
    <a href="{{ $postUrl }}">{{ $labels['open_post'] }}</a>
    &nbsp;&middot;&nbsp;
    <a href="{{ $flagsUrl }}">{{ $labels['review_flags'] }}</a>
</p>
</x-mail::html.information>
