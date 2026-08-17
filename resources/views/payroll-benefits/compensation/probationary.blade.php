@extends('layouts.app')

@php
    $pageTitle = 'Probationary to Regular Conversion Desk';
    $currentPage = 'compensation.probationary';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Probationary to Regular Conversion Desk</h1>
            <p class="text-xs text-gray-500 mt-1">Track 6-month statutory countdown, evaluate performance milestones, and execute regularization adjustments.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Milestone Watch Active
            </span>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <div x-data="{
        activeTab: 'active', {{-- 'active', 'policy' --}}
        actionModalOpen: false,
        activeEmployee: null,
        decision: 'regularize',
        newRate: 0,
        extensionMonths: 3,
        reason: '',
        
        openDecisionModal(item) {
            this.activeEmployee = item;
            this.decision = 'regularize';
            this.newRate = item.suggested_regular_salary;
            this.reason = 'Regularization adjustment upon successful 6-month probationary review with standard band merit.';
            this.actionModalOpen = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Active Reviews -->
                <button type="button" @click="activeTab = 'active'" 
                        :class="activeTab === 'active' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Active Probationary Reviews
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ count($probationaryEmployees ?? []) }} Staff</span>
                </button>

                <!-- Tab 2: Policy & Framework -->
                <button type="button" @click="activeTab = 'policy'" 
                        :class="activeTab === 'policy' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Statutory Framework & Rules
                </button>
            </div>

            <!-- Total Monitored Badge -->
            <div class="hidden sm:flex items-center gap-2 text-xs font-bold bg-white/80 border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-2xs">
                <span class="text-gray-400">Total Monitored:</span>
                <span class="font-black text-gray-900">{{ count($probationaryEmployees ?? []) }} In Probation</span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ACTIVE PROBATIONARY REVIEWS & ROSTER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'active'" x-transition class="space-y-6">

            <!-- Milestone KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Critical ≤ 7 Days -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border-2 border-rose-200 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-rose-600">Critical Reminder</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-pulse"></span>
                    </div>
                    <div class="text-3xl font-black font-outfit text-gray-900">
                        {{ count($overview['critical_7_days'] ?? []) }}
                    </div>
                    <p class="text-xs text-gray-500 font-medium">≤ 7 Days left. Immediate decision required.</p>
                </div>

                <!-- Due in ≤ 30 Days -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-amber-200 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-700">Evaluation Due</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="text-3xl font-black font-outfit text-gray-900">
                        {{ count($overview['due_30_days'] ?? []) }}
                    </div>
                    <p class="text-xs text-gray-500 font-medium">≤ 30 Days left. Submit review proposal.</p>
                </div>

                <!-- Notice in ≤ 60 Days -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-blue-200 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-blue-700">Notice Period</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    </div>
                    <div class="text-3xl font-black font-outfit text-gray-900">
                        {{ count($overview['notice_60_days'] ?? ($overview['review_60_days'] ?? [])) }}
                    </div>
                    <p class="text-xs text-gray-500 font-medium">≤ 60 Days left. Monitor performance metrics.</p>
                </div>

                <!-- Upcoming -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-gray-400">On Track</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    </div>
                    <div class="text-3xl font-black font-outfit text-gray-900">
                        {{ count($overview['upcoming'] ?? ($overview['on_track'] ?? [])) }}
                    </div>
                    <p class="text-xs text-gray-500 font-medium">> 60 Days. In statutory training period.</p>
                </div>

            </div>

            <!-- Active Probationary Employee Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Probationary Personnel Watchlist</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Automated 6-month countdown timer tracked from date of hire.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Employee</th>
                                <th class="py-3.5 px-4">Position</th>
                                <th class="py-3.5 px-4 text-center">Hire Date</th>
                                <th class="py-3.5 px-4 text-center">6-Month Target</th>
                                <th class="py-3.5 px-4 text-center">Countdown</th>
                                <th class="py-3.5 px-4 text-right">Current Base</th>
                                <th class="py-3.5 px-4 text-right">Regular Salary</th>
                                <th class="py-3.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($probationaryEmployees as $item)
                                @php
                                    $emp = is_array($item) ? ($item['employee'] ?? null) : $item;
                                    $days = is_array($item) ? ($item['days_remaining'] ?? 0) : 0;
                                @endphp
                                @if($emp)
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        <div class="font-black text-sm text-gray-900">{{ $emp->first_name ?? 'Employee' }} {{ $emp->last_name ?? '' }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $emp->employee_code ?? '' }}</span>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-gray-700">{{ $emp->position ?? 'Staff' }}</td>
                                    <td class="py-4 px-4 text-center font-mono text-gray-600 text-xs">
                                        {{ $item['hire_date'] }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono font-bold text-gray-900 text-xs">
                                        {{ $item['target_date'] }}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if($days <= 7)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200 animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                                {{ $days }}d left
                                            </span>
                                        @elseif($days <= 30)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                {{ $days }}d left
                                            </span>
                                        @elseif($days <= 60)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-800 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                                {{ $days }}d left
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                                {{ $days }}d left
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-semibold text-gray-700 text-sm">
                                        ₱{{ number_format((float)$item['current_salary'], 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-black text-gray-900 text-sm">
                                        ₱{{ number_format((float)$item['suggested_regular_salary'], 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <button type="button" @click="openDecisionModal({{ Js::from($item) }})" 
                                                class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-2xs">
                                            Evaluate
                                        </button>
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No active employees currently in probationary period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: STATUTORY POLICY & RULES -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'policy'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Probationary Conversion Policy & Module Integration</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Automated downstream triggers upon regularizing probationary employees.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="p-5 bg-emerald-50/40 rounded-2xl border border-emerald-200 space-y-2">
                        <span class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider block">1. Benefits & HMO Entitlement</span>
                        <p class="text-xs text-gray-700 font-medium">Automatically triggers Module 4 (HMO & Benefits) full health coverage entitlement upon regularization.</p>
                    </div>

                    <div class="p-5 bg-blue-50/40 rounded-2xl border border-blue-200 space-y-2">
                        <span class="text-xs font-extrabold text-blue-800 uppercase tracking-wider block">2. Tenure Step Progression</span>
                        <p class="text-xs text-gray-700 font-medium">Starts the Tenure Step progression clock from Step 1 towards multi-year step milestones.</p>
                    </div>

                    <div class="p-5 bg-purple-50/40 rounded-2xl border border-purple-200 space-y-2">
                        <span class="text-xs font-extrabold text-purple-800 uppercase tracking-wider block">3. Automated Payroll Sync</span>
                        <p class="text-xs text-gray-700 font-medium">Synchronizes the regularized base salary directly into the Payroll processing engine for subsequent cutoffs.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Decision Modal (Regularize, Extend, Terminate) -->
        <div x-show="actionModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="actionModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-lg w-full p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Probationary Review Decision Desk</h3>
                    <button type="button" @click="actionModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeEmployee">
                    <form :action="'{{ url('/compensation/probationary') }}/' + activeEmployee.employee.id + '/regularize'" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="decision" :value="decision">

                        <!-- Employee Overview Card -->
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Employee</span>
                                <span class="font-black text-sm text-gray-900" x-text="activeEmployee.employee.first_name + ' ' + activeEmployee.employee.last_name"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Position</span>
                                <span class="font-extrabold text-gray-800" x-text="activeEmployee.employee.position"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Hire Date</span>
                                <span class="font-mono font-bold text-gray-700" x-text="activeEmployee.hire_date"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Current Base</span>
                                <span class="font-black font-outfit text-gray-900 text-sm" x-text="'₱' + Number(activeEmployee.current_salary).toLocaleString()"></span>
                            </div>
                        </div>

                        <!-- Decision Tabs -->
                        <div>
                            <label class="block text-xs font-extrabold text-gray-800 mb-1.5">Conversion Action Decision</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="decision = 'regularize'" 
                                        :class="decision === 'regularize' ? 'bg-[#16a34a] text-white font-black shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold'" 
                                        class="py-2.5 text-xs rounded-xl transition-all text-center">
                                    Regularize
                                </button>
                                <button type="button" @click="decision = 'extend'" 
                                        :class="decision === 'extend' ? 'bg-amber-600 text-white font-black shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold'" 
                                        class="py-2.5 text-xs rounded-xl transition-all text-center">
                                    Extend
                                </button>
                                <button type="button" @click="decision = 'terminate'" 
                                        :class="decision === 'terminate' ? 'bg-rose-600 text-white font-black shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold'" 
                                        class="py-2.5 text-xs rounded-xl transition-all text-center">
                                    Terminate
                                </button>
                            </div>
                        </div>

                        <!-- 1. Regularize Form Fields -->
                        <div x-show="decision === 'regularize'" class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Regularized Monthly Base Rate (₱)</label>
                                <input type="number" step="100" min="0" name="new_rate" x-model.number="newRate" required
                                       class="w-full text-base font-black font-outfit bg-white border-2 border-gray-300 rounded-xl px-4 py-2.5 text-gray-900 focus:outline-none focus:border-[#16a34a]">
                            </div>

                            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-xs space-y-1 font-semibold">
                                <div>• Triggers Module 4 full health HMO entitlement.</div>
                                <div>• Initiates Tenure Step clock progression.</div>
                                <div>• Syncs new salary directly to next Payroll cycle.</div>
                            </div>
                        </div>

                        <!-- 2. Extend Form Fields -->
                        <div x-show="decision === 'extend'" class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Extension Duration</label>
                                <select name="extension_months" x-model.number="extensionMonths" 
                                        class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-amber-600">
                                    <option value="1">1 Month Extension</option>
                                    <option value="2">2 Months Extension</option>
                                    <option value="3">3 Months Extension (Standard)</option>
                                    <option value="6">6 Months Extension (Max Statutory)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 3. Terminate Form Fields -->
                        <div x-show="decision === 'terminate'" class="space-y-3">
                            <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-rose-900 text-xs font-semibold">
                                Employee status will be updated to separated, and offboarding/severance will be routed to Team 1.
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button type="button" @click="actionModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">Cancel</button>
                            <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-6 py-2.5 rounded-xl transition-all shadow-sm">Save & Execute</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>

@endsection
