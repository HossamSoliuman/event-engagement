<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Dashboard') — EventBomb Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --red:#FF3D00; --dark:#0A0A18; --card:#12121F; --card2:#1A1A2E;
            --border:#252540; --text:#E8E8F2; --muted:#7878A0; --gold:#FFD700;
            --green:#22C55E; --blue:#3B82F6; --sidebar:220px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--text);font-size:14px}
        h1,h2,h3,h4,h5{font-family:'Syne',sans-serif}
        a{color:inherit;text-decoration:none}
        code{font-family:monospace;background:rgba(255,255,255,.06);padding:2px 6px;border-radius:4px;font-size:12px}

        /* ── Sidebar ── */
        .sidebar{width:var(--sidebar);background:var(--card);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:transform .25s}
        .sb-logo{padding:20px 18px 16px;border-bottom:1px solid var(--border)}
        .sb-logo .wordmark{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--red)}
        .sb-logo .tagline{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:2px}
        .sb-scroll{flex:1;overflow-y:auto;padding:12px 0;scrollbar-width:none}
        .sb-scroll::-webkit-scrollbar{display:none}
        .sb-label{padding:10px 18px 4px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted)}
        .nav-item{display:flex;align-items:center;gap:9px;padding:9px 18px;color:var(--muted);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent;cursor:pointer;background:none;border-top:none;border-right:none;border-bottom:none;width:100%;text-align:left}
        .nav-item:hover{color:var(--text);background:rgba(255,255,255,.03)}
        .nav-item.active{color:var(--red);border-left-color:var(--red);background:rgba(255,61,0,.07)}
        .nav-item .ni-icon{width:16px;text-align:center;font-style:normal;flex-shrink:0}
        .nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px}
        .sb-footer{padding:14px 18px;border-top:1px solid var(--border)}
        .sb-user{display:flex;align-items:center;gap:10px}
        .sb-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border)}
        .sb-user-info .sb-name{font-weight:600;font-size:13px;line-height:1.2}
        .sb-user-info .sb-role{font-size:11px;color:var(--muted)}

        /* ── Mobile overlay ── */
        .sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:199}

        /* ── Main ── */
        .main-wrap{margin-left:var(--sidebar);min-height:100vh;display:flex;flex-direction:column}
        .topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 24px;height:56px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100}
        .tb-hamburger{display:none;background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;padding:4px}
        .tb-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;flex:1}
        .tb-actions{display:flex;align-items:center;gap:8px}

        /* Notification bell */
        .notif-btn{position:relative;background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;padding:6px;border-radius:8px;transition:color .15s}
        .notif-btn:hover{color:var(--text);background:rgba(255,255,255,.05)}
        .notif-dot{position:absolute;top:4px;right:4px;width:8px;height:8px;background:var(--red);border-radius:50%;border:2px solid var(--card)}

        .main-content{flex:1;padding:24px}

        /* ── Cards ── */
        .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
        .card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .card-header h3{font-size:14px;font-weight:700}
        .card-body{padding:20px}
        .card + .card{margin-top:16px}

        /* ── Stat cards ── */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}
        .stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px 20px}
        .stat-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:6px}
        .stat-value{font-family:'Syne',sans-serif;font-size:30px;font-weight:800;line-height:1}
        .stat-change{font-size:11px;color:var(--muted);margin-top:4px}
        .stat-value.c-red{color:var(--red)} .stat-value.c-gold{color:var(--gold)}
        .stat-value.c-green{color:var(--green)} .stat-value.c-blue{color:var(--blue)}

        /* ── Buttons ── */
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:'DM Sans',sans-serif;white-space:nowrap}
        .btn-primary{background:var(--red);color:#fff}.btn-primary:hover{background:#d93400}
        .btn-secondary{background:var(--border);color:var(--text)}.btn-secondary:hover{background:#303050}
        .btn-success{background:#166534;color:#fff}.btn-success:hover{background:#14532d}
        .btn-danger{background:#7f1d1d;color:#fff}.btn-danger:hover{background:#6b1a1a}
        .btn-gold{background:var(--gold);color:#0A0A18}.btn-gold:hover{background:#e6c200}
        .btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}.btn-outline:hover{border-color:var(--red);color:var(--red)}
        .btn-ghost{background:transparent;color:var(--muted)}.btn-ghost:hover{color:var(--text);background:rgba(255,255,255,.04)}
        .btn-sm{padding:5px 11px;font-size:12px;border-radius:6px}
        .btn-lg{padding:12px 24px;font-size:15px}
        .btn:disabled{opacity:.45;cursor:not-allowed}

        /* ── Forms ── */
        .form-group{margin-bottom:16px}
        .form-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:6px}
        .form-control{width:100%;background:var(--dark);border:1px solid var(--border);border-radius:8px;padding:9px 13px;color:var(--text);font-size:14px;font-family:'DM Sans',sans-serif;transition:border-color .15s}
        .form-control:focus{outline:none;border-color:var(--red)}
        .form-control::placeholder{color:var(--muted)}
        textarea.form-control{resize:vertical;min-height:80px}
        .form-hint{font-size:11px;color:var(--muted);margin-top:4px}
        .form-check{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--muted)}
        .form-check input{accent-color:var(--red);width:15px;height:15px;cursor:pointer}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}

        /* ── Table ── */
        .table-wrap{overflow-x:auto}
        .table{width:100%;border-collapse:collapse;font-size:13px}
        .table th{text-align:left;padding:10px 14px;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);border-bottom:1px solid var(--border);white-space:nowrap}
        .table td{padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.03);vertical-align:middle}
        .table tr:last-child td{border-bottom:none}
        .table tr:hover td{background:rgba(255,255,255,.015)}

        /* ── Badges ── */
        .badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .badge-pending{background:rgba(251,191,36,.12);color:#fbbf24}
        .badge-approved{background:rgba(34,197,94,.12);color:#22c55e}
        .badge-rejected{background:rgba(239,68,68,.12);color:#ef4444}
        .badge-on-screen{background:rgba(255,215,0,.15);color:var(--gold)}
        .badge-active{background:rgba(34,197,94,.12);color:#22c55e}
        .badge-inactive{background:rgba(120,120,160,.12);color:var(--muted)}
        .badge-winner{background:rgba(255,215,0,.2);color:var(--gold)}
        .badge-superadmin{background:rgba(255,61,0,.15);color:var(--red)}
        .badge-admin{background:rgba(59,130,246,.12);color:#60a5fa}
        .badge-moderator{background:rgba(120,120,160,.12);color:var(--muted)}

        /* ── Toggle ── */
        .toggle{position:relative;display:inline-block;width:42px;height:23px;flex-shrink:0}
        .toggle input{opacity:0;width:0;height:0}
        .toggle-slider{position:absolute;cursor:pointer;inset:0;background:var(--border);border-radius:23px;transition:.2s}
        .toggle-slider::before{position:absolute;content:"";height:17px;width:17px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
        input:checked+.toggle-slider{background:var(--red)}
        input:checked+.toggle-slider::before{transform:translateX(19px)}

        /* ── Alerts ── */
        .alert{padding:11px 15px;border-radius:8px;margin-bottom:16px;font-size:13px;display:flex;align-items:center;gap:10px}
        .alert-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);color:#4ade80}
        .alert-error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#f87171}
        .alert-info{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);color:#60a5fa}

        /* ── Tabs ── */
        .tabs{display:flex;gap:2px;border-bottom:1px solid var(--border);margin-bottom:20px}
        .tab{padding:9px 18px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;transition:all .15s;background:none;border-top:none;border-left:none;border-right:none;font-family:'DM Sans',sans-serif}
        .tab:hover{color:var(--text)}
        .tab.active{color:var(--red);border-bottom-color:var(--red)}
        .tab-count{font-size:10px;background:rgba(255,255,255,.08);padding:2px 7px;border-radius:10px;margin-left:4px}

        /* ── Foto grid ── */
        .foto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
        .foto-card{background:var(--card2);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:transform .15s,box-shadow .15s}
        .foto-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.4)}
        .foto-card.on-screen{border-color:var(--gold);box-shadow:0 0 24px rgba(255,215,0,.15)}
        .foto-card img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
        .foto-meta{padding:8px 10px;font-size:12px}
        .foto-meta strong{display:block;color:var(--text)}
        .foto-meta span{color:var(--muted)}
        .foto-actions{display:flex;flex-wrap:wrap;gap:5px;padding:8px 10px;border-top:1px solid var(--border)}

        /* ── Progress bar ── */
        .progress-bar{height:8px;background:var(--border);border-radius:4px;overflow:hidden}
        .progress-fill{height:100%;background:var(--red);border-radius:4px;transition:width .5s ease}
        .progress-fill.gold{background:var(--gold)}
        .progress-fill.green{background:var(--green)}

        /* ── Search ── */
        .search-bar{position:relative}
        .search-bar input{padding-left:36px}
        .search-bar::before{content:"🔍";position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:13px;pointer-events:none}

        /* ── Helpers ── */
        .flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}
        .gap-1{gap:4px}.gap-2{gap:8px}.gap-3{gap:12px}.gap-4{gap:16px}
        .mb-0{margin-bottom:0!important}.mb-1{margin-bottom:6px}.mb-2{margin-bottom:12px}.mb-3{margin-bottom:20px}.mb-4{margin-bottom:28px}
        .mt-2{margin-top:12px}.mt-3{margin-top:20px}
        .text-muted{color:var(--muted)}.text-sm{font-size:12px}.text-xs{font-size:11px}
        .text-red{color:var(--red)}.text-gold{color:var(--gold)}.text-green{color:var(--green)}
        .font-bold{font-weight:700}.truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .w-full{width:100%}.max-w-lg{max-width:640px}.max-w-xl{max-width:800px}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
        .grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
        .avatar-sm{width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0}
        .divider{border:none;border-top:1px solid var(--border);margin:20px 0}
        .empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
        .empty-state .empty-icon{font-size:44px;margin-bottom:14px}
        .empty-state h3{font-size:18px;margin-bottom:6px;color:var(--text)}
        .color-swatch{width:28px;height:28px;border-radius:6px;border:2px solid var(--border);cursor:pointer;flex-shrink:0}

        /* ── Activity log ── */
        .activity-item{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.03)}
        .activity-dot{width:8px;height:8px;border-radius:50%;background:var(--muted);flex-shrink:0;margin-top:6px}
        .activity-dot.red{background:var(--red)}.activity-dot.green{background:var(--green)}.activity-dot.gold{background:var(--gold)}

        /* ── Pagination ── */
        .pagination{display:flex;gap:4px;flex-wrap:wrap;margin-top:16px}
        nav.pagination{display:flex;gap:4px}

        /* ── Winner card ── */
        .winner-card{background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,61,0,.08));border:2px solid var(--gold);border-radius:14px;padding:24px;text-align:center}
        .winner-card .trophy{font-size:52px;margin-bottom:12px}
        .winner-card h2{font-size:22px;color:var(--gold)}
        .winner-card p{color:var(--muted);margin-top:6px}

        /* ── Responsive ── */
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sb-overlay.open{display:block}
            .main-wrap{margin-left:0}
            .tb-hamburger{display:block}
            .form-row,.form-row-3,.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}
            .main-content{padding:14px}
            .foto-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}
        }

        /* ── Mobile Admin PWA feel ── */
        @media(max-width:480px){
            .stats-grid{grid-template-columns:1fr 1fr}
            .topbar{padding:0 14px}
            .card-body{padding:14px}
            .btn{padding:8px 12px;font-size:12px}
        }
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sb-logo">
        <div class="wordmark">⚡ EventBomb</div>
        <div class="tagline">Admin Console v2</div>
    </div>

    <nav class="sb-scroll">
        <div class="sb-label">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="ni-icon">◈</i> Dashboard
        </a>
        <a href="{{ route('admin.events.index') }}" class="nav-item {{ request()->routeIs('admin.events*') ? 'active' : '' }}">
            <i class="ni-icon">◉</i> Events
        </a>

        @php $activeEvent = \App\Models\Event::where('is_active',true)->first(); @endphp
        @if($activeEvent)
        <div class="sb-label">Live: {{ Str::limit($activeEvent->name, 18) }}</div>

        <a href="{{ route('admin.fotos.index', $activeEvent) }}" class="nav-item {{ request()->routeIs('admin.fotos*') ? 'active' : '' }}">
            <i class="ni-icon">📷</i> Foto Queue
            @php $pend = $activeEvent->getPendingFotosCount() @endphp
            @if($pend > 0)<span class="nav-badge">{{ $pend }}</span>@endif
        </a>
        <a href="{{ route('admin.lottery.index', $activeEvent) }}" class="nav-item {{ request()->routeIs('admin.lottery*') ? 'active' : '' }}">
            <i class="ni-icon">🎰</i> Lottery
        </a>
        <a href="{{ route('admin.voting.index', $activeEvent) }}" class="nav-item {{ request()->routeIs('admin.voting*') ? 'active' : '' }}">
            <i class="ni-icon">🏆</i> Voting
        </a>
        <a href="{{ route('admin.membership.index', $activeEvent) }}" class="nav-item {{ request()->routeIs('admin.membership*') ? 'active' : '' }}">
            <i class="ni-icon">⭐</i> Members
        </a>

        <div class="sb-label">Screens</div>
        <a href="{{ route('vidiwall.show', $activeEvent->slug) }}" target="_blank" class="nav-item">
            <i class="ni-icon">📺</i> Vidiwall ↗
        </a>
        <a href="{{ route('event.landing', $activeEvent->slug) }}" target="_blank" class="nav-item">
            <i class="ni-icon">📱</i> Guest Page ↗
        </a>
        @endif

        <div class="sb-label">Admin</div>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
            <i class="ni-icon">👥</i> Users
        </a>
        @endif
        <a href="{{ route('admin.settings.index') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
            <i class="ni-icon">⚙</i> Settings
        </a>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="nav-item" style="width:100%">
                <i class="ni-icon">⎋</i> Logout
            </button>
        </form>
    </nav>

    <div class="sb-footer">
        <div class="sb-user">
            <img src="{{ auth()->user()->avatar_url }}" class="sb-avatar" alt="">
            <div class="sb-user-info">
                <div class="sb-name">{{ auth()->user()->name }}</div>
                <div class="sb-role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
        </div>
    </div>
</aside>

<!-- Main -->
<div class="main-wrap">
    <header class="topbar">
        <button class="tb-hamburger" onclick="openSidebar()">☰</button>
        <div class="tb-title">@yield('page-title','Dashboard')</div>
        <div class="tb-actions">
            @if($activeEvent ?? null)
                <span class="badge badge-active" style="display:none" id="liveBadge">● LIVE</span>
            @endif
            @yield('topbar-actions')
            <button class="notif-btn" title="Notifications">
                🔔
                @if(($pendingCount ?? 0) > 0)<span class="notif-dot"></span>@endif
            </button>
        </div>
    </header>

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">✕ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <div>@foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sbOverlay').classList.add('open'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sbOverlay').classList.remove('open'); }

// Show LIVE badge after a moment
setTimeout(() => { document.getElementById('liveBadge')?.style.removeProperty('display'); }, 500);

// Color picker sync
document.querySelectorAll('input[type=color]').forEach(picker => {
    const target = document.getElementById(picker.dataset.target);
    if (target) {
        picker.addEventListener('input', e => { target.value = e.target.value; });
        target?.addEventListener('input', e => { if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) picker.value = e.target.value; });
    }
});

// Module toggle via fetch
function toggleModule(module, el, eventId) {
    fetch(`/admin/events/${eventId}/toggle-module`, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
        body:JSON.stringify({module})
    }).then(r=>r.json()).then(d=>{ el.checked=d.enabled; }).catch(()=>{ el.checked=!el.checked; });
}
</script>
@livewireScripts
@stack('scripts')
</body>
</html>
