@extends('layouts.app')

@php
    $pageTitle = 'Tenure Step Progression & Matrix';
    $currentPage = 'compensation.tenure-steps';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Tenure Step Progression & Matrix</h1>
            <p class="text-xs text-gray-500 mt-1">Define multi-year step increment tables (Steps 1–7), track employee years of service, and approve tenure raises.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Tenure Engine Live
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
        activeTab: 'roster', {{-- 'roster', 'matrix', 'policy' --}}
        stepModalOpen: false,
        applyModalOpen: false,
        holdModalOpen: false,
        cardModalOpen: false,
        selectedGradeId: '{{ $salaryGrades->first()?->id }}',
        activeCandidate: null,
        
        openApply(c) {
            this.activeCandidate = c;
            this.applyModalOpen = true;
        },
        openHold(c) {
            this.activeCandidate = c;
            this.holdModalOpen = true;
        },
        openCard(c) {
            this.activeCandidate = c;
            this.cardModalOpen = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Eligible Employees Roster -->
                <button type="button" @click="activeTab = 'roster'" 
                        :class="activeTab === 'roster' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Eligible Employee Roster
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ count($candidates) }} Staff</span>
                </button>

                <!-- Tab 2: Step Matrix -->
                <button type="button" @click="activeTab = 'matrix'" 
                        :class="activeTab === 'matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Step Matrix Configuration (1–7)
                </button>

                <!-- Tab 3: Policy Guide -->
                <button type="button" @click="activeTab = 'policy'" 
                        :class="activeTab === 'policy' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Hold Policy & Rules
                </button>
            </div>

            <!-- Add Step Button -->
            <div>
                <button type="button" @click="stepModalOpen = true" 
                        class="bg-[#1c1c1e] hover:bg-black text-white text-xs font-black px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Configure Custom Step
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ELIGIBLE EMPLOYEE ROSTER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'roster'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Personnel Tenure Progression Monitoring</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Employees reaching statutory years of service milestones eligible for incremental step rate adjustments.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Employee</th>
                                <th class="py-3.5 px-4">Position</th>
                                <th class="py-3.5 px-4 text-center">Tenure</th>
                                <th class="py-3.5 px-4 text-center">Current Step</th>
                                <th class="py-3.5 px-4 text-center">Target Step</th>
                                <th class="py-3.5 px-4 text-right">Current Salary</th>
                                <th class="py-3.5 px-4 text-right">Incremented Rate</th>
                                <th class="py-3.5 px-4 text-center">Eligibility</th>
                                <th class="py-3.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($candidates as $c)
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        <div class="font-black text-sm text-gray-900">{{ $c['employee']->first_name }} {{ $c['employee']->last_name }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $c['employee']->employee_code }}</span>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-gray-700">{{ $c['employee']->position }}</td>
                                    <td class="py-4 px-4 text-center font-mono font-bold text-gray-800 text-xs">
                                        {{ number_format((float)($c['tenure_years'] ?? ($c['years_of_service'] ?? ($c['employee']->years_of_service ?? 0))), 1) }} yrs
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-gray-100 text-gray-800">
                                            Step {{ $c['current_step'] ?? 1 }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-red-50 text-[#F44336] border border-red-100">
                                            Step {{ $c['target_step'] ?? ($c['next_step'] ?? (($c['current_step'] ?? 1) + 1)) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-semibold text-gray-700 text-sm">
                                        ₱{{ number_format((float)$c['current_salary'], 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-black text-gray-900 text-sm">
                                        ₱{{ number_format((float)$c['projected_salary'], 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if($c['step_status'] === 'on_hold')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                On Hold
                                            </span>
                                        @elseif($c['is_eligible'])
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200 animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Eligible Now
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                                In Progress
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($c['is_eligible'])
                                                <button type="button" @click="openApply({{ Js::from($c) }})" 
                                                        class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-3.5 py-1.5 rounded-xl transition-all shadow-2xs">
                                                    Apply Step
                                                </button>
                                                <button type="button" @click="openHold({{ Js::from($c) }})" 
                                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-3 py-1.5 rounded-xl transition-all">
                                                    Hold
                                                </button>
                                            @else
                                                <button type="button" @click="openCard({{ Js::from($c) }})" 
                                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-3 py-1.5 rounded-xl transition-all">
                                                    View Card
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No active personnel records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: STEP MATRIX 1–7 CONFIGURATION -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'matrix'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Salary Grade Step Increment Tables (Steps 1–7)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Statutory 3-year incremental rate matrices configured per job grade position.</p>
                    </div>

                    <!-- Grade Selector Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto bg-gray-100 p-1 rounded-2xl">
                        @foreach($salaryGrades as $grade)
                            <button type="button" @click="selectedGradeId = '{{ $grade->id }}'" 
                                    :class="selectedGradeId == '{{ $grade->id }}' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 font-bold'" 
                                    class="px-3 py-1.5 text-xs rounded-xl transition-all whitespace-nowrap">
                                {{ $grade->position_name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @foreach($salaryGrades as $grade)
                    <div x-show="selectedGradeId == '{{ $grade->id }}'" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                    <th class="py-3.5 px-4">Step Level</th>
                                    <th class="py-3.5 px-4 text-center">Required Service</th>
                                    <th class="py-3.5 px-4 text-right">Step Base Monthly Rate</th>
                                    <th class="py-3.5 px-4 text-right">Annual Escalation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                @forelse($grade->steps as $step)
                                    <tr class="hover:bg-gray-50/75 transition-colors">
                                        <td class="py-4 px-4 font-black text-sm text-gray-900">
                                            <span class="px-3 py-1.5 rounded-xl bg-gray-900 text-white font-mono text-xs font-extrabold shadow-2xs">
                                                Step {{ $step->step_number }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-center font-bold text-gray-700 text-xs">
                                            {{ $step->years_required }} Years Continuous Service
                                        </td>
                                        <td class="py-4 px-4 text-right font-black font-outfit text-gray-900 text-sm">
                                            ₱{{ number_format((float)$step->step_salary, 2) }}
                                        </td>
                                        <td class="py-4 px-4 text-right font-mono font-bold text-emerald-700 text-xs">
                                            +{{ number_format((float)$grade->annual_growth_rate, 1) }}% statutory step
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-400 text-xs font-semibold">
                                            No step increment levels configured for this salary grade yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: HOLD POLICY REFERENCE GUIDE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'policy'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Statutory Tenure Step Hold Conditions</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Guidelines and compliance rules for temporarily withholding step increments.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="p-5 bg-rose-50/40 rounded-2xl border border-rose-200 space-y-2">
                        <span class="text-xs font-extrabold text-rose-800 uppercase tracking-wider block">1. Active Disciplinary Case</span>
                        <p class="text-xs text-gray-700 font-medium">Pending HR disciplinary investigation, suspension, or formal warning letter within the past 12-month period.</p>
                    </div>

                    <div class="p-5 bg-amber-50/40 rounded-2xl border border-amber-200 space-y-2">
                        <span class="text-xs font-extrabold text-amber-800 uppercase tracking-wider block">2. Unsatisfactory Performance</span>
                        <p class="text-xs text-gray-700 font-medium">Team 3 performance review rating of 'Needs Improvement' for the preceding appraisal cycle.</p>
                    </div>

                    <div class="p-5 bg-blue-50/40 rounded-2xl border border-blue-200 space-y-2">
                        <span class="text-xs font-extrabold text-blue-800 uppercase tracking-wider block">3. Prolonged AWOL / Leave</span>
                        <p class="text-xs text-gray-700 font-medium">Unapproved absences or extended leaves without pay exceeding 30 consecutive calendar days.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Apply Step Modal -->
        <div x-show="applyModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="applyModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Approve Tenure Step Increment</h3>
                    <button type="button" @click="applyModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <template x-if="activeCandidate">
                    <form :action="'{{ route('compensation.tenure-steps.apply', ['employee' => '__ID__']) }}'.replace('__ID__', activeCandidate.employee.id)" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="target_step" :value="activeCandidate.target_step || activeCandidate.next_step || (activeCandidate.current_step + 1)">
                        <input type="hidden" name="new_rate" :value="activeCandidate.projected_salary">

                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-bold">Employee:</span>
                                <span class="font-black text-gray-900" x-text="activeCandidate.employee.first_name + ' ' + activeCandidate.employee.last_name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-bold">Progression:</span>
                                <span class="font-black text-[#F44336]" x-text="'Step ' + activeCandidate.current_step + ' → Step ' + (activeCandidate.target_step || activeCandidate.next_step || (activeCandidate.current_step + 1))"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-bold">New Monthly Rate:</span>
                                <span class="font-black font-outfit text-gray-900 text-sm" x-text="'₱' + Number(activeCandidate.projected_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Approval Notes</label>
                            <input type="text" name="reason" value="Approved statutory tenure step increment upon reaching service milestone." 
                                   class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button type="button" @click="applyModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">Cancel</button>
                            <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Confirm Step & Sync</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- Hold Step Modal -->
        <div x-show="holdModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="holdModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Withhold Step Increment</h3>
                    <button type="button" @click="holdModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <template x-if="activeCandidate">
                    <form :action="'{{ route('compensation.tenure-steps.hold', ['employee' => '__ID__']) }}'.replace('__ID__', activeCandidate.employee.id)" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Reason for Step Hold</label>
                            <textarea name="reason" rows="3" required placeholder="Specify disciplinary, performance, or attendance reason for withholding..." 
                                      class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-900 focus:outline-none focus:border-[#F44336]"></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button type="button" @click="holdModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">Cancel</button>
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Hold</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- Employee Tenure Progression Card Modal -->
        <div x-show="cardModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="cardModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-lg w-full p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Personnel Tenure Progression Card</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Individual service timeline and step milestone record.</p>
                    </div>
                    <button type="button" @click="cardModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeCandidate">
                    <div class="space-y-4 text-xs">
                        <!-- Employee Profile Card -->
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Employee Name</span>
                                <span class="font-black text-sm text-gray-900" x-text="activeCandidate.employee.first_name + ' ' + activeCandidate.employee.last_name"></span>
                                <span class="text-xs text-gray-500 font-mono" x-text="activeCandidate.employee.employee_code"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Position & Grade</span>
                                <span class="font-extrabold text-gray-800" x-text="activeCandidate.employee.position"></span>
                                <span class="text-xs text-gray-500 block" x-text="activeCandidate.salary_grade ? activeCandidate.salary_grade.position_name : 'Standard Grade'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Continuous Service Tenure</span>
                                <span class="font-black text-sm text-gray-900" x-text="Number(activeCandidate.tenure_years || activeCandidate.years_of_service || 0).toFixed(1) + ' Years'"></span>
                                <span class="text-xs text-gray-500 block" x-text="activeCandidate.tenure_text || 'Completed service'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs font-bold block">Step Progression Status</span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black mt-0.5"
                                      :class="activeCandidate.step_status === 'on_hold' ? 'bg-amber-50 text-amber-800 border border-amber-200' : (activeCandidate.is_eligible ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-700')">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="activeCandidate.step_status === 'on_hold' ? 'bg-amber-600' : (activeCandidate.is_eligible ? 'bg-emerald-600' : 'bg-gray-400')"></span>
                                    <span x-text="activeCandidate.step_status === 'on_hold' ? 'On Hold' : (activeCandidate.is_eligible ? 'Eligible for Step Up' : 'Service In Progress')"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Step Milestone Progression Breakdown -->
                        <div class="p-4 bg-red-50/30 rounded-2xl border border-red-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-gray-800">Step Progression Ladder</span>
                                <span class="text-xs font-black text-[#F44336]" x-text="'Step ' + activeCandidate.current_step + ' → Step ' + (activeCandidate.target_step || activeCandidate.next_step || (activeCandidate.current_step + 1))"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-red-100">
                                <div>
                                    <span class="text-gray-400 font-bold block text-[11px]">Current Base Salary</span>
                                    <span class="font-black font-outfit text-sm text-gray-800" x-text="'₱' + Number(activeCandidate.current_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-bold block text-[11px]">Projected Incremented Rate</span>
                                    <span class="font-black font-outfit text-base text-gray-900" x-text="'₱' + Number(activeCandidate.projected_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Hold Reason if applicable -->
                        <template x-if="activeCandidate.step_status === 'on_hold' && activeCandidate.hold_reason">
                            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 text-xs font-semibold">
                                <span class="font-bold block text-amber-800">Withholding Reason:</span>
                                <span x-text="activeCandidate.hold_reason"></span>
                            </div>
                        </template>

                        <!-- Action Footer -->
                        <div class="pt-2 flex items-center justify-between">
                            <button type="button" @click="cardModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                                Close
                            </button>
                            <div class="flex items-center gap-2">
                                <template x-if="activeCandidate.is_eligible">
                                    <button type="button" @click="cardModalOpen = false; openApply(activeCandidate)" 
                                            class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">
                                        Apply Step Increment
                                    </button>
                                </template>
                                <template x-if="activeCandidate.step_status !== 'on_hold'">
                                    <button type="button" @click="cardModalOpen = false; openHold(activeCandidate)" 
                                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                                        Put on Hold
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Configure Custom Step Modal -->
        <div x-show="stepModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="stepModalOpen = false" class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Configure Salary Grade Step</h3>
                    <button type="button" @click="stepModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form action="{{ route('compensation.tenure-steps.store-step') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Salary Grade Position</label>
                        <select name="salary_grade_id" required 
                                class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                            @foreach($salaryGrades as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->position_name }} (Min: ₱{{ number_format($grade->min_salary) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Step Level (1–7)</label>
                            <input type="number" min="1" max="10" name="step_number" value="2" required 
                                   class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Required Service (Yrs)</label>
                            <input type="number" min="0" max="30" step="0.5" name="years_required" value="3.0" required 
                                   class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Increment %</label>
                            <input type="number" min="0" max="50" step="0.1" name="increment_percentage" value="5.0" required 
                                   class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Base Override (₱)</label>
                            <input type="number" min="0" step="100" name="base_amount" placeholder="Optional fixed rate" 
                                   class="w-full text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2.5">
                        <button type="button" @click="stepModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">Cancel</button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Step Increment</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
