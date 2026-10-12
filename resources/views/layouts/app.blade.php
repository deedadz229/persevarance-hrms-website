<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · PTI HRMS</title>
    <style>
        /* ── PTI Design Tokens (Anti-AI-Slop Architecture) ────────── */
        :root {
            /* Brand Identity (PTI Vermilion Corporate Red) */
            --brand: #b91c1c;
            --brand-dark: #991b1b;
            --brand-hover: #dc2626;
            --brand-soft: #fef2f2;
            --brand-border: #fecaca;

            /* Surfaces & Canvases */
            --canvas: #f8fafc;
            --surface: #ffffff;
            --surface-subtle: #f1f5f9;
            --sidebar: #0b0f17;

            /* Ink & Typography */
            --ink: #0f172a;
            --ink-secondary: #334155;
            --muted: #64748b;
            --muted-light: #94a3b8;

            /* Borders & Dividers */
            --line: #e2e8f0;
            --line-subtle: #f1f5f9;
            --line-strong: #cbd5e1;

            /* Semantic Status (Not Rainbow) */
            --status-success-bg: #f0fdf4;
            --status-success-fg: #15803d;
            --status-success-border: #bbf7d0;

            --status-warning-bg: #fffbeb;
            --status-warning-fg: #b45309;
            --status-warning-border: #fde68a;

            --status-danger-bg: #fef2f2;
            --status-danger-fg: #b91c1c;
            --status-danger-border: #fecaca;

            --status-info-bg: #f1f5f9;
            --status-info-fg: #334155;
            --status-info-border: #cbd5e1;

            /* Radius Scale (Differentiated Hierarchy) */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-full: 9999px;

            /* Shadows (Restrained & Functional) */
            --shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.05);
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            --shadow-dropdown: 0 10px 25px -3px rgba(15, 23, 42, 0.12), 0 4px 6px -4px rgba(15, 23, 42, 0.06);

            /* Timing */
            --trans-fast: 150ms ease-out;
            --trans-normal: 200ms ease-out;
        }

        /* ── Base Reset & System Typography ───────────────────────── */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13.5px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        button, input, select, textarea {
            font: inherit;
        }

        /* ── Tabular Nums for Data Alignment ──────────────────────── */
        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        /* ── Accessibility & Focus Rings ─────────────────────────── */
        :focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
        }

        /* ── Prefers Reduced Motion (WCAG 2.3.3) ──────────────────── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* ── App Shell Layout ─────────────────────────────────────── */
        .app-shell {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
        }
        .main-panel {
            min-width: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--canvas);
        }

        /* ── Topbar (Clean, 60px Sticky Header) ───────────────────── */
        .topbar {
            display: flex;
            align-items: center;
            gap: 16px;
            height: 60px;
            padding: 0 28px;
            border-bottom: 1px solid var(--line);
            background: var(--surface);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            flex-shrink: 0;
            transition: background var(--trans-fast), color var(--trans-fast), border-color var(--trans-fast);
        }
        .sidebar-toggle:hover {
            background: var(--surface-subtle);
            color: var(--ink);
            border-color: var(--line-strong);
        }
        .page-heading {
            min-width: 0;
        }
        .page-heading h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.25;
            letter-spacing: -0.01em;
        }
        .page-heading p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 11.5px;
            line-height: 1.3;
        }
        .topbar-spacer {
            flex: 1;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── Language Switcher ────────────────────────────────────── */
        .lang-switcher {
            display: inline-flex;
            align-items: center;
            background: var(--surface-subtle);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 2px;
            gap: 2px;
        }
        .lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            color: var(--muted);
            transition: all var(--trans-fast);
            line-height: 1.2;
        }
        .lang-btn:hover {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.6);
        }
        .lang-btn.is-active {
            background: var(--surface);
            color: var(--brand);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        /* ── Profile Dropdown Menu ────────────────────────────────── */
        .profile-menu {
            position: relative;
        }
        .profile-menu summary {
            display: flex;
            align-items: center;
            gap: 10px;
            list-style: none;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: var(--radius-sm);
            transition: background var(--trans-fast);
        }
        .profile-menu summary:hover {
            background: var(--surface-subtle);
        }
        .profile-menu summary::-webkit-details-marker {
            display: none;
        }
        .avatar {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: var(--radius-full);
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 700;
            font-size: 13px;
            border: 1px solid var(--brand-border);
            flex-shrink: 0;
        }
        .profile-meta strong, .profile-meta span {
            display: block;
        }
        .profile-meta strong {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.25;
        }
        .profile-meta span {
            margin-top: 1px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.2;
        }
        .profile-dropdown {
            position: absolute;
            z-index: 30;
            top: calc(100% + 8px);
            right: 0;
            width: 200px;
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            box-shadow: var(--shadow-dropdown);
        }
        .profile-dropdown a, .profile-dropdown button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border: 0;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            text-align: left;
            cursor: pointer;
            transition: background var(--trans-fast), color var(--trans-fast);
            background: transparent;
        }
        .profile-dropdown a {
            color: var(--ink);
        }
        .profile-dropdown a:hover {
            background: var(--surface-subtle);
            color: var(--brand);
        }
        .profile-dropdown button {
            color: var(--status-danger-fg);
            margin-top: 2px;
            border-top: 1px solid var(--line-subtle);
            border-radius: 0 0 var(--radius-sm) var(--radius-sm);
            padding-top: 9px;
        }
        .profile-dropdown button:hover {
            background: var(--brand-soft);
        }

        /* ── Main Content Area ────────────────────────────────────── */
        .content {
            padding: 24px 28px 48px;
            flex: 1;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* ── Flash Notifications ──────────────────────────────────── */
        .flash {
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .flash-success {
            border: 1px solid var(--status-success-border);
            background: var(--status-success-bg);
            color: var(--status-success-fg);
        }
        .flash-error {
            border: 1px solid var(--status-danger-border);
            background: var(--status-danger-bg);
            color: var(--status-danger-fg);
        }

        /* ── Foundation Reusable Components (Cross-View Support) ───── */
        .card {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            box-shadow: var(--shadow-xs);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: var(--radius-sm);
            padding: 3px 8px;
            background: var(--brand-soft);
            color: var(--brand);
            border: 1px solid var(--brand-border);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* ── Responsive Adaptations ───────────────────────────────── */
        @media (max-width: 1024px) {
            .topbar {
                padding: 0 16px;
            }
            .content {
                padding: 20px 16px 36px;
            }
        }
        @media (max-width: 768px) {
            .profile-meta {
                display: none;
            }
            .page-heading p {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    @include('components.sidebar')
    <div class="main-panel">
        <header class="topbar">
            <button class="sidebar-toggle" type="button" id="sidebarToggle" aria-label="Toggle sidebar" title="Sembunyikan/Tampilkan Sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="page-heading">
                <h1>@yield('title', 'Dashboard')</h1>
                <p>Perseverance Technology Indonesia · HRMS</p>
            </div>
            <div class="topbar-spacer"></div>
            <div class="topbar-actions">
                {{-- Language Switcher Component --}}
                <div class="lang-switcher" aria-label="{{ __('common.language') }}">
                    <a href="{{ route('locale.switch', 'id') }}" 
                       class="lang-btn {{ app()->getLocale() === 'id' ? 'is-active' : '' }}" 
                       title="Bahasa Indonesia">
                        <span>🇮🇩</span> ID
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}" 
                       class="lang-btn {{ app()->getLocale() === 'en' ? 'is-active' : '' }}" 
                       title="English">
                        <span>🇬🇧</span> EN
                    </a>
                </div>

                <details class="profile-menu">
                    <summary>
                        <span class="avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="profile-meta">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->role?->label ?? 'Tanpa role' }}</span>
                        </span>
                    </summary>
                    <div class="profile-dropdown">
                        <a href="{{ route('settings.index') }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: var(--muted);">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            <span>{{ auth()->user()->role?->name === 'super_admin' ? __('navigation.settings') : __('navigation.profile_account') }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: var(--status-danger-fg);">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>{{ __('navigation.logout') }}</span>
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
        <main class="content">
            @if (session('success')) <div class="flash flash-success">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="flash flash-error">{{ session('error') }}</div> @endif
            @yield('content')
        </main>
    </div>
</div>
<script>
(function() {
    var STORAGE_KEY = 'pti_sidebar_collapsed';
    var sidebar = document.querySelector('.sidebar');
    var toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;

    function applyState(collapsed, animate) {
        if (animate) sidebar.style.transition = 'width var(--trans-normal), min-width var(--trans-normal), padding var(--trans-normal)';
        else sidebar.style.transition = 'none';
        if (collapsed) {
            sidebar.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('sidebar-collapsed');
        }
    }

    var initialCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
    applyState(initialCollapsed, false);

    toggle.addEventListener('click', function() {
        var isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        var next = !isCollapsed;
        applyState(next, true);
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && !sidebar.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
            applyState(true, true);
            localStorage.setItem(STORAGE_KEY, '1');
        }
    });
})();
</script>
<script>
(function() {
    function initSidebarDropdowns() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        const groupLabels = sidebar.querySelectorAll('.nav-group-label');
        if (!groupLabels.length) return;

        groupLabels.forEach((label, idx) => {
            const groupEl = label.closest('.nav-group');
            const groupIndex = label.dataset.groupIndex ?? (groupEl ? groupEl.dataset.groupIndex : String(idx));
            const submenuId = label.getAttribute('aria-controls');
            const submenu = (submenuId ? document.getElementById(submenuId) : null)
                || (groupEl ? groupEl.querySelector('.nav-submenu') : null)
                || label.nextElementSibling;

            if (!submenu) return;

            const storageKey = 'pti_sidebar_group_' + groupIndex;
            const isServerActive = label.getAttribute('aria-expanded') === 'true' || submenu.classList.contains('open');

            // 1. Initial state:
            // Kelompok yang berisi halaman aktif harus otomatis terbuka saat halaman dimuat.
            if (isServerActive) {
                submenu.classList.add('open');
                label.setAttribute('aria-expanded', 'true');
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            } else {
                // Pulihkan status buka/tutup dari localStorage jika pernah disimpan
                try {
                    const stored = localStorage.getItem(storageKey);
                    if (stored === '1') {
                        submenu.classList.add('open');
                        label.setAttribute('aria-expanded', 'true');
                    } else if (stored === '0') {
                        submenu.classList.remove('open');
                        label.setAttribute('aria-expanded', 'false');
                    }
                } catch (e) {}
            }

            // 2. Click handler untuk membuka / menutup dropdown
            label.addEventListener('click', function(e) {
                e.preventDefault();
                const willOpen = !submenu.classList.contains('open');
                if (willOpen) {
                    submenu.classList.add('open');
                    label.setAttribute('aria-expanded', 'true');
                } else {
                    submenu.classList.remove('open');
                    label.setAttribute('aria-expanded', 'false');
                }
                try {
                    localStorage.setItem(storageKey, willOpen ? '1' : '0');
                } catch (e) {}
            });
        });

        // Respect prefers-reduced-motion
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const style = document.createElement('style');
            style.textContent = '.nav-submenu, .nav-group-label .chevron-icon { transition: none !important; }';
            document.head.appendChild(style);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarDropdowns);
    } else {
        initSidebarDropdowns();
    }
})();
</script>
</body>
</html>
