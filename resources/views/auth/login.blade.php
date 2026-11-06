@extends('layouts.app')

@section('title', 'Welcome back')

@section('content')
    <main class="login-main">
        <section class="login-story" aria-labelledby="story-title">
            <div>
                <p class="eyebrow"><span></span> Your files, one login away</p>
                <h1 id="story-title">Welcome<br>back to <em>simple.</em></h1>
                <p>Pick up where you left off. Your transfers, expiry dates and file history are waiting in one calm place.</p>
            </div>

            <div class="login-proof">
                <div><strong>2 GB</strong><span>per file</span></div>
                <div><strong>30 days</strong><span>max expiry</span></div>
                <div><strong>Private</strong><span>by default</span></div>
            </div>

            <p class="login-quote">“Sharing should feel effortless, even when the files are anything but small.”</p>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <div class="login-mascot" id="loginMascot" aria-hidden="true">
                    <svg viewBox="0 0 180 150" role="presentation">
                        <circle class="mascot-halo" cx="90" cy="70" r="62"/>
                        <circle class="mascot-ear" cx="50" cy="39" r="18"/><circle class="mascot-ear" cx="130" cy="39" r="18"/>
                        <path class="mascot-body" d="M36 143c4-34 23-50 54-50s50 16 54 50"/>
                        <path class="mascot-head" d="M46 57c0-29 19-47 44-47s44 18 44 47v24c0 27-18 42-44 42S46 108 46 81z"/>
                        <g class="mascot-face">
                            <ellipse class="mascot-eye-patch" cx="70" cy="68" rx="15" ry="19" transform="rotate(19 70 68)"/>
                            <ellipse class="mascot-eye-patch" cx="110" cy="68" rx="15" ry="19" transform="rotate(-19 110 68)"/>
                            <g class="mascot-eye-unit"><ellipse class="mascot-eye" cx="71" cy="68" rx="8" ry="10"/><circle class="mascot-pupil" cx="71" cy="68" r="3.5"/></g>
                            <g class="mascot-eye-unit"><ellipse class="mascot-eye" cx="109" cy="68" rx="8" ry="10"/><circle class="mascot-pupil" cx="109" cy="68" r="3.5"/></g>
                            <ellipse class="mascot-muzzle" cx="90" cy="91" rx="18" ry="14"/>
                            <path class="mascot-nose" d="M84 87q6-5 12 0-1 7-6 7t-6-7z"/>
                            <path class="mascot-mouth" d="M90 94v4m-9 0c5 6 13 6 18 0"/>
                            <circle class="mascot-cheek" cx="61" cy="84" r="6"/><circle class="mascot-cheek" cx="119" cy="84" r="6"/>
                        </g>
                        <path class="mascot-scarf" d="M57 107q33 19 66 0l-7 17q-26 12-52 0z"/>
                        <g class="mascot-transfer-badge"><rect x="76" y="120" width="28" height="21" rx="4"/><path d="M78 124l12 9 12-9"/></g>
                        <g class="mascot-hands">
                            <ellipse cx="59" cy="132" rx="17" ry="14"/><ellipse cx="121" cy="132" rx="17" ry="14"/>
                            <path d="M48 133l18-13M114 120l18 13"/>
                        </g>
                    </svg>
                </div>
                <div class="login-heading">
                    <p class="section-label">MEMBER ACCESS</p>
                    <h2 id="login-title">Log in to your account</h2>
                    <p>Enter your details to continue to the dashboard.</p>
                </div>

                @if (session('status'))
                    <div class="status-message" role="status">{{ session('status') }}</div>
                @endif

                <form class="login-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label for="email">Email address</label>
                    <div class="input-shell @error('email') input-error @enderror">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 7 9-7"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" autofocus required>
                    </div>
                    @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                    <div class="label-row">
                        <label for="password">Password</label>
                        <span>Use your account password</span>
                    </div>
                    <div class="input-shell @error('password') input-error @enderror">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 10V8a6 6 0 0 1 12 0v2M5 10h14v11H5z"/></svg>
                        <input id="password" class="min-w-0 flex-1" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button
                            id="password-toggle"
                            class="shrink-0 rounded px-2 py-1 text-[10px] font-semibold text-[#718d18] transition hover:bg-[#eef5d2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#a6bd4a]"
                            type="button"
                            aria-controls="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        ><svg class="password-eye-icon" viewBox="0 0 24 24" aria-hidden="true"><path class="eye-open" d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12zm10-2a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/><path class="eye-closed" d="M3 3l18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6 0 10 6 10 6a18 18 0 0 1-2.1 2.5M6.2 6.2C3.6 8 2 12 2 12s4 6 10 6c1.3 0 2.5-.3 3.6-.7"/></svg><span class="sr-only">Show password</span></button>
                    </div>
                    @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                    <label class="remember-option">
                        <input name="remember" type="checkbox" value="1">
                        <span>Keep me logged in on this device</span>
                    </label>

                    <button class="login-button" type="submit">Log in <span>&rarr;</span></button>
                </form>

                <p class="login-security"><span>&#9671;</span> Protected by encrypted, session-based authentication.</p>
            </div>
        </section>
    </main>

    <script>
        const passwordInput = document.querySelector('#password');
        const passwordToggle = document.querySelector('#password-toggle');
        const emailInput = document.querySelector('#email');
        const mascot = document.querySelector('#loginMascot');

        emailInput.addEventListener('focus', () => mascot.classList.add('email-focused'));
        emailInput.addEventListener('blur', () => mascot.classList.remove('email-focused'));
        passwordInput.addEventListener('focus', () => mascot.classList.add('password-focused'));
        passwordInput.addEventListener('blur', () => mascot.classList.remove('password-focused'));

        let eyeFrame;
        document.addEventListener('pointermove', (event) => {
            if (eyeFrame) cancelAnimationFrame(eyeFrame);
            eyeFrame = requestAnimationFrame(() => {
                const bounds = mascot.getBoundingClientRect();
                const centerX = bounds.left + bounds.width / 2;
                const centerY = bounds.top + bounds.height * .46;
                const distance = Math.hypot(event.clientX - centerX, event.clientY - centerY) || 1;
                const strength = Math.min(4, distance / 55);
                mascot.style.setProperty('--look-x', `${((event.clientX - centerX) / distance) * strength}px`);
                mascot.style.setProperty('--look-y', `${((event.clientY - centerY) / distance) * strength}px`);
            });
        });
        document.addEventListener('pointerleave', () => {
            mascot.style.setProperty('--look-x', '0px');
            mascot.style.setProperty('--look-y', '0px');
        });

        passwordToggle.addEventListener('click', () => {
            const isPasswordVisible = passwordInput.type === 'text';

            passwordInput.type = isPasswordVisible ? 'password' : 'text';
            passwordToggle.setAttribute('aria-label', isPasswordVisible ? 'Show password' : 'Hide password');
            passwordToggle.setAttribute('aria-pressed', String(! isPasswordVisible));
            passwordToggle.querySelector('.sr-only').textContent = isPasswordVisible ? 'Show password' : 'Hide password';
            mascot.classList.toggle('password-visible', !isPasswordVisible);
        });
    </script>
@endsection
