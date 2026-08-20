<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($pageTitle ?? 'Employee Self-Service') }} — TripWise TNVS</title>

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
                        white: '#faf9f6',
                        gray: {
                            50: '#f1efe9',
                            100: '#e7e4dc',
                            200: '#dad5c9',
                        },
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
            background-color: #f1efe9;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1efe9; }
        ::-webkit-scrollbar-thumb { background: #F44336; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #D32F2F; }
    </style>

    @stack('styles')
</head>
<body class="antialiased text-gray-800 min-h-screen flex flex-col">

    <!-- Dedicated ESS Top Navigation Bar (Zero Admin Sidebar) -->
    <header class="bg-forest text-white border-b border-white/10 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Logo & Brand Header -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <div class="w-9 h-9 overflow-hidden rounded-xl border border-[#F44336]/40 bg-white flex-shrink-0 flex items-center justify-center p-0.5 shadow-xs">
                            <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise" class="w-full h-full object-contain">
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-lg font-extrabold font-outfit text-white tracking-tight">
                                TripWise<span class="text-brand">.</span>
                            </span>
                            <span class="block text-[10px] text-gray-400 font-mono tracking-wider">TNVS ESS Portal</span>
                        </div>
                    </a>

                    <div class="h-6 w-px bg-white/15 hidden sm:block"></div>

                    <!-- ESS Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand/20 border border-brand/40 text-white font-bold text-xs font-outfit">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Employee Self-Service (ESS)
                    </span>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    
                    <!-- Back to Main Switchboard -->
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs transition-all border border-white/10 shadow-2xs">
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="hidden md:inline">Main Portals</span>
                    </a>

                    <!-- HR Admin Console Link -->
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand hover:bg-brand-dark text-white font-bold text-xs transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="hidden sm:inline">HR Admin Console</span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-forest text-gray-400 py-6 border-t border-white/10 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-bold text-white font-outfit">TripWise TNVS</span>
                <span>• Employee Self-Service (ESS) Subsystem</span>
            </div>
            <div class="text-[11px] text-gray-500 font-mono">
                Team 4: Payroll & Statutory Benefits Management
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
