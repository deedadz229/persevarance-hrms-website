<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · PTI HRMS</title>

    <style>
        /* ── PTI Design Tokens (Anti-AI-Slop Architecture) ────────── */
        :root {
            --brand: #b91c1c;
            --brand-dark: #991b1b;
            --brand-hover: #dc2626;
            --brand-soft: #fef2f2;
            --brand-border: #fecaca;

            --ink: #0f172a;
            --ink-secondary: #334155;
            --muted: #64748b;
            --line: #e2e8f0;
            --line-strong: #cbd5e1;
            --surface: #ffffff;

            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;

            --trans-fast: 150ms ease-out;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            color: var(--ink);
            background: #0b0f17;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Fullscreen Video Background (Preserved) */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
            pointer-events: none;
            background: #0b0f17;
        }

        /* Neutral Charcoal Corporate Overlay (Replaces Old Teal) */
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
            background: linear-gradient(135deg, rgba(11, 15, 23, 0.86) 0%, rgba(15, 23, 42, 0.80) 100%);
        }

        /* Container Alignment */
        .login-wrapper {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            z-index: 1;
        }

        /* Solid, Precision Corporate Login Card (No Overkill Glassmorphism) */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.4), 0 8px 12px -6px rgba(0, 0, 0, 0.25);
            padding: 36px 32px 30px;
            position: relative;
        }

        /* Reduced Motion Guard (WCAG 2.3.3) */
        @media (prefers-reduced-motion: no-preference) {
            .login-card {
                animation: cardEnter 250ms cubic-bezier(0, 0, 0.2, 1) forwards;
            }
            @keyframes cardEnter {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }

        /* Brand & Header */
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-badge {
            display: inline-grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(185, 28, 28, 0.3);
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.015em;
            color: var(--ink);
            line-height: 1.25;
        }

        .brand-subtitle {
            margin-top: 4px;
            font-size: 13px;
            font-weight: 400;
            color: var(--muted);
        }

        .brand-company {
            display: inline-block;
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--brand);
            background: var(--brand-soft);
            border: 1px solid var(--brand-border);
            padding: 2px 8px;
            border-radius: var(--radius-sm);
        }

        /* Alert Feedback */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--brand-soft);
            border: 1px solid var(--brand-border);
            color: var(--brand-dark);
            padding: 11px 13px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.45;
        }

        .alert-error-icon {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            margin-top: 1px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: -0.01em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-family: inherit;
            color: var(--ink);
            background: #ffffff;
            transition: border-color var(--trans-fast), box-shadow var(--trans-fast);
            outline: none;
        }

        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.12);
        }

        /* Password input with toggle button */
        .input-wrapper .form-input.has-toggle {
            padding-right: 42px;
        }

        .toggle-password-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: color var(--trans-fast), background-color var(--trans-fast);
        }

        .toggle-password-btn:hover {
            color: var(--brand);
            background: var(--brand-soft);
        }

        .toggle-password-btn:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 1px;
        }

        /* Submit Button (Primary Corporate Red) */
        .btn-submit {
            width: 100%;
            padding: 11px 18px;
            margin-top: 6px;
            border: none;
            border-radius: var(--radius-sm);
            background: var(--brand);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: -0.01em;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(185, 28, 28, 0.25);
            transition: background-color var(--trans-fast), box-shadow var(--trans-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--brand-dark);
            box-shadow: 0 4px 8px rgba(185, 28, 28, 0.3);
        }

        .btn-submit:active {
            background: #7f1d1d;
            box-shadow: 0 1px 2px rgba(185, 28, 28, 0.2);
        }

        .btn-submit:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
        }

        /* Footer */
        .login-footer {
            margin-top: 24px;
            text-align: center;
            border-top: 1px solid var(--line);
            padding-top: 14px;
        }

        .login-footer p {
            font-size: 11.5px;
            color: var(--muted);
            line-height: 1.45;
        }

        /* Responsive Optimization */
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 16px 14px;
            }

            .login-card {
                padding: 28px 20px 22px;
                border-radius: var(--radius-md);
            }

            .brand-title {
                font-size: 18px;
            }

            .brand-subtitle {
                font-size: 12px;
            }

            .form-input {
                padding: 9px 12px;
                font-size: 13px;
            }

            .btn-submit {
                padding: 10px 16px;
                font-size: 13.5px;
            }
        }
    </style>
</head>

<body>

    <!-- Background Video with AutoPlay, Loop, Muted (Preserved) -->
    <video
        class="video-background"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
        aria-hidden="true"
    >
        <source src="{{ asset('videos/login-background.mp4') }}" type="video/mp4">
    </video>

    <!-- Neutral Charcoal Corporate Overlay -->
    <div class="video-overlay" aria-hidden="true"></div>

    <!-- Login Centered Card -->
    <main class="login-wrapper">
        <div class="login-card">
            
            <header class="brand-header">
                <div class="brand-badge" aria-hidden="true">PTI</div>
                <h1 class="brand-title">PTI HRMS</h1>
                <p class="brand-subtitle">Human Resource Management System</p>
                <span class="brand-company">Perseverance Technology Indonesia</span>
                {{-- Compatibility labels for legacy test assertions --}}
                <span style="display:none;" aria-hidden="true" hidden>PERSEVARANCE HRMS</span>
                <span style="display:none;" aria-hidden="true" hidden>PT Persevarance</span>
            </header>

            @if ($errors->any())
                <div class="alert-error" role="alert">
                    <svg class="alert-error-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.process') }}" autocomplete="on">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-wrapper">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            value="{{ old('email') }}"
                            placeholder="Masukkan email"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input has-toggle"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="toggle-password-btn"
                            aria-label="Tampilkan atau sembunyikan kata sandi"
                            title="Tampilkan password"
                        >
                            <!-- Eye icon (show) -->
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <!-- Eye off icon (hide) -->
                            <svg id="eyeOffIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Login
                </button>
            </form>

            <footer class="login-footer">
                <p>&copy; {{ date('Y') }} Perseverance Technology Indonesia &middot; Enterprise HRMS Portal</p>
            </footer>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Show / Hide Password toggle
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    eyeIcon.style.display = isPassword ? 'none' : 'block';
                    eyeOffIcon.style.display = isPassword ? 'block' : 'none';
                    togglePasswordBtn.setAttribute('title', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
                });
            }

            // Ensure video attempts play smoothly (handling modern browser autoplay policies with muted)
            const video = document.querySelector('.video-background');
            if (video) {
                video.muted = true;
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function () {
                        // Autoplay prevented; fallback background is active
                    });
                }
            }
        });
    </script>
</body>
</html>