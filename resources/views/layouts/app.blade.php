<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — My Transfer</title>
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
    @vite(['resources/css/app.css'])
</head>
<body class="app-body">
    <div class="app-frame">
        <header class="app-header">
            <a class="brand" href="{{ url('/') }}" aria-label="My Transfer home">
                <span class="brand-mark"><i></i><i></i></span>
                <span>Anarock Transfer</span>
            </a>

            <nav class="app-header-nav" aria-label="Primary navigation">
                @auth
                    <a class="app-header-link" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="header-send-link" href="{{ url('/') }}">Send files <span>&nearr;</span></a>
                    <div class="user-chip">
                        <span class="user-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="user-meta"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="icon-button" type="submit" aria-label="Log out" title="Log out">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3m9-8h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
                        </button>
                    </form>
                @else
                    <a class="app-header-link" href="{{ url('/') }}">Back to home</a>
                @endauth
            </nav>
        </header>

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
