<!DOCTYPE html>
<html lang="en" style="overflow-x: hidden;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — NITA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style id="nita-layout">
        #sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            z-index: 50;
            width: 220px;
            height: calc(100vh - 56px);
            background: white;
            border-right: 1px solid rgba(28,25,23,.12);
            overflow-y: auto;
        }
        #main-content {
            padding: 24px 32px;
            min-width: 0;
            margin-left: 220px;
            overflow-x: hidden;
        }
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 49;
            background: rgba(0,0,0,0.4);
        }
        #mobile-menu-btn {
            display: none;
        }
        @media (max-width: 1023px) {
            #sidebar {
                left: 0;
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }
            #sidebar.sidebar-open {
                transform: translateX(0) !important;
            }
            #main-content {
                margin-left: 0;
                padding: 16px;
                overflow-x: hidden;
            }
            #mobile-menu-btn {
                display: flex;
            }
        }
    </style>
</head>
<body class="font-sans bg-[#F8F6F3] text-[#1C1917] min-h-screen antialiased" style="overflow-x: hidden;">

<nav style="position: sticky; top: 0; z-index: 60; background: rgba(248,246,243,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(28,25,23,.12);">
    <div style="max-width: 1600px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; height: 56px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button id="mobile-menu-btn" style="width: 36px; height: 36px; border-radius: 8px; border: none; background: none; cursor: pointer; align-items: center; justify-content: center;" onclick="toggleSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="{{ url('/dashboard') }}" style="flex-shrink: 0; text-decoration: none;">
                <img src="{{ asset('images/logo.svg') }}" alt="NITA" style="height: 30px;" loading="eager">
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ url('/alerts') }}" style="width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: inherit;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </a>
            <a href="{{ url('/alerts') }}" style="width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: inherit;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
            </a>
            <div style="width: 1px; height: 20px; background: rgba(28,25,23,.12); margin: 0 4px;"></div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #B45353; color: white; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            </div>
        </div>
    </div>
</nav>

<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<aside id="sidebar">
    @include('partials._sidebar')
</aside>

<main id="main-content">
    @yield('content')
</main>

@include('partials._settings-drawer')

<script>
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('sidebar-open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function openSidebar() {
    document.getElementById('sidebar').classList.add('sidebar-open');
    document.getElementById('sidebar-overlay').style.display = 'block';
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('sidebar-open');
    document.getElementById('sidebar-overlay').style.display = 'none';
}

document.querySelectorAll('#sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
});
</script>
</body>
</html>
