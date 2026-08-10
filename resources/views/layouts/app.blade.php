<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($pageTitle ?? 'Dashboard') }} — TripWise TNVS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#F44336',
                            dark: '#D32F2F',
                            light: '#EF5350',
                            soft: '#fff5f5',
                        },
                        forest: {
                            DEFAULT: '#1c1c1e',
                            dark: '#111112',
                            light: '#2c2c2e',
                            soft: '#fff5f5',
                        },
                        gold: {
                            DEFAULT: '#F44336',
                            dark: '#D32F2F',
                            light: '#EF5350',
                            soft: '#fff5f5',
                        },
                        cream: {
                            DEFAULT: '#faf9f6',
                            dark: '#f1efe9',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f8f9fa;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Default desktop layout — headbar and content positioned after sidebar */
        #navigia-headbar { left: 16rem; transition: left 0.3s ease; }
        #main-content   { margin-left: 16rem; transition: margin-left 0.3s ease; }

        /* Sidebar collapsed state */
        body.sidebar-collapsed #navigia-sidebar { width: 4.5rem; }
        body.sidebar-collapsed #navigia-sidebar .sidebar-text { display: none; }
        body.sidebar-collapsed #navigia-sidebar .sidebar-group > div { display: none !important; }
        body.sidebar-collapsed #navigia-headbar { left: 4.5rem; }
        body.sidebar-collapsed #main-content { margin-left: 4.5rem; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1efe9; }
        ::-webkit-scrollbar-thumb { background: #F44336; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #D32F2F; }

        /* Mobile: sidebar hidden off-screen by default */
        @media (max-width: 1023px) {
            #navigia-sidebar { transform: translateX(-100%); }
            #navigia-sidebar.mobile-open { transform: translateX(0); }
            #navigia-headbar { left: 0 !important; transition: none; }
            #main-content { margin-left: 0 !important; transition: none; }
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased text-gray-800">

    {{-- ========= Sidebar Partial ========= --}}
    @include('partials.sidebar')

    {{-- ========= Headbar Partial ========= --}}
    @include('partials.headbar')

    {{-- ========= Main Content Area ========= --}}
    <main id="main-content" class="pt-16 min-h-screen transition-all duration-300 bg-gray-50">
        <div class="p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>

    <!-- Shared JavaScript for sidebar & headbar interactions -->
    <script>
        // ---- Sidebar group toggle ----
        function toggleGroup(groupId) {
            const content = document.getElementById(groupId);
            const arrow = document.getElementById(groupId + '-arrow');
            content.classList.toggle('hidden');
            if (arrow) arrow.classList.toggle('rotate-180');
        }

        // ---- Mobile Sidebar ----
        function openMobileSidebar() {
            document.getElementById('navigia-sidebar').classList.add('mobile-open');
            document.getElementById('sidebar-overlay').classList.remove('hidden');
        }
        function closeMobileSidebar() {
            document.getElementById('navigia-sidebar').classList.remove('mobile-open');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }

        // ---- Desktop sidebar collapse ----
        const desktopCollapseBtn = document.getElementById('desktop-collapse-btn');
        if (desktopCollapseBtn) {
            desktopCollapseBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
            });
        }

        // ---- User menu dropdown ----
        function toggleUserMenu() {
            document.getElementById('user-menu').classList.toggle('hidden');
        }
        document.addEventListener('click', (e) => {
            const userMenu = document.getElementById('user-menu');
            const userBtn = document.getElementById('user-menu-btn');
            if (userMenu && userBtn && !userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
