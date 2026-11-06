<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Files shared with you &mdash; My Transfer</title>
    @vite(['resources/css/app.css', 'resources/js/transfer-show.js'])
</head>
<body>
@php
    $remainingSeconds = max(0, (int) now()->diffInSeconds($transfer->expires_at, false));

    if ($transfer->expires_at->isPast()) {
        $expiryLabel = 'expired';
    } elseif ($remainingSeconds >= 86400) {
        $days = (int) ceil($remainingSeconds / 86400);
        $expiryLabel = 'expires in '.$days.' '.str('day')->plural($days);
    } elseif ($remainingSeconds >= 3600) {
        $hours = (int) ceil($remainingSeconds / 3600);
        $expiryLabel = 'expires in '.$hours.' '.str('hour')->plural($hours);
    } else {
        $minutes = max(1, (int) ceil($remainingSeconds / 60));
        $expiryLabel = 'expires in '.$minutes.' '.str('minute')->plural($minutes);
    }
@endphp

<div class="page-shell download-shell">
    <header class="site-header">
        <a class="brand" href="{{ url('/') }}" aria-label="My Transfer home">
            <span class="brand-mark"><i></i><i></i></span>
            <span>Anarock Transfer</span>
        </a>
        <nav class="site-nav" aria-label="Primary navigation">
            <a href="{{ url('/') }}">Send files</a>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-cta" type="submit">Log out <span>&rarr;</span></button>
                </form>
            @else
                <a class="nav-cta" href="{{ route('login') }}">Log in <span>&rarr;</span></a>
            @endauth
        </nav>
    </header>

    <main class="download-page">
        <div class="download-heading">
            <p class="eyebrow"><span></span> Secure file transfer</p>
            <h1>Your files<br><em>are ready.</em></h1>
            <p class="hero-text">
                This transfer contains {{ $transfer->file_count }} {{ str('file')->plural($transfer->file_count) }}
                and {{ $expiryLabel }}.
            </p>
            <p class="download-expiry-date">Available until {{ $transfer->expires_at->format('d M Y, h:i A') }} UTC</p>
        </div>

        <div class="download-list">
            @foreach ($transfer->files as $file)
                @php
                    $sizeLabel = $file->size < 1048576
                        ? number_format(max(1, $file->size / 1024), 1).' KB'
                        : number_format($file->size / 1048576, 1).' MB';
                @endphp
                <div class="download-file-row">
                    <span class="file-icon">FILE</span>
                    <span class="file-name">{{ $file->original_name }}</span>
                    <span class="file-size">{{ $sizeLabel }}</span>
                    <a class="transfer-button" href="{{ route('transfers.download', [$transfer->token, $file->id]) }}">
                        Download <span>&darr;</span>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="download-actions">
            <a class="back-link" href="{{ url('/') }}">&larr; Send your own files</a>
            <button class="share-link-button" type="button" data-share-open>Share link <span>&nearr;</span></button>
        </div>
    </main>

    <footer class="bottom-note download-footer">
        <span>Private file transfer</span>
        <span class="line"></span>
        <span>&copy; {{ now()->year }} My Transfer</span>
    </footer>
</div>

<dialog class="share-dialog" data-share-dialog data-track-url="{{ route('transfers.share.track', $transfer->token) }}">
    <div class="share-dialog-header">
        <div>
            <p class="eyebrow"><span></span> Share transfer</p>
            <h2>Send this download link</h2>
        </div>
        <button class="share-dialog-close" type="button" data-share-close aria-label="Close share dialog">&times;</button>
    </div>
    <p class="share-dialog-copy">Anyone with this link can access the files until the transfer expires.</p>
    <label class="share-url-label" for="share-url">Download link</label>
    <div class="share-url-row">
        <input id="share-url" type="text" value="{{ route('transfers.show', $transfer->token) }}" readonly data-share-url>
        <button type="button" data-copy-link>Copy link</button>
    </div>
    <button class="whatsapp-share-button" type="button" data-whatsapp-share>
        <span>WA</span> Share on WhatsApp
    </button>
    <p class="share-dialog-status" data-share-status aria-live="polite"></p>
</dialog>
</body>
</html>
