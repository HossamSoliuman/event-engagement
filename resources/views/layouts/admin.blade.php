<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — EventBomb</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --eb-red: #FF3D00;
            --eb-dark: #0D0D1A;
            --eb-card: #161628;
            --eb-border: #2a2a45;
            --eb-text: #e8e8f0;
            --eb-muted: #8888aa;
            --eb-gold: #FFD700;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--eb-dark);
            color: var(--eb-text);
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Syne', sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: var(--eb-card);
            border-right: 1px solid var(--eb-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid var(--eb-border);
        }

        .sidebar-logo span {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--eb-red);
            letter-spacing: -0.5px;
        }

        .sidebar-logo small {
            display: block;
            color: var(--eb-muted);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
            overflow-y: auto;
        }

        .nav-section {
            padding: 8px 20px 4px;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--eb-muted);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--eb-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all .15s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            color: var(--eb-text);
            background: rgba(255, 255, 255, .04);
        }

        .nav-item.active {
            color: var(--eb-red);
            border-left-color: var(--eb-red);
            background: rgba(255, 61, 0, .08);
        }

        .nav-item .icon {
            width: 18px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--eb-border);
        }

        .user-badge {
            font-size: 13px;
            color: var(--eb-muted);
        }

        .user-badge strong {
            display: block;
            color: var(--eb-text);
            font-size: 14px;
        }

        /* Main */
        .main-wrap {
            margin-left: 240px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--eb-card);
            border-bottom: 1px solid var(--eb-border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .main-content {
            flex: 1;
            padding: 28px;
        }

        /* Cards */
        .card {
            background: var(--eb-card);
            border: 1px solid var(--eb-border);
            border-radius: 12px;
        }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--eb-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-body {
            padding: 22px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .15s;
            font-family: 'DM Sans', sans-serif;
        }

        .btn-primary {
            background: var(--eb-red);
            color: #fff;
        }

        .btn-primary:hover {
            background: #e03500;
        }

        .btn-secondary {
            background: var(--eb-border);
            color: var(--eb-text);
        }

        .btn-secondary:hover {
            background: #383860;
        }

        .btn-success {
            background: #1a7a4a;
            color: #fff;
        }

        .btn-success:hover {
            background: #15623c;
        }

        .btn-danger {
            background: #7a1a1a;
            color: #fff;
        }

        .btn-danger:hover {
            background: #621515;
        }

        .btn-gold {
            background: var(--eb-gold);
            color: #0D0D1A;
        }

        .btn-gold:hover {
            background: #e6c200;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--eb-border);
            color: var(--eb-text);
        }

        .btn-outline:hover {
            border-color: var(--eb-red);
            color: var(--eb-red);
        }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--eb-card);
            border: 1px solid var(--eb-border);
            border-radius: 12px;
            padding: 20px;
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--eb-muted);
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
        }

        .stat-value.red {
            color: var(--eb-red);
        }

        .stat-value.gold {
            color: var(--eb-gold);
        }

        .stat-value.green {
            color: #4ade80;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(74, 222, 128, .1);
            border: 1px solid rgba(74, 222, 128, .3);
            color: #4ade80;
        }

        .alert-error {
            background: rgba(239, 68, 68, .1);
            border: 1px solid rgba(239, 68, 68, .3);
            color: #f87171;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .badge-pending {
            background: rgba(251, 191, 36, .15);
            color: #fbbf24;
        }

        .badge-approved {
            background: rgba(74, 222, 128, .15);
            color: #4ade80;
        }

        .badge-rejected {
            background: rgba(239, 68, 68, .15);
            color: #f87171;
        }

        .badge-on-screen {
            background: rgba(255, 215, 0, .2);
            color: var(--eb-gold);
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--eb-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .form-control {
            width: 100%;
            background: var(--eb-dark);
            border: 1px solid var(--eb-border);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--eb-text);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color .15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--eb-red);
        }

        .form-control::placeholder {
            color: var(--eb-muted);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .form-check input {
            accent-color: var(--eb-red);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table th {
            text-align: left;
            padding: 10px 14px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--eb-muted);
            border-bottom: 1px solid var(--eb-border);
        }

        .table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            vertical-align: middle;
        }

        .table tr:hover td {
            background: rgba(255, 255, 255, .02);
        }

        /* Toggle switch */
        .toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--eb-border);
            border-radius: 24px;
            transition: .2s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: .2s;
        }

        input:checked+.toggle-slider {
            background: var(--eb-red);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(20px);
        }

        /* Grid helpers */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        .mb-1 {
            margin-bottom: 6px;
        }

        .mb-2 {
            margin-bottom: 12px;
        }

        .mb-3 {
            margin-bottom: 20px;
        }

        .mt-3 {
            margin-top: 20px;
        }

        .text-muted {
            color: var(--eb-muted);
        }

        .text-sm {
            font-size: 13px;
        }

        .font-bold {
            font-weight: 700;
        }

        .w-full {
            width: 100%;
        }

        /* Foto grid */
        .foto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .foto-card {
            background: var(--eb-card);
            border: 1px solid var(--eb-border);
            border-radius: 10px;
            overflow: hidden;
            transition: transform .15s;
        }

        .foto-card:hover {
            transform: translateY(-2px);
        }

        .foto-card.on-screen {
            border-color: var(--eb-gold);
            box-shadow: 0 0 20px rgba(255, 215, 0, .2);
        }

        .foto-card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
        }

        .foto-card-body {
            padding: 10px;
        }

        .foto-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            padding: 10px;
            border-top: 1px solid var(--eb-border);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-wrap {
                margin-left: 0;
            }

            .grid-2,
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">
            <span>⚡ EventBomb</span>
            <small>Admin Console</small>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="{{ route('admin.dashboard') }}"
                class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="icon">◈</span> Dashboard
            </a>
            <a href="{{ route('admin.events.index') }}"
                class="nav-item {{ request()->routeIs('admin.events*') ? 'active' : '' }}">
                <span class="icon">◉</span> Events
            </a>

            @php $activeEvent = \App\Models\Event::where('is_active', true)->first(); @endphp
            @if ($activeEvent)
                <div class="nav-section">Active Event</div>
                <a href="{{ route('admin.fotos.index', $activeEvent) }}"
                    class="nav-item {{ request()->routeIs('admin.fotos*') ? 'active' : '' }}">
                    <span class="icon">📷</span> Foto Queue
                    @php $pending = $activeEvent->getPendingFotosCount(); @endphp
                    @if ($pending > 0)
                        <span
                            style="margin-left:auto; background:var(--eb-red); color:#fff; font-size:11px; padding:2px 7px; border-radius:10px;">{{ $pending }}</span>
                    @endif
                </a>
                <a href="{{ route('vidiwall.show', $activeEvent->slug) }}" target="_blank" class="nav-item">
                    <span class="icon">📺</span> Vidiwall ↗
                </a>
                <a href="{{ route('event.landing', $activeEvent->slug) }}" target="_blank" class="nav-item">
                    <span class="icon">📱</span> Guest View ↗
                </a>
            @endif

            <div class="nav-section">Account</div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="nav-item"
                    style="background:none; border:none; width:100%; cursor:pointer; text-align:left;">
                    <span class="icon">⎋</span> Logout
                </button>
            </form>
        </nav>
        <div class="sidebar-footer">
            <div class="user-badge">
                <strong>{{ auth()->user()->name }}</strong>
                {{ ucfirst(auth()->user()->role) }}
            </div>
        </div>
    </div>

    <div class="main-wrap">
        <div class="topbar">
            <h1>@yield('page-title', 'Dashboard')</h1>
            <div class="flex gap-2 items-center">
                @if ($activeEvent ?? null)
                    <span class="badge badge-approved">● LIVE: {{ $activeEvent->name }}</span>
                @endif
                @yield('topbar-actions')
            </div>
        </div>

        <div class="main-content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
