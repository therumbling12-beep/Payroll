{{--
|--------------------------------------------------------------------------
| Headbar Partial - TripWise TNVS
|--------------------------------------------------------------------------
| Usage: @include('partials.headbar')
| Requires: $pageTitle variable (string) from the parent view
--}}

<header class="fixed top-0 right-0 z-50" id="navigia-headbar"
    style="background: #ffffff; border-bottom: 1px solid #f1efe9;">

    <div class="flex items-center justify-between h-16 px-4 sm:px-6">

        <!-- Left: Mobile Menu Toggle + Page Title/Breadcrumb -->
        <div class="flex items-center gap-4">
            <!-- Mobile hamburger -->
            <button id="mobile-sidebar-btn" class="lg:hidden flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-[#F44336] hover:bg-red-50 transition-colors" onclick="openMobileSidebar()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Desktop sidebar collapse toggle -->
            <button id="desktop-collapse-btn" class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-[#F44336] hover:bg-red-50 transition-colors" title="Toggle sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Breadcrumb / Page Title -->
            <div class="hidden sm:flex items-center gap-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-400 hover:text-[#F44336] transition-colors text-xs">TripWise</a>
                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-xs font-semibold text-gray-700">{{ $pageTitle ?? 'Dashboard' }}</span>
            </div>
        </div>

        <!-- Right: Search, Notifications, Profile -->
        <div class="flex items-center gap-2 sm:gap-3">

            <!-- Search Bar -->
            <div class="hidden md:flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 w-52 focus-within:border-[#F44336]/50 focus-within:bg-white transition-all">
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" placeholder="Search modules..." class="bg-transparent text-xs text-gray-600 placeholder-gray-400 outline-none w-full">
            </div>

            <!-- Notifications Bell -->
            <button class="relative flex items-center justify-center w-8 h-8 rounded-xl bg-gray-50 hover:bg-red-50 border border-gray-200 text-gray-500 hover:text-[#F44336] transition-all" title="Notifications">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <!-- Badge -->
                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-[#F44336]"></span>
            </button>

            <!-- Settings -->
            <button class="flex items-center justify-center w-8 h-8 rounded-xl bg-gray-50 hover:bg-red-50 border border-gray-200 text-gray-500 hover:text-[#F44336] transition-all" title="Settings">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>

            <!-- Divider -->
            <div class="hidden sm:block w-px h-6 bg-gray-200"></div>

            <!-- User Profile -->
            <div class="relative">
                <button id="user-menu-btn" onclick="toggleUserMenu()" class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-all">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background-color:#F44336;">
                        A
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-xs font-bold text-gray-800 leading-none">Admin</p>
                        <p class="text-[10px] text-gray-400">Administrator</p>
                    </div>
                    <svg class="hidden sm:block w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div id="user-menu" class="hidden absolute right-0 mt-2 w-44 bg-white rounded-xl border border-gray-100 shadow-lg py-1 z-50">
                    <div class="px-3 py-2 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-900">Admin User</p>
                        <p class="text-[10px] text-gray-400">admin@tripwise.app</p>
                    </div>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Profile
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </a>
                    <div class="border-t border-gray-100 mt-1"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-500 hover:bg-red-50 transition-colors text-left cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</header>
