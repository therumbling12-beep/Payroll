{{--
|--------------------------------------------------------------------------
| Sidebar Partial - TripWise TNVS
|--------------------------------------------------------------------------
| Usage: @include('partials.sidebar')
| Requires: $currentPage variable (string) from the parent view
--}}

<aside id="navigia-sidebar" class="fixed top-0 left-0 h-full z-40 flex flex-col transition-all duration-300 w-64 overflow-x-hidden" style="background-color:#1c1c1e;">

    <!-- Sidebar Header: Logo & Brand -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-white/10 flex-shrink-0 overflow-hidden">
        <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 overflow-hidden rounded-xl border border-[#F44336]/40 bg-white flex-shrink-0 flex items-center justify-center p-0.5">
                <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise" class="w-full h-full object-contain">
            </div>
            <span class="sidebar-text text-lg font-extrabold text-white tracking-tight truncate" style="font-family:'Outfit',sans-serif;">
                TripWise<span style="color:#F44336;">.</span>
            </span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" id="sidebar-nav">

        <!-- Overview -->
        <a href="{{ url('/dashboard') }}"
           class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 group {{ (isset($currentPage) && $currentPage === 'dashboard') ? 'bg-[#F44336] text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="sidebar-text truncate">Dashboard</span>
        </a>



        <!-- Divider Label -->
        <div class="px-3 pt-4 pb-1">
            <span class="sidebar-text text-[10px] font-bold uppercase tracking-widest text-white/30">Payroll & Benefits System</span>
        </div>

        <!-- 1. Payroll Management -->
        <div class="sidebar-group">
            <button onclick="toggleGroup('payroll-group')"
                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white {{ (isset($currentPage) && str_starts_with($currentPage, 'payroll')) ? 'bg-white/10 text-white' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="sidebar-text flex-1 text-left truncate">Payroll Management</span>
                <svg class="sidebar-text w-3 h-3 flex-shrink-0 transition-transform duration-200 {{ (isset($currentPage) && str_starts_with($currentPage, 'payroll')) ? 'rotate-180' : '' }}" id="payroll-group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="payroll-group" class="{{ (isset($currentPage) && str_starts_with($currentPage, 'payroll')) ? '' : 'hidden' }} ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                <a href="{{ route('payroll.salary-computation') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.salary-computation') ? 'text-white font-bold' : '' }}">Salary Computation</a>
                <a href="{{ route('payroll.loans') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.loans') ? 'text-white font-bold' : '' }}">Loan Amortizations</a>
                <a href="{{ route('payroll.thirteenth-month') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.thirteenth-month') ? 'text-white font-bold' : '' }}">13th Month Pay</a>
                <a href="{{ route('payroll.off-cycle') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.off-cycle') ? 'text-white font-bold' : '' }}">Off-Cycle & Final Pay</a>
                <a href="{{ route('payroll.payment-modes') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.payment-modes') ? 'text-white font-bold' : '' }}">Payment Modes Config</a>
                <a href="{{ route('payroll.audit-trail') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'payroll.audit-trail') ? 'text-white font-bold' : '' }}">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        AI Audit & Compliance
                    </span>
                </a>
            </div>
        </div>

        <!-- 2. Compensation Planning -->
        <div class="sidebar-group">
            <button onclick="toggleGroup('compensation-group')"
                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white {{ (isset($currentPage) && str_starts_with($currentPage, 'compensation')) ? 'bg-white/10 text-white' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="sidebar-text flex-1 text-left truncate">Compensation Planning</span>
                <svg class="sidebar-text w-3 h-3 flex-shrink-0 transition-transform duration-200 {{ (isset($currentPage) && str_starts_with($currentPage, 'compensation')) ? 'rotate-180' : '' }}" id="compensation-group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="compensation-group" class="{{ (isset($currentPage) && str_starts_with($currentPage, 'compensation')) ? '' : 'hidden' }} ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                <a href="{{ route('compensation.salary-bands') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'compensation.salary-bands') ? 'text-white font-bold' : '' }}">Pay Scale Benchmarks</a>
                <a href="{{ route('compensation.counter-offers') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'compensation.counter-offers') ? 'text-white font-bold' : '' }}">Counter Offers & Packages</a>
                <a href="{{ route('compensation.merit-promotions') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'compensation.merit-promotions') ? 'text-white font-bold' : '' }}">Salary Progression & Merit</a>
                <a href="{{ route('compensation.audit-trail') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'compensation.audit-trail') ? 'text-white font-bold' : '' }}">Audit Trail</a>
            </div>
        </div>

        <!-- 3. Claims and Reimbursement -->
        <div class="sidebar-group">
            <button onclick="toggleGroup('claims-group')"
                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white {{ (isset($currentPage) && str_starts_with($currentPage, 'claims')) ? 'bg-white/10 text-white' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="sidebar-text flex-1 text-left truncate">Claims & Reimbursement</span>
                <svg class="sidebar-text w-3 h-3 flex-shrink-0 transition-transform duration-200 {{ (isset($currentPage) && str_starts_with($currentPage, 'claims')) ? 'rotate-180' : '' }}" id="claims-group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="claims-group" class="{{ (isset($currentPage) && str_starts_with($currentPage, 'claims')) ? '' : 'hidden' }} ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                <a href="{{ route('claims.expenses') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'claims.expenses') ? 'text-white font-bold' : '' }}">Driver Work Expenses</a>
                <a href="{{ route('claims.maternity-leave') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'claims.maternity-leave') ? 'text-white font-bold' : '' }}">Maternity Leave Request</a>
                <a href="{{ route('claims.reports') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'claims.reports') ? 'text-white font-bold' : '' }}">Summary & Audit Reports</a>
                <a href="{{ route('claims.categories') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'claims.categories') ? 'text-white font-bold' : '' }}">Claim Categories & Limits</a>
            </div>
        </div>

        <!-- 4. Benefits Administration -->
        <div class="sidebar-group">
            <button onclick="toggleGroup('benefits-group')"
                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white {{ (isset($currentPage) && (str_starts_with($currentPage, 'benefits') || str_starts_with($currentPage, 'driver-insurance'))) ? 'bg-white/10 text-white' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span class="sidebar-text flex-1 text-left truncate">Benefits Admin</span>
                <svg class="sidebar-text w-3 h-3 flex-shrink-0 transition-transform duration-200 {{ (isset($currentPage) && (str_starts_with($currentPage, 'benefits') || str_starts_with($currentPage, 'driver-insurance'))) ? 'rotate-180' : '' }}" id="benefits-group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="benefits-group" class="{{ (isset($currentPage) && (str_starts_with($currentPage, 'benefits') || str_starts_with($currentPage, 'driver-insurance'))) ? '' : 'hidden' }} ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                <a href="{{ route('benefits.sil') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && ($currentPage === 'benefits.sil' || $currentPage === 'benefits.index')) ? 'text-white font-bold' : '' }}">Service Incentive Leave (SIL)</a>
                <a href="{{ route('benefits.meal-allowance') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'benefits.meal-allowance') ? 'text-white font-bold' : '' }}">Meal Allowance Subsidy</a>
                <a href="{{ route('benefits.christmas-bonus') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'benefits.christmas-bonus') ? 'text-white font-bold' : '' }}">Christmas Bonus Policy</a>
                <a href="{{ route('driver-insurance.index') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && str_starts_with($currentPage, 'driver-insurance')) ? 'text-white font-bold' : '' }}">Driver Insurance Pool</a>
            </div>
        </div>

        <!-- 5. HR Analytics Dashboard -->
        <div class="sidebar-group">
            <button onclick="toggleGroup('analytics-group')"
                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-white/80 hover:bg-white/10 hover:text-white {{ (isset($currentPage) && str_starts_with($currentPage, 'analytics')) ? 'bg-white/10 text-white' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
                <span class="sidebar-text flex-1 text-left truncate">HR Analytics Dashboard</span>
                <svg class="sidebar-text w-3 h-3 flex-shrink-0 transition-transform duration-200 {{ (isset($currentPage) && str_starts_with($currentPage, 'analytics')) ? 'rotate-180' : '' }}" id="analytics-group-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="analytics-group" class="{{ (isset($currentPage) && str_starts_with($currentPage, 'analytics')) ? '' : 'hidden' }} ml-4 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                <a href="{{ route('analytics.performance') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'analytics.performance') ? 'text-white font-bold' : '' }}">Employee Performance</a>
                <a href="{{ route('analytics.payroll') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'analytics.payroll') ? 'text-white font-bold' : '' }}">Payroll & Salary Reports</a>
                <a href="{{ route('analytics.budget') }}" class="block text-xs text-white/60 hover:text-white py-1.5 transition-colors {{ (isset($currentPage) && $currentPage === 'analytics.budget') ? 'text-white font-bold' : '' }}">Cost & Budget Overview</a>
            </div>
        </div>

    </nav>

    <!-- Sidebar Footer -->
    <div class="flex-shrink-0 px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-[#F44336]/20 flex items-center justify-center text-[#F44336] text-xs font-bold flex-shrink-0">
                A
            </div>
            <div class="sidebar-text flex-1 min-w-0">
                <p class="text-xs font-bold text-white truncate">Admin User</p>
                <p class="text-[10px] text-white/40 truncate">admin@tripwise.app</p>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="inline flex-shrink-0">
                @csrf
                <button type="submit" class="sidebar-text text-white/40 hover:text-white transition-colors cursor-pointer" title="Sign out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

</aside>

<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="closeMobileSidebar()"></div>
