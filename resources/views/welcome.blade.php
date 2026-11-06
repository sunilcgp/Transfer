<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Transfer — Share files beautifully</title>
    <script>
        const nativeResponseJson = Response.prototype.json;
        Response.prototype.json = async function () {
            const contentType = this.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                throw new Error(this.redirected && new URL(this.url).pathname === '/login'
                    ? 'Your session has expired. Please log in again.'
                    : 'The server returned an invalid response. Please refresh and try again.');
            }

            return nativeResponseJson.call(this);
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (min-width: 801px) {
            html, body { height: 100%; overflow: hidden; }
            .page-shell { height: 100vh; min-height: 0; }
            .hero-section { height: calc(100vh - 92px); padding: 28px 0 24px; gap: 64px; }
            .hero-copy h1 { margin: 18px 0; font-size: clamp(52px, 5.4vw, 76px); }
            .hero-text { font-size: 14px; max-width: 360px; }
            .hero-points { margin-top: 22px; }
            .upload-card { padding: 20px; max-height: calc(100vh - 126px); overflow: auto; }
            .card-topline { margin-bottom: 15px; }
            .form-section { padding-bottom: 13px; margin-bottom: 13px; }
            .form-section textarea { min-height: 58px; }
            .drop-zone { min-height: 150px; }
            .upload-icon { width: 44px; height: 44px; margin-bottom: 10px; }
            .drop-zone small { margin-top: 12px; }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <header class="site-header"><a class="brand" href="{{ url('/') }}"><span class="brand-mark"><i></i><i></i></span><span>Anarock Transfer</span></a><nav class="site-nav"><a href="#how-it-works">How it works</a><a href="#security">Security</a>@auth<a href="{{ route('dashboard') }}">Dashboard</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-cta" type="submit">Log out <span>&rarr;</span></button></form>@else<a href="{{ route('login') }}">Log in</a><a class="nav-cta" href="{{ route('dashboard') }}">Go to dashboard <span>&nearr;</span></a>@endauth</nav><button class="menu-button" type="button" aria-label="Open menu">&#9776;</button></header>
    <main>
        <section class="hero-section">
            <div class="hero-copy"><p class="eyebrow"><span></span> File sharing, reimagined</p><h1>Big files.<br><em>Small effort.</em></h1><p class="hero-text">Send your photos, videos and important files anywhere in the world. Fast, private, and wonderfully simple.</p><div class="hero-points"><span><b>✓</b> No sign-up required</span><span><b>✓</b> Up to 2 GB free</span></div></div>
            <form class="upload-card" id="transferForm" action="{{ route('transfers.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                <div class="card-topline"><span>NEW TRANSFER</span><span class="secure-label">⌁ End-to-end secure</span></div>
                <div class="form-section"><p class="form-section-title">Who is this for?</p><label for="fromEmail">Your email address</label><input id="fromEmail" type="email" value="{{ auth()->user()?->email }}" placeholder="you@example.com" required><label for="toEmailInput">Recipient email addresses</label><div class="recipient-entry"><div class="recipient-chips" id="recipientChips"></div><input id="toEmailInput" type="email" placeholder="Add an email and press Enter"></div><p class="field-hint">You can add multiple recipients.</p></div>
                <div class="form-section"><label for="message">Message <span class="optional">Optional</span></label><textarea id="message" placeholder="Tell your recipients what you're sending..." rows="3"></textarea></div>
                <div class="form-section"><p class="form-section-title">Add your files</p><div class="drop-zone" id="dropZone" tabindex="0" role="button" aria-label="Choose files to upload"><input type="file" id="fileInput" multiple hidden><div class="upload-icon"><svg viewBox="0 0 48 48"><path d="M24 32V8m0 0 9 9m-9-9-9 9M9 27v8a5 5 0 0 0 5 5h20a5 5 0 0 0 5-5v-8"/></svg></div><h2>Drop files here</h2><p>or <button class="browse-button" type="button">browse your computer</button></p><small>Maximum file size is 2 GB per file</small></div><div class="file-list" id="fileList"></div></div>
                @auth
                    <div class="schedule-control"><label class="schedule-toggle" for="scheduleTransfer"><input id="scheduleTransfer" type="checkbox"><span></span> Schedule for later</label><label class="schedule-date" for="scheduledAt" hidden>Send date and time<input id="scheduledAt" type="datetime-local"></label></div>
                @endauth
                <div class="transfer-settings"><label for="expiresIn">Link expires<select id="expiresIn"><option value="1">After 1 day</option><option value="2">After 2 days</option><option value="7" selected>After 7 days</option><option value="30">After 30 days</option></select></label><button class="transfer-button" id="transferButton" type="submit" disabled>Send transfer <span>→</span></button></div><p class="form-error" id="formError" role="alert"></p>
            </form>
        </section>
        <section class="stats-row" id="security"><div><strong>01</strong><span>Choose your files</span></div><div><strong>02</strong><span>Set an expiry date</span></div><div><strong>03</strong><span>Share your link</span></div><div class="trust-note"><span class="shield">◇</span><span>Your files are encrypted<br>and always yours.</span></div></section>
        <section class="bottom-note" id="how-it-works"><span>Made for moments that matter.</span><span class="line"></span><span>&copy; {{ now()->year }} My Transfer</span></section>
    </main>
</div><div class="toast" id="toast" role="status"></div>
</body>
</html>
