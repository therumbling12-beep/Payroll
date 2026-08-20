<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>TripWise - Ride Smart, Arrive Wise.</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
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
                            neutral: {
                                dark: '#1c1c1e',
                                gray: '#2c2c2e',
                                light: '#f2f2f7',
                                soft: '#faf9f6',
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
            body {
                background-color: #faf9f6;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .font-outfit {
                font-family: 'Outfit', sans-serif;
            }
            /* Custom Scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }
            ::-webkit-scrollbar-track {
                background: #f1efe9;
            }
            ::-webkit-scrollbar-thumb {
                background: #F44336;
                border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: #D32F2F;
            }
        </style>
    </head>
    <body class="antialiased text-gray-900 bg-cream pt-20">

        <!-- Header / Navigation -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-forest border-b border-brand/20 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Left Side: Logo, Brand & Dropdown besides each other -->
                    <div class="flex items-center gap-4 sm:gap-6">
                        <!-- Mobile Hamburger Button -->
                        <button id="mobile-landing-menu-btn" class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/5 border border-white/10 text-white hover:text-brand hover:bg-white/10 transition-all md:hidden" title="Open Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <!-- Logo and Brand -->
                        <a href="{{ url('/') }}" class="flex items-center gap-3">
                            <div class="w-10 h-10 overflow-hidden rounded-xl border border-brand/35 bg-white flex-shrink-0 p-0.5 flex items-center justify-center">
                                <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise" class="w-full h-full object-contain">
                            </div>
                            <span class="text-xl font-extrabold font-outfit tracking-tight text-white">
                                TripWise<span class="text-brand">.</span>
                            </span>
                        </a>

                        <!-- Subsystems Dropdown Button (besides logo) - Hidden on Mobile, Shown on Desktop -->
                        <div class="relative hidden md:block">
                            <button id="dropdown-btn" class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gold hover:bg-gold-dark text-forest font-bold text-xs tracking-wide shadow-md transition-all duration-200 focus:outline-none">
                                <span>TNVS Systems</span>
                                <svg class="w-3.5 h-3.5 transition-transform duration-200" id="dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Megamenu Dropdown -->
                            <div id="dropdown-menu" class="absolute left-0 mt-4 w-[560px] bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50">
                                <div class="pb-3 border-b border-gray-100 flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="font-outfit font-bold text-forest text-base">TripWise TNVS Ecosystem</h4>
                                        <p class="text-[10px] text-gray-500">Configure modules for operations</p>
                                    </div>
                                    <span class="text-[10px] font-semibold text-brand bg-brand/10 px-2 py-0.5 rounded-full">10 Core Systems</span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 max-h-[380px] overflow-y-auto pr-1">
                                    <!-- Item 1 -->
                                    <a href="#hrms" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Recruitment & Core HR</h5>
                                            <p class="text-[10px] text-gray-500">Applicant Tracking & Core HCM</p>
                                        </div>
                                    </a>
                                    <!-- Item 2 -->
                                    <a href="#workforce" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Workforce Management</h5>
                                            <p class="text-[10px] text-gray-500">Attendance, Roster & Leaves</p>
                                        </div>
                                    </a>
                                    <!-- Item 3 -->
                                    <a href="#performance" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Performance & Dev</h5>
                                            <p class="text-[10px] text-gray-500">Competency & Training Paths</p>
                                        </div>
                                    </a>
                                    <!-- Item 4 -->
                                    <a href="{{ route('ess.dashboard') }}" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Payroll & Benefits (ESS)</h5>
                                            <p class="text-[10px] text-gray-500">Employee Self-Service Portal</p>
                                        </div>
                                    </a>
                                    <!-- Item 5 -->
                                    <a href="#finance" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Financial Management</h5>
                                            <p class="text-[10px] text-gray-500">Ledger, Billing, AP/AR & Taxes</p>
                                        </div>
                                    </a>
                                    <!-- Item 6 -->
                                    <a href="#supply-chain" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Supply Chain & Inventory</h5>
                                            <p class="text-[10px] text-gray-500">Warehouses, Procurement</p>
                                        </div>
                                    </a>
                                    <!-- Item 7 -->
                                    <a href="#fleet" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Fleet & Transportation</h5>
                                            <p class="text-[10px] text-gray-500">Vehicles Reserves & Fuel Logs</p>
                                        </div>
                                    </a>
                                    <!-- Item 8 -->
                                    <a href="#facilities" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">Facilities & Admin</h5>
                                            <p class="text-[10px] text-gray-500">Visitor Logs & Office Booking</p>
                                        </div>
                                    </a>
                                    <!-- Item 9 -->
                                    <a href="#operations" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">TNVS Operations & Drivers</h5>
                                            <p class="text-[10px] text-gray-500">Dispatch, Driver Wallets & Logs</p>
                                        </div>
                                    </a>
                                    <!-- Item 10 -->
                                    <a href="#booking" class="flex gap-2.5 p-2 rounded-lg hover:bg-forest-soft transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center text-forest group-hover:bg-forest group-hover:text-white transition-colors flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-900 font-outfit">TNVS Booking, Payments & CX</h5>
                                            <p class="text-[10px] text-gray-500">Bookings, CRM & Fare collection</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                        <div class="hidden md:block">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="text-sm font-bold text-white hover:text-gold transition-colors">Dashboard</a>
                                @else
                                    <button onclick="openGatewayModal('employee')" class="px-4 py-2 rounded-xl bg-gold hover:bg-gold-dark text-forest font-bold text-xs tracking-wide shadow-md transition-all duration-200">
                                        Employee Portal
                                    </button>
                                @endauth
                            @else
                                <button onclick="openGatewayModal('employee')" class="px-4 py-2 rounded-xl bg-gold hover:bg-gold-dark text-forest font-bold text-xs tracking-wide shadow-md transition-all duration-200">
                                    Employee Portal
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Drawer Menu (Hidden by default) -->
        <div id="mobile-landing-drawer" class="fixed inset-0 z-[100] md:hidden invisible opacity-0 transition-all duration-300">
            <!-- Overlay background -->
            <div id="mobile-drawer-overlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            
            <!-- Drawer container -->
            <div class="absolute top-0 left-0 bottom-0 w-80 max-w-[90%] bg-forest flex flex-col shadow-2xl border-r border-brand/20">
                <div class="flex items-center justify-between px-6 py-5 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 overflow-hidden rounded-xl border border-brand/35 bg-white p-0.5 flex items-center justify-center">
                            <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise" class="w-full h-full object-contain">
                        </div>
                        <span class="text-lg font-extrabold font-outfit text-white">TripWise<span class="text-brand">.</span></span>
                    </div>
                    <button id="mobile-landing-drawer-close" class="text-white/60 hover:text-white p-1" title="Close Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation links -->
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div class="space-y-3">
                        <h4 class="text-[10px] font-bold text-brand uppercase tracking-widest">Main Links</h4>
                        <a href="#showcase" class="mobile-drawer-link block text-sm font-bold text-white/80 hover:text-white py-1">Showcase</a>
                        <a href="#about" class="mobile-drawer-link block text-sm font-bold text-white/80 hover:text-white py-1">About</a>
                    </div>
                    
                    <div class="space-y-3 pt-6 border-t border-white/10">
                        <h4 class="text-[10px] font-bold text-brand uppercase tracking-widest">Ecosystem Sections</h4>
                        <a href="#hrms" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">HRMS & Payroll</a>
                        <a href="#finance" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">Financial Management</a>
                        <a href="#supply-chain" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">Supply Chain & Inventory</a>
                        <a href="#fleet" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">Fleet Management</a>
                        <a href="#facilities" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">Facilities & Admin</a>
                        <a href="#operations" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">TNVS Operations</a>
                        <a href="#booking" class="mobile-drawer-link block text-xs font-semibold text-white/70 hover:text-white py-1">Booking & Customer Experience</a>
                    </div>
                </div>

                <!-- Auth/Bottom area in drawer -->
                <div class="p-6 border-t border-white/10 bg-black/10">
                    <button onclick="openGatewayModal('employee')" class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl bg-gold hover:bg-gold-dark text-forest font-bold text-xs shadow-md transition-all">Employee Portal</button>
                </div>
            </div>
        </div>

        <!-- Universal Portal Gateway Modal (Employee / Passenger Switchboard) -->
        <div id="gateway-selection-modal" class="fixed inset-0 z-[150] invisible opacity-0 transition-all duration-300 flex items-center justify-center p-4">
            <!-- Blur Overlay background -->
            <div onclick="closeGatewayModal()" class="absolute inset-0 bg-gray-950/80 backdrop-blur-md"></div>
            
            <!-- Modal Body -->
            <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl border border-gray-100 flex flex-col max-h-[90vh] overflow-hidden relative z-10 transition-transform duration-300 scale-95" id="gateway-modal-body">
                
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h3 class="font-outfit font-extrabold text-gray-900 text-lg sm:text-xl">TripWise System Gateway</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Select a capstone system module below to redirect to its portal login</p>
                    </div>
                    <button onclick="closeGatewayModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Gateway Cards Grid -->
                <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Team 1: Recruitment Onboarding & Core HR -->
                    <a href="{{ env('TEAM1_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 1</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Recruitment Onboarding & Core HR</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Applicant management, recruitment boards, employee profile hubs, and self-service files.</p>
                        </div>
                    </a>

                    <!-- Team 2: Workforce Management -->
                    <a href="{{ env('TEAM2_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 2</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Workforce Management</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Time & attendance logs, shifts calendar scheduling, timesheets, and leave records.</p>
                        </div>
                    </a>

                    <!-- Team 3: Performance & Development -->
                    <a href="{{ env('TEAM3_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 3</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Performance & Development</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Competency review metrics, learning/training course logs, and succession schedules.</p>
                        </div>
                    </a>

                    <!-- Team 4: Payroll & Benefits (Employee Self-Service) -->
                    <a href="{{ route('ess.dashboard') }}" class="flex items-start gap-4 p-4 rounded-2xl border border-brand/30 hover:border-brand/60 hover:shadow-md transition-all group bg-brand/5 relative overflow-hidden">
                        <div class="w-10 h-10 rounded-xl bg-brand text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold text-white uppercase tracking-wider bg-brand px-2 py-0.5 rounded">Team 4</span>
                                <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded-full">Active Self-Service</span>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Payroll & Benefits Self-Service (ESS)</h4>
                            <p class="text-[11px] text-gray-600 mt-0.5 leading-relaxed">Access your interactive payslips, APE schedule, Group Life coverage, and benefit claims.</p>
                        </div>
                    </a>

                    <!-- Team 5: Financial Management System -->
                    <a href="{{ env('TEAM5_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 5</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Financial Management System</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">General ledger ledgers, account disbursements, billing entries (AR/AP), and tax filings.</p>
                        </div>
                    </a>

                    <!-- Team 6: Supply Chain & Inventory Management -->
                    <a href="{{ env('TEAM6_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 6</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Supply Chain & Inventory Management</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Warehousing logistics audits, procurement sources, supplier listings, and tracking logs.</p>
                        </div>
                    </a>

                    <!-- Team 7: Fleet & Transportation Management -->
                    <a href="{{ env('TEAM7_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 7</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Fleet & Transportation Management</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Vehicle reservations systems, fuel allocations, route optimization, and TCAO reports.</p>
                        </div>
                    </a>

                    <!-- Team 8: Facilities & Administrative Management -->
                    <a href="{{ env('TEAM8_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 8</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">Facilities & Administrative Management</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Facilities office booking, visitor registration, legal contracts, and archives indexes.</p>
                        </div>
                    </a>

                    <!-- Team 9: TNVS Operations & Driver Management -->
                    <a href="{{ env('TEAM9_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-brand/35 hover:shadow-md transition-all group bg-gray-50/50">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded">Team 9</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">TNVS Operations & Driver Management</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Dispatch queues console, driver performance, driver wallet payouts, and supplies management.</p>
                        </div>
                    </a>

                    <!-- Team 10: TNVS Booking, Payments & Customer Experience -->
                    <a href="{{ env('TEAM10_URL', '#') }}" target="_blank" class="flex items-start gap-4 p-4 rounded-2xl border border-brand/30 hover:border-brand/35 hover:shadow-md transition-all group bg-brand/5">
                        <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center flex-shrink-0 group-hover:bg-brand group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-brand uppercase tracking-wider bg-brand/10 px-2 py-0.5 rounded">Team 10</span>
                            <h4 class="text-sm font-bold text-gray-900 font-outfit mt-1 group-hover:text-brand transition-colors">TNVS Booking, Payments & CX</h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">Active ride requests, fare collections ledger, CRM dashboard, and passenger tracking maps.</p>
                        </div>
                    </a>

                </div>
            </div>
        </div>

        <script>
            // Modal display triggers
            function openGatewayModal(type) {
                const modal = document.getElementById('gateway-selection-modal');
                const modalBody = document.getElementById('gateway-modal-body');
                
                modal.classList.remove('invisible', 'opacity-0');
                setTimeout(() => {
                    modalBody.classList.remove('scale-95');
                    modalBody.classList.add('scale-100');
                }, 50);
            }

            function closeGatewayModal() {
                const modal = document.getElementById('gateway-selection-modal');
                const modalBody = document.getElementById('gateway-modal-body');
                
                modalBody.classList.remove('scale-100');
                modalBody.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('invisible', 'opacity-0');
                }, 200);
            }

            // Mobile Menu Logic
            const menuBtn = document.getElementById('mobile-landing-menu-btn');
            const drawer = document.getElementById('mobile-landing-drawer');
            const closeBtn = document.getElementById('mobile-landing-drawer-close');
            const overlay = document.getElementById('mobile-drawer-overlay');
            const drawerLinks = document.querySelectorAll('.mobile-drawer-link');

            const toggleDrawer = (show) => {
                if (show) {
                    drawer.classList.remove('invisible', 'opacity-0');
                } else {
                    drawer.classList.add('invisible', 'opacity-0');
                }
            };

            if (menuBtn) menuBtn.addEventListener('click', () => toggleDrawer(true));
            if (closeBtn) closeBtn.addEventListener('click', () => toggleDrawer(false));
            if (overlay) overlay.addEventListener('click', () => toggleDrawer(false));
            drawerLinks.forEach(link => link.addEventListener('click', () => toggleDrawer(false)));

            // Dropdown Logic (TNVS Systems)
            const dropdownBtn = document.getElementById('dropdown-btn');
            const dropdownMenu = document.getElementById('dropdown-menu');
            const dropdownArrow = document.getElementById('dropdown-arrow');

            if(dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !dropdownMenu.classList.contains('opacity-0');
                    if (!isOpen) {
                        dropdownMenu.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
                        dropdownMenu.classList.add('opacity-100', 'translate-y-0');
                        if (dropdownArrow) dropdownArrow.classList.add('rotate-180');
                    } else {
                        dropdownMenu.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
                        dropdownMenu.classList.remove('opacity-100', 'translate-y-0');
                        if (dropdownArrow) dropdownArrow.classList.remove('rotate-180');
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!dropdownMenu.contains(e.target) && e.target !== dropdownBtn) {
                        dropdownMenu.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
                        dropdownMenu.classList.remove('opacity-100', 'translate-y-0');
                        if (dropdownArrow) dropdownArrow.classList.remove('rotate-180');
                    }
                });
            }
        </script>

        <!-- Hero Section -->
        <section class="py-12 md:py-24 overflow-hidden relative bg-cream">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-6 space-y-6 text-center lg:text-left">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-forest/10 border border-forest/20 text-xs font-semibold text-forest tracking-wide">
                            <span class="w-2 h-2 rounded-full bg-gold animate-ping"></span>
                            Unified TNVS Platform
                        </span>
                        
                        <h1 class="text-4xl sm:text-5xl font-extrabold font-outfit tracking-tight text-forest leading-tight">
                            Manage operations, bookings & drivers in <span class="text-gold">one place.</span>
                        </h1>
                        
                        <p class="text-sm sm:text-base text-gray-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            TripWise provides a high-performance operating ecosystem tailored for modern transportation networks, integrating finance, fleet operations, supply chain, and employee capital.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a href="#showcase" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl bg-forest hover:bg-forest-light text-white font-bold text-sm shadow-md transition-all">
                                Explore Modules
                            </a>
                            <a href="#about" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-gray-300 hover:border-forest text-forest font-bold text-sm bg-transparent transition-all">
                                Learn More
                            </a>
                        </div>
                    </div>

                    <!-- Right Hero Content (Interactive Mockup Widget) -->
                    <div class="lg:col-span-6 flex justify-center lg:justify-end">
                        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 p-6 relative overflow-hidden">
                            <!-- Header Grid -->
                            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gold/15 flex items-center justify-center text-gold">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-bold text-gray-900 font-outfit">Operations Hub</h3>
                                        <p class="text-[10px] text-gray-500">Live TNVS Performance</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">System Live</span>
                            </div>

                            <!-- Live Mock Statistics -->
                            <div class="grid grid-cols-2 gap-4 py-4">
                                <div class="p-3 bg-forest-soft rounded-xl">
                                    <span class="text-[10px] font-bold text-forest/70 uppercase">Active Bookings</span>
                                    <span class="block text-xl font-extrabold text-forest font-outfit mt-0.5">1,482</span>
                                </div>
                                <div class="p-3 bg-gold-soft rounded-xl">
                                    <span class="text-[10px] font-bold text-gold-dark uppercase">Available Fleet</span>
                                    <span class="block text-xl font-extrabold text-gold font-outfit mt-0.5">94.8%</span>
                                </div>
                            </div>

                            <!-- Dispatch list -->
                            <div class="space-y-3">
                                <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-forest flex items-center justify-center text-white text-xs font-bold">N1</div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900">Trip #29384 - Active</p>
                                            <p class="text-[10px] text-gray-500">Driver Wallet: $382.10</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-forest bg-forest/10 px-2 py-0.5 rounded-full">Route Optimized</span>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-white text-xs font-bold">N2</div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900">Dispatch #29385 - Pending</p>
                                            <p class="text-[10px] text-gray-500">Fleet Code: FL-8321</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-gold-dark bg-gold/10 px-2 py-0.5 rounded-full">Awaiting Driver</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- TNVS Photo Highlight Strip -->
        <section class="py-0 bg-forest overflow-hidden">
            <div class="grid grid-cols-3 h-48 sm:h-64">
                <!-- Fleet Image -->
                <div class="relative overflow-hidden col-span-2">
                    <img src="{{ asset('tnvs_fleet.png') }}" alt="TNVS Fleet" class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity duration-500 hover:scale-105 transform transition-transform">
                    <div class="absolute inset-0 bg-gradient-to-r from-forest/70 to-transparent"></div>
                    <div class="absolute bottom-4 left-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-gold">Our Fleet</span>
                        <p class="text-white font-outfit font-bold text-base sm:text-lg leading-tight">Modern TNVS Vehicles<br>Ready to Dispatch</p>
                    </div>
                </div>
                <!-- Driver Image -->
                <div class="relative overflow-hidden">
                    <img src="{{ asset('tnvs_driver.png') }}" alt="Professional Driver" class="w-full h-full object-cover object-top opacity-80 hover:opacity-100 transition-opacity duration-500 hover:scale-105 transform transition-transform">
                    <div class="absolute inset-0 bg-gradient-to-t from-forest/80 to-transparent"></div>
                    <div class="absolute bottom-4 left-3 right-3">
                        <p class="text-white font-outfit font-bold text-xs sm:text-sm leading-tight">Verified Drivers</p>
                        <p class="text-gold text-[10px] font-semibold">Professional & Safe</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ecosystem Showcasing Grid Section -->
        <section id="showcase" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-12 space-y-3">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand">TripWise Core Systems</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold font-outfit text-forest">
                        The Comprehensive TNVS Enterprise Ecosystem
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">
                        A seamless architecture unifying human capital, accounting, dispatching, route tracking, logistics, and facilities into a single high-performance command center.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- 1. Recruitment Onboarding & Core HR -->
                    <div id="hrms" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 1</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Recruitment Onboarding & Core HR</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Applicant management pipelines, recruiter modules, new hire documentation onboarding, and employee self-service records.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Applicant Management</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Core HCM</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Self-Service Portal</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 2. Workforce Management -->
                    <div id="workforce" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 2</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Workforce Management</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Comprehensive time and attendance tracking, shift scheduling, roster builders, leaf management pipelines, and workforce analytics.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Time & Attendance</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Shifts Scheduler</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Workforce Analytics</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 3. Performance & Development -->
                    <div id="performance" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 3</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Performance & Development</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Continuous performance monitoring, core competencies analysis, employee learning modules, training management, and succession planning.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Competency Reviews</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Learning Management</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Succession Path</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 4. Payroll & Benefits -->
                    <div id="payroll" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 4</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Payroll & Benefits</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Automated payroll calculations, statutory benefits administration, compliance records, claims logs, and employee compensation files.
                        </p>
                        <div class="mt-auto space-y-2.5 pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Payroll Calculation</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Claims Tracking</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Statutory Benefits</li>
                            </ul>
                            <div class="pt-1">
                                <a href="{{ route('ess.dashboard') }}" class="block w-full text-center py-2 px-3 bg-brand hover:bg-brand-dark text-white font-bold text-[11px] rounded-lg transition-all shadow-xs">
                                    Launch Employee Self-Service (ESS)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Financial Management System -->
                    <div id="finance" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 5</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Financial Management System</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Audit-compliant transaction core covering general ledger, automated collections, disbursements, and real-time tax accounting.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">General Ledger</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Accounts Payable</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Taxation Core</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 6. Supply Chain & Inventory Management -->
                    <div id="supply-chain" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 6</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Supply Chain & Inventory</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Keep track of vehicle parts, storage supplies, procurement pipelines, and document logs across dynamic warehouses.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Smart Warehousing</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Procurement</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Supplier Info</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 7. Fleet & Transportation Management -->
                    <div id="fleet" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 7</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Fleet & Transportation Management</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Proactive GPS tracking, dispatch lists, real-time fuel efficiency optimization, and route tracking solutions.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Vehicle Reservation</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Fuel Auditing</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Cost Analytics</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 8. Facilities & Administrative Management -->
                    <div id="facilities" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 8</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">Facilities & Administrative Management</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Organize workspace reservations, visitor security passes, and archival compliance for active contracts.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Office Space</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Visitor Registry</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Contracts</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 9. TNVS Operations & Driver Management -->
                    <div id="operations" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 9</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">TNVS Operations & Driver Management</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Control trip dispatches, balance driver wallets, track mobile logs, and audit consumables on-the-go.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Trip Dispatch</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Driver Wallets</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Mobile Tracking</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 10. TNVS Booking, Payments & Customer Experience -->
                    <div id="booking" class="bg-cream-soft rounded-xl border border-gray-100 p-5 hover:shadow-lg transition-all flex flex-col group scroll-mt-24 md:col-span-2 lg:col-span-1">
                        <div class="w-10 h-10 rounded-lg bg-forest/10 flex items-center justify-center text-forest mb-4 group-hover:bg-forest group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Team 10</span>
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1.5">TNVS Booking, Payments & CX</h3>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Web & Mobile Booking interfaces, passenger CRM tools, fare collection nodes, and deep metrics dashboards.
                        </p>
                        <div class="mt-auto pt-3 border-t border-gray-200/50">
                            <ul class="flex flex-wrap gap-1">
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Mobile Booking</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">Payments & CRM</li>
                                <li class="text-[9px] font-medium text-forest bg-forest/5 px-2 py-0.5 rounded">KPI Dashboard</li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Operations Dashboard Spotlight -->
        <section class="py-16 bg-cream-dark overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

                    <!-- Image Left -->
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl group">
                        <img src="{{ asset('tnvs_ops.png') }}" alt="TNVS Operations Control Room" class="w-full h-72 sm:h-80 object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-forest/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-xs font-bold text-white">Live Operations Center</span>
                        </div>
                    </div>

                    <!-- Text Right -->
                    <div class="space-y-5">
                        <span class="text-xs font-bold uppercase tracking-widest text-gold">Command & Control</span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold font-outfit text-forest leading-tight">
                            Real-time oversight of every<br>trip, driver & vehicle
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            TripWise's Operations Hub gives fleet managers a full 360° view of all active trips, live GPS routes, driver statuses, and dispatch queues — all from a single dashboard.
                        </p>
                        <ul class="space-y-2.5">
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                                <span class="text-xs text-gray-700 leading-snug">Live GPS tracking with playback for every completed trip</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                                <span class="text-xs text-gray-700 leading-snug">Automated dispatch routing with real-time fleet availability</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                                <span class="text-xs text-gray-700 leading-snug">Instant SOP compliance monitoring and audit logs</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                                <span class="text-xs text-gray-700 leading-snug">KPI analytics dashboards with Transport Cost Analysis</span>
                            </li>
                        </ul>
                        <a href="#showcase" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-forest hover:bg-forest-light text-white font-bold text-xs transition-all shadow-md">
                            Explore Subsystems
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-16 bg-forest text-cream-dark relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <div class="lg:col-span-5 space-y-6">
                        <span class="text-xs font-bold uppercase tracking-widest text-brand">About TripWise</span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold font-outfit text-white tracking-tight leading-tight">
                            The future of fleet and transit coordination is here.
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">
                            TripWise streamlines the backend operations of Transportation Network Vehicle Services, integrating fragmented tools into one beautifully designed, lightning-fast dashboard.
                        </p>
                        <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">
                            From route planning and automated accounting reconciliation to driver shifts and customer experience pipelines, we provide the ultimate solution for fleet performance scaling.
                        </p>
                    </div>

                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                            <h4 class="text-xs font-bold text-white font-outfit mb-1">Automated Core Payroll</h4>
                            <p class="text-[11px] text-gray-400 leading-relaxed">Configure schedules, timesheet analytics, and claims payouts without leaving the dispatch interface.</p>
                        </div>
                        <div class="p-5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                            <h4 class="text-xs font-bold text-white font-outfit mb-1">Smart Inventory Audits</h4>
                            <p class="text-[11px] text-gray-400 leading-relaxed">Track components in real time. Maintain logistics compliance databases and warehouse stocks effortlessly.</p>
                        </div>
                        <div class="p-5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                            <h4 class="text-xs font-bold text-white font-outfit mb-1">Operational Scalability</h4>
                            <p class="text-[11px] text-gray-400 leading-relaxed">Manage visitor check-ins, office facilities, and trip dispatch requests under standard audit logs.</p>
                        </div>
                        <div class="p-5 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                            <h4 class="text-xs font-bold text-white font-outfit mb-1">Enterprise Integrations</h4>
                            <p class="text-[11px] text-gray-400 leading-relaxed">Eager loaded Eloquent database drivers and clean RESTful API modules keep synchronization instant.</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-950 text-gray-400 py-12 border-t border-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 overflow-hidden rounded border border-brand/30 bg-white flex-shrink-0 p-0.5 flex items-center justify-center">
                            <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="text-sm font-bold font-outfit text-white tracking-tight">TripWise</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-8 text-[11px] font-medium">
                        <a href="#" class="hover:text-brand transition-colors">Home</a>
                        <a href="#about" class="hover:text-brand transition-colors">About</a>
                        <a href="#showcase" class="hover:text-brand transition-colors">Subsystems</a>
                    </div>

                    <p class="text-[10px] text-gray-600">
                        &copy; {{ date('Y') }} TripWise. All rights reserved.
                    </p>

                </div>
            </div>
        </footer>

    </body>
</html>
