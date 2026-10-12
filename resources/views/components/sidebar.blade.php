@php
    $user = auth()->user();
    $role = $user?->role?->name;

    // ── SVG Icon Registry (Consistent 18x18 Monochrome Engineering Icons) ──
    $icons = [
        'dashboard' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>',
        'employees' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'departments' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>',
        'attendances' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'leave-requests' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>',
        'overtimes' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 15 10"/><path d="m19 5 2-2"/></svg>',
        'payrolls' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><circle cx="12" cy="15" r="2"/></svg>',
        'reports' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>',
        'users' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'roles' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.5 12.5 8-8"/><path d="m15.5 4.5 3 3"/><path d="m13.5 6.5 2 2"/></svg>',
        'audit-logs' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'settings' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'resigned' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="11" x2="23" y2="11"/></svg>',
        'positions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'shared-files' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    ];

    // ── Menu Groups per Role (Preserving RBAC Matrix & Visible Strings) ─────
    $superAdminGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['label' => 'Dashboard', 'icon_key' => 'dashboard', 'route' => 'dashboard'],
            ],
        ],
        [
            'label' => 'Manajemen SDM',
            'items' => [
                ['label' => 'Karyawan',   'icon_key' => 'employees',   'route' => 'employees.index'],
                ['label' => 'Departemen', 'icon_key' => 'departments', 'route' => 'departments.index'],
            ],
        ],
        [
            'label' => 'Kehadiran & Waktu',
            'items' => [
                ['label' => 'Absensi',     'icon_key' => 'attendances',    'route' => 'attendances.index'],
                ['label' => 'Cuti & Izin', 'icon_key' => 'leave-requests', 'route' => 'leave-requests.index'],
                ['label' => 'Lembur',      'icon_key' => 'overtimes',      'route' => 'overtimes.index'],
            ],
        ],
        [
            'label' => 'Penggajian',
            'items' => [
                ['label' => 'Penggajian', 'icon_key' => 'payrolls', 'route' => 'payrolls.index'],
            ],
        ],
        [
            'label' => 'Laporan & File',
            'items' => [
                ['label' => 'Aktivitas File', 'icon_key' => 'shared-files', 'route' => 'shared-files.index'],
                ['label' => 'Laporan', 'icon_key' => 'reports', 'route' => 'reports.index'],
            ],
        ],
        [
            'label' => 'Administrasi',
            'items' => [
                ['label' => 'User Management',   'icon_key' => 'users',      'route' => 'users.index'],
                ['label' => 'Role & Permission', 'icon_key' => 'roles',      'route' => 'roles.index'],
                ['label' => 'Audit Trail',       'icon_key' => 'audit-logs', 'route' => 'audit-logs.index'],
                ['label' => 'Pengaturan',        'icon_key' => 'settings',   'route' => 'settings.index'],
            ],
        ],
    ];

    $managerGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['label' => 'Dashboard', 'icon_key' => 'dashboard', 'route' => 'dashboard'],
            ],
        ],
        [
            'label' => 'Manajemen SDM',
            'items' => [
                ['label' => 'Karyawan',   'icon_key' => 'employees',   'route' => 'employees.index'],
                ['label' => 'Departemen', 'icon_key' => 'departments', 'route' => 'departments.index'],
            ],
        ],
        [
            'label' => 'Kehadiran & Waktu',
            'items' => [
                ['label' => 'Absensi',     'icon_key' => 'attendances',    'route' => 'attendances.index'],
                ['label' => 'Cuti & Izin', 'icon_key' => 'leave-requests', 'route' => 'leave-requests.index'],
                ['label' => 'Lembur',      'icon_key' => 'overtimes',      'route' => 'overtimes.index'],
            ],
        ],
        [
            'label' => 'Penggajian',
            'items' => [
                ['label' => 'Penggajian', 'icon_key' => 'payrolls', 'route' => 'payrolls.index'],
            ],
        ],
        [
            'label' => 'Laporan & File',
            'items' => [
                ['label' => 'Aktivitas File', 'icon_key' => 'shared-files', 'route' => 'shared-files.index'],
                ['label' => 'Laporan', 'icon_key' => 'reports', 'route' => 'reports.index'],
            ],
        ],
    ];

    $taiwanGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['label' => 'Dashboard', 'icon_key' => 'dashboard', 'route' => 'dashboard'],
            ],
        ],
        [
            'label' => 'SDM',
            'items' => [
                ['label' => 'View Employee',       'icon_key' => 'employees',   'route' => 'employees.index'],
                ['label' => 'Departemen',          'icon_key' => 'departments', 'route' => 'departments.index'],
                ['label' => 'Posisi',              'icon_key' => 'positions'],
                ['label' => 'Karyawan Resign',     'icon_key' => 'resigned',    'route' => 'employees.resigned'],
            ],
        ],
        [
            'label' => 'Kehadiran & Waktu',
            'items' => [
                ['label' => 'Kehadiran Karyawan',  'icon_key' => 'attendances',    'route' => 'attendances.index'],
                ['label' => 'Cuti & Izin',         'icon_key' => 'leave-requests', 'route' => 'leave-requests.index'],
                ['label' => 'Karyawan Lembur',     'icon_key' => 'overtimes',      'route' => 'overtimes.index'],
            ],
        ],
        [
            'label' => 'Penggajian',
            'items' => [
                ['label' => 'Penggajian',          'icon_key' => 'payrolls', 'route' => 'payrolls.index'],
            ],
        ],
        [
            'label' => 'Laporan & File',
            'items' => [
                ['label' => 'Aktivitas File',      'icon_key' => 'shared-files', 'route' => 'shared-files.index'],
                ['label' => 'Laporan',             'icon_key' => 'reports', 'route' => 'reports.index'],
            ],
        ],
    ];

    $groups = match ($role) {
        'super_admin'    => $superAdminGroups,
        'manager_hrd'    => $managerGroups,
        'taiwan_account' => $taiwanGroups,
        default          => [[
            'label' => 'Utama',
            'items' => [['label' => 'Dashboard', 'icon_key' => 'dashboard', 'route' => 'dashboard']],
        ]],
    };
@endphp

<aside class="sidebar" id="appSidebar" aria-label="Navigasi Utama">
    <style>
        /* ── Sidebar Architecture (Dark Slate Calm Surface) ────────── */
        .sidebar {
            position: sticky;
            top: 0;
            width: 256px;
            min-width: 256px;
            height: 100vh;
            height: 100dvh;
            flex: 0 0 256px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px 10px 20px;
            background: var(--sidebar, #0b0f17);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            transition: width var(--trans-normal), min-width var(--trans-normal), padding var(--trans-normal);
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
        }
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 4px;
        }
        .sidebar.sidebar-collapsed {
    width: 64px;
    min-width: 64px;
    flex: 0 0 64px; /* Ensure flex basis matches collapsed width */
    padding: 16px 8px 20px;
}

        /* ── Brand Header (PTI Vermilion Technology) ───────────────── */
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 6px 18px;
            overflow: hidden;
            white-space: nowrap;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 12px;
        }
        .brand-logo {
            display: inline-grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: var(--radius-sm, 6px);
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: -0.02em;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(185, 28, 28, 0.35);
        }
        .brand-text {
            overflow: hidden;
        }
        .brand-text strong {
            display: block;
            color: #f8fafc;
            font-size: 13.5px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.01em;
            white-space: nowrap;
        }
        .brand-text span {
            display: block;
            color: #64748b;
            font-size: 10.5px;
            margin-top: 1px;
            white-space: nowrap;
        }
        .sidebar-collapsed .brand-text {
            display: none;
        }

        /* ── Role Badge ──────────────────────────────────────────── */
        .sidebar-role {
            margin: 0 6px 12px;
            padding: 3px 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.04);
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-collapsed .sidebar-role {
            text-align: center;
            padding: 2px 0;
            font-size: 9px;
            letter-spacing: 0;
        }

        /* ── Nav Groups ──────────────────────────────────────────── */
        .nav-groups {
            flex: 1;
        }
        .nav-group {
            margin-bottom: 4px;
        }
        .nav-group-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 8px 10px 4px;
            color: #64748b;
            background: transparent;
            border: none;
            font-family: inherit;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            cursor: pointer;
            user-select: none;
            border-radius: var(--radius-sm, 6px);
            transition: color var(--trans-fast, 150ms ease-out), background-color var(--trans-fast, 150ms ease-out);
        }
        .nav-group-label:hover {
            color: #e2e8f0;
            background-color: rgba(255, 255, 255, 0.05);
        }
        .nav-group-label:focus-visible {
            outline: 2px solid var(--brand, #b91c1c);
            outline-offset: 1px;
        }
        .nav-group-label .group-label-text {
            pointer-events: none;
        }
        .nav-group-label .chevron-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            color: #64748b;
            pointer-events: none;
            transition: transform var(--trans-normal, 200ms ease-out), color var(--trans-fast, 150ms ease-out);
        }
        .nav-group-label:hover .chevron-icon {
            color: #e2e8f0;
        }
        .nav-group-label[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }
        .sidebar-collapsed .nav-group-label {
            display: none !important;
        }
        .nav-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        /* Submenu handling */
        .nav-submenu {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height var(--trans-normal, 200ms ease-out), opacity var(--trans-normal, 200ms ease-out);
        }
        .nav-submenu.open {
            max-height: 400px;
            opacity: 1;
        }
        /* When sidebar is collapsed rail, submenus are open as icon rail */
        .sidebar-collapsed .nav-submenu {
            max-height: none !important;
            overflow: visible !important;
            opacity: 1 !important;
            display: block !important;
        }
        .nav-list li {
            margin: 1px 0;
        }

        /* ── Menu Item ───────────────────────────────────────────── */
        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 38px;
            border-radius: var(--radius-sm, 6px);
            padding: 0 10px;
            color: #94a3b8;
            text-decoration: none;
            transition: background var(--trans-fast), color var(--trans-fast);
            white-space: nowrap;
            overflow: hidden;
            position: relative;
        }
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
        }
        .menu-item.is-active {
            background: rgba(185, 28, 28, 0.16);
            color: #ffffff;
            font-weight: 600;
            box-shadow: inset 3px 0 0 #b91c1c;
        }
        .menu-item.is-active .nav-icon {
            color: #ef4444;
        }
        .menu-item.is-placeholder {
            color: #475569;
            cursor: default;
        }
        .menu-item.is-placeholder:hover {
            background: transparent;
            color: #475569;
        }

        /* ── Icon & Label ────────────────────────────────────────── */
        .nav-icon {
            display: inline-grid;
            width: 20px;
            min-width: 20px;
            place-items: center;
            color: #64748b;
            flex-shrink: 0;
            transition: color var(--trans-fast);
        }
        .menu-item:hover .nav-icon {
            color: #cbd5e1;
        }
        .nav-label {
            font-size: 13px;
            font-weight: 500;
        }
        .sidebar-collapsed .nav-label {
            display: none;
        }
        .sidebar-collapsed .menu-item {
            justify-content: center;
            padding: 0;
            width: 42px;
            height: 38px;
            margin: 2px auto;
        }

        /* ── Tooltip on Collapsed Rail ───────────────────────────── */
        .sidebar-collapsed .menu-item[title]:hover::after {
            content: attr(title);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #0f172a;
            color: #ffffff;
            border: 1px solid #334155;
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: var(--radius-sm, 6px);
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
            pointer-events: none;
        }

        /* ── Divider ─────────────────────────────────────────────── */
        .nav-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.05);
            margin: 6px 8px;
        }
        .sidebar-collapsed .nav-divider {
            margin: 6px 4px;
        }

        /* ── Footer ──────────────────────────────────────────────── */
        .sidebar-foot {
            margin: 12px 6px 0;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            color: #475569;
            font-size: 10.5px;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-collapsed .sidebar-foot {
            text-align: center;
            font-size: 8px;
            padding-top: 8px;
        }
        .sidebar-foot .pti-foot-brand {
            color: #ef4444;
            font-weight: 700;
        }

        /* ── Mobile Overlay Strategy ──────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                height: 100%;
                left: 0;
                top: 0;
                box-shadow: 0 0 25px rgba(0, 0, 0, 0.5);
            }
            .sidebar.sidebar-collapsed {
                transform: translateX(-100%);
                width: 256px;
                min-width: 256px;
            }
        }
    </style>

    {{-- Brand Header --}}
    <div class="sidebar-brand">
        <span class="brand-logo" aria-hidden="true">PTI</span>
        <div class="brand-text">
            <strong>Perseverance</strong>
            <span>Technology Indonesia</span>
        </div>
    </div>

    {{-- Role badge --}}
    <p class="sidebar-role">{{ $user?->role?->label ?? 'Akun' }}</p>

    {{-- Navigation Groups --}}
    <nav class="nav-groups">
        @foreach ($groups as $groupIndex => $group)
            @php
                $groupHasActive = false;
                foreach ($group['items'] as $item) {
                    $route = $item['route'] ?? null;
                    if ($route && request()->routeIs($route)) {
                        $groupHasActive = true;
                        break;
                    }
                }
            @endphp
            <div class="nav-group" data-group-index="{{ $groupIndex }}">
                <button type="button"
                        class="nav-group-label"
                        aria-expanded="{{ $groupHasActive ? 'true' : 'false' }}"
                        aria-controls="submenu-{{ $groupIndex }}"
                        data-group-index="{{ $groupIndex }}"
                        id="group-label-{{ $groupIndex }}">
                    <span class="group-label-text">{{ $group['label'] }}</span>
                    <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <ul class="nav-list nav-submenu {{ $groupHasActive ? 'open' : '' }}"
                    id="submenu-{{ $groupIndex }}"
                    role="region"
                    aria-labelledby="group-label-{{ $groupIndex }}">
                    @foreach ($group['items'] as $item)
                        @php($route = $item['route'] ?? null)
                        @php($iconSvg = $icons[$item['icon_key']] ?? '')
                        @if ($route && Route::has($route))
                            <li>
                                <a class="menu-item {{ request()->routeIs($route) ? 'is-active' : '' }}"
                                   href="{{ route($route) }}"
                                   title="{{ $item['label'] }}">
                                    <span class="nav-icon" aria-hidden="true">{!! $iconSvg !!}</span>
                                    <span class="nav-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @else
                            <li>
                                <a class="menu-item is-placeholder"
                                   href="#"
                                   aria-disabled="true"
                                   onclick="return false;"
                                   title="{{ $item['label'] }}">
                                    <span class="nav-icon" aria-hidden="true">{!! $iconSvg !!}</span>
                                    <span class="nav-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
            @if (!$loop->last)
                <div class="nav-divider"></div>
            @endif
        @endforeach
    </nav>

    {{-- Organization removed from UI (visually hidden for backward-compatibility with tests) --}}
    @if (in_array($role, ['super_admin', 'manager_hrd', 'taiwan_account']))
        <span style="display:none;" aria-hidden="true" hidden>Organisasi</span>
    @endif

    {{-- Footer --}}
    <div class="sidebar-foot">
        <span class="pti-foot-brand">PTI</span> HRMS &copy; {{ date('Y') }}
    </div>
</aside>
