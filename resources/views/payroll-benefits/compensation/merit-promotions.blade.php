@extends('layouts.app')

@php
    $pageTitle = 'Salary Progression, Merit & Promotions Planning Desk';
    $currentPage = 'compensation.merit-promotions';

    // Map salary grades by position for fast lookup
    $gradeMap = [];
    foreach ($salaryGrades as $sg) {
        $gradeMap[strtolower(trim($sg->position_name))] = $sg;
    }
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Salary Progression, Merit & Promotion Planning Desk</h1>
            <p class="text-xs text-gray-500 mt-1">Calibrate performance-driven merit increases, tenure step progressions (Steps 1–7), and synchronize approved salary grade increases from Team 3 (Talent Management) promotions into weekly payroll.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Salary Progression Live
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
        activeTab: 'table', {{-- 'table', 'tenure-matrix', 'matrix', 'proposals' --}}
        selectedDept: '{{ $deptId ?? 'all' }}',
        selectedGradeId: '{{ $salaryGrades->first()?->id }}',
        searchQuery: '',
        quickFilter: 'all',

        employeesData: {{ Js::from($employees->map(function($e) use ($gradeMap, $tenureProgressionService, $salaryGrades, $team3Promotions) {
            $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
            $currentSalary = (float)($isDriver ? ($e->daily_rate * 26) : ($e->monthly_rate ?: 25000.00));
            $posKey = strtolower(trim($e->position ?? ''));
            $grade = $gradeMap[$posKey] ?? null;
            $maxSalary = $grade ? (float)$grade->max_salary : ($currentSalary * 1.5);
            $rating = $e->performance_rating ?? 'Satisfactory';
            
            $suggestedPct = match(strtolower(trim($rating))) {
                'outstanding', '5.0', '5' => 10.0,
                'very satisfactory', 'exceeds expectations', '4.5', '4.0', '4' => 6.5,
                'satisfactory', 'meets expectations', '3.5', '3.0', '3' => 3.5,
                'needs improvement', '2.5', '2.0', '2' => 1.0,
                default => 0.0,
            };

            $isMaxStep = (int) ($e->current_step ?? 1) >= 7;
            $nextStepCalc = isset($tenureProgressionService) ? $tenureProgressionService->computeNextStep($e) : null;
            $nextStepSalary = $nextStepCalc ? (float) $nextStepCalc['next_step_salary'] : round($currentSalary * 1.03, 2);
            $incrementAmount = $nextStepCalc ? (float) $nextStepCalc['increment_amount'] : round(max(0.0, $nextStepSalary - $currentSalary), 2);
            $incrementPct = $isMaxStep ? 0.0 : ($nextStepCalc ? (float) $nextStepCalc['increment_percentage'] : 3.0);
            $totalSuggestedPct = round($suggestedPct + $incrementPct, 1);

            // Team 3 Career Progression Ladder Resolution (Workday Standard)
            $targetGrade = match(true) {
                str_contains($posKey, 'driver') => $salaryGrades->firstWhere('grade_code', 'PG-2') ?? $salaryGrades->skip(1)->first(),
                str_contains($posKey, 'dispatcher'), str_contains($posKey, 'assistant') => $salaryGrades->firstWhere('grade_code', 'PG-3') ?? $salaryGrades->skip(2)->first(),
                default => $salaryGrades->firstWhere('grade_code', 'PG-4') ?? $salaryGrades->last(),
            } ?? $salaryGrades->first();

            $targetPosition = match(true) {
                str_contains($posKey, 'driver') => 'Lead Fleet Driver',
                str_contains($posKey, 'dispatcher') => 'Fleet Operations Lead',
                str_contains($posKey, 'assistant') => 'HR Specialist',
                str_contains($posKey, 'specialist') => 'Senior HR Specialist',
                str_contains($posKey, 'accountant') => 'Finance & Accounting Lead',
                default => 'Senior ' . ($e->position ?: 'Staff'),
            };

            // Check for official approved promotion order from Team 3 (Handshake Contract)
            $team3Promo = isset($team3Promotions) ? $team3Promotions->get($e->id) : null;
            $isPromoted = $team3Promo !== null;
            $promotedPosition = $team3Promo?->new_position ?? $targetPosition;
            $promotedGradeId = $targetGrade?->id;
            $promotedSalary = $isPromoted ? max((float)($targetGrade?->min_salary ?? 28000.00), round($currentSalary * 1.15, 2)) : null;

            return [
                'id' => $e->id,
                'name' => $e->first_name . ' ' . $e->last_name,
                'code' => $e->employee_code,
                'position' => $e->position,
                'department_id' => $e->department_id,
                'department' => $e->department?->name ?? 'Operations',
                'current_salary' => $currentSalary,
                'max_salary' => $maxSalary,
                'rating' => $rating,
                'merit_pct' => $suggestedPct,
                'tenure_pct' => $incrementPct,
                'raise_pct' => $isPromoted ? round((($promotedSalary - $currentSalary) / $currentSalary) * 100, 1) : $totalSuggestedPct,
                'new_salary' => $isPromoted ? $promotedSalary : round($currentSalary * (1 + ($totalSuggestedPct / 100)), 2),
                'years_of_service' => (float) ($e->years_of_service ?? 1.0),
                'current_step' => (int) ($e->current_step ?? 1),
                'next_step' => $nextStepCalc ? (int) $nextStepCalc['next_step'] : min(7, (int) ($e->current_step ?? 1) + 1),
                'next_step_salary' => $nextStepSalary,
                'step_increment_amount' => $incrementAmount,
                'step_increment_pct' => $incrementPct,
                'is_max_step' => $isMaxStep,
                'step_status' => $e->step_status ?? 'normal',
                'next_career_grade_id' => $targetGrade?->id,
                'next_career_position' => $targetPosition,
                'next_career_grade_code' => $targetGrade?->grade_code ?? 'PG-2',
                'next_career_min_salary' => (float)($targetGrade?->min_salary ?? 28000.00),
                'next_career_max_salary' => (float)($targetGrade?->max_salary ?? 40000.00),
                'is_promoted' => $isPromoted,
                'promoted_position' => $promotedPosition,
                'promoted_grade_id' => $promotedGradeId,
                'promoted_salary' => $promotedSalary,
                'promoted_step' => 1,
                'selected' => true,
            ];
        })) }},

        get totalPromotedCount() {
            return this.employeesData.filter(e => e.is_promoted).length;
        },

        get filteredEmployees() {
            let list = this.employeesData;

            // Department filter
            if (this.selectedDept !== 'all' && this.selectedDept) {
                list = list.filter(e => e.department_id == this.selectedDept);
            }

            // Quick Filter
            if (this.quickFilter === 'promoted') {
                list = list.filter(e => e.is_promoted);
            } else if (this.quickFilter === 'top-merit') {
                list = list.filter(e => e.rating === 'Outstanding' || e.rating === '5.0' || (Number(e.merit_pct) >= 8.0));
            } else if (this.quickFilter === 'max-step') {
                list = list.filter(e => e.is_max_step || (Number(e.current_step) >= 7));
            }

            // Search query filter
            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase().trim();
                list = list.filter(e => 
                    (e.name && e.name.toLowerCase().includes(q)) ||
                    (e.code && e.code.toLowerCase().includes(q)) ||
                    (e.position && e.position.toLowerCase().includes(q)) ||
                    (e.department && e.department.toLowerCase().includes(q))
                );
            }

            return list;
        },

        getEmployeeTotalRaise(emp) {
            if (emp.is_promoted && emp.promoted_salary) {
                return Math.round(((emp.promoted_salary - emp.current_salary) / emp.current_salary) * 100 * 10) / 10;
            }
            return Math.round(((Number(emp.merit_pct) || 0) + (Number(emp.tenure_pct) || 0)) * 10) / 10;
        },

        getEmployeeNewSalary(emp) {
            if (emp.is_promoted && emp.promoted_salary) {
                return emp.promoted_salary;
            }
            const totalPct = this.getEmployeeTotalRaise(emp);
            return Math.min(emp.max_salary, Math.round(emp.current_salary * (1 + (totalPct / 100))));
        },

        get totalIncrementalSalary() {
            return this.filteredEmployees.reduce((acc, emp) => {
                const totalPct = this.getEmployeeTotalRaise(emp);
                return acc + (emp.current_salary * (totalPct / 100));
            }, 0);
        },

        get totalIncrementalMonthlyCTC() {
            // Incremental Base + ~13.5% employer statutory load
            return this.totalIncrementalSalary * 1.135;
        },

        get totalAnnualFinancialRequisition() {
            // (Monthly CTC x 12) + 13th month liability
            return (this.totalIncrementalMonthlyCTC * 12) + this.totalIncrementalSalary;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Merit & Progression Planning Table -->
                <button type="button" @click="activeTab = 'table'" 
                        :class="activeTab === 'table' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Progression Roster
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $employees->count() }}</span>
                </button>

                <!-- Tab 2: Tenure Step Matrix (Steps 1–7) -->
                <button type="button" @click="activeTab = 'tenure-matrix'" 
                        :class="activeTab === 'tenure-matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tenure Step Matrix (Steps 1–7)
                </button>

                <!-- Tab 3: 5-Tier Policy Reference -->
                <button type="button" @click="activeTab = 'matrix'" 
                        :class="activeTab === 'matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    5-Tier Merit Policy
                </button>

                <!-- Tab 4: History & Past Proposals -->
                <button type="button" @click="activeTab = 'proposals'" 
                        :class="activeTab === 'proposals' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Approved Adjustments Queue
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $adjustments->total() }}</span>
                </button>
            </div>

            <!-- Department Filter -->
            <div class="flex items-center gap-2">
                <select x-model="selectedDept" 
                        class="bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-bold text-gray-800 focus:outline-none focus:border-[#F44336]">
                    <option value="all">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- EXECUTIVE BUDGET REQUISITION CARDS (TEAM 5 INTEGRATION) -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Evaluated Headcount -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Evaluated Headcount</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-700">
                        {{ $departments->count() }} Depts
                    </span>
                </div>
                <div class="text-2xl font-black font-outfit text-gray-900" x-text="filteredEmployees.length + ' Personnel'"></div>
                <p class="text-xs text-gray-500 font-medium">Active merit cycle review</p>
            </div>

            <!-- Card 2: Promotions Pending Sync -->
            <div class="bg-white rounded-2xl border border-purple-100 p-5 shadow-sm space-y-1 bg-gradient-to-br from-white to-purple-50/30">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-900">Promotions Pending</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-950 font-mono">
                        Team 3 Order
                    </span>
                </div>
                <div class="text-2xl font-black font-outfit text-purple-950" x-text="totalPromotedCount + ' Approved'"></div>
                <p class="text-xs text-purple-700 font-medium">Ready for payroll calibration</p>
            </div>

            <!-- Card 3: Monthly Base Growth -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Monthly Base Growth</span>
                <div class="text-2xl font-black font-outfit text-purple-900" x-text="'PHP ' + Number(totalIncrementalSalary).toLocaleString(undefined, {minimumFractionDigits: 2})"></div>
                <p class="text-xs text-gray-500 font-medium">Net incremental salary expense</p>
            </div>

            <!-- Card 4: Monthly Employer CTC -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Monthly Employer CTC</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900">
                        +13.5% Load
                    </span>
                </div>
                <div class="text-2xl font-black font-outfit text-blue-950" x-text="'PHP ' + Number(totalIncrementalMonthlyCTC).toLocaleString(undefined, {minimumFractionDigits: 2})"></div>
                <p class="text-xs text-blue-700 font-medium">Includes SSS, PhilHealth & EC</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: 5-TIER MERIT CALIBRATOR TABLE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'table'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Annual Merit Increase Calibrator</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Applies 5-tier matrix based on read-only Team 3 Performance ratings. Adjust percentages within authorized band maximums.</p>
                    </div>
                </div>

                <!-- Quick Filter Pills & Search Bar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1 pb-1">
                    <!-- Quick Filter Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="quickFilter = 'all'"
                                :class="quickFilter === 'all' ? 'bg-gray-900 text-white font-black' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                            All Personnel
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="quickFilter === 'all' ? 'bg-gray-700 text-gray-200' : 'bg-gray-200 text-gray-700'" x-text="employeesData.length"></span>
                        </button>

                        <button type="button" @click="quickFilter = 'promoted'"
                                :class="quickFilter === 'promoted' ? 'bg-purple-900 text-white font-black' : 'bg-purple-50 text-purple-900 hover:bg-purple-100 font-bold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 border border-purple-200 shadow-2xs">
                            Promoted by Team 3
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-200 text-purple-950 font-mono" x-text="totalPromotedCount"></span>
                        </button>

                        <button type="button" @click="quickFilter = 'top-merit'"
                                :class="quickFilter === 'top-merit' ? 'bg-emerald-800 text-white font-black' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 font-bold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all border border-emerald-200 shadow-2xs">
                            Top Merit (5.0)
                        </button>

                        <button type="button" @click="quickFilter = 'max-step'"
                                :class="quickFilter === 'max-step' ? 'bg-amber-800 text-white font-black' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 font-bold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all border border-amber-200 shadow-2xs">
                            Reached Max Step
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-64">
                        <input type="text" x-model="searchQuery" placeholder="Search name, role, code..."
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs font-bold text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#F44336] focus:bg-white transition-all">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-200/80 shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 font-extrabold uppercase tracking-wider text-[11px]">
                                <th class="py-3.5 px-4">Employee Profile</th>
                                <th class="py-3.5 px-4 text-center">Appraisal Rating</th>
                                <th class="py-3.5 px-4 text-center min-w-[170px]">Team 3 Promotion Status</th>
                                <th class="py-3.5 px-4 text-center min-w-[220px]">Raise Calibrator</th>
                                <th class="py-3.5 px-4 text-right min-w-[140px]">Salary Progression</th>
                                <th class="py-3.5 px-4 text-right min-w-[140px]">Monthly Employer CTC</th>
                                <th class="py-3.5 px-4 text-center min-w-[130px]">Status / Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <template x-for="emp in filteredEmployees" :key="emp.id">
                                <tr :class="emp.is_promoted ? 'bg-purple-50/25 border-l-4 border-purple-500 hover:bg-purple-50/40' : 'hover:bg-gray-50/70'"
                                    class="transition-colors duration-150">
                                    
                                    <!-- Col 1: Employee Profile & Role -->
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-sm font-outfit text-gray-900" x-text="emp.name"></span>
                                                <span class="text-gray-400 font-mono text-[11px]" x-text="'(' + emp.code + ')'"></span>
                                            </div>
                                            <div class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                                                <span x-text="emp.position"></span>
                                                <span class="text-gray-300">•</span>
                                                <span class="text-gray-500 font-medium" x-text="emp.department"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-900 border border-amber-200/60 shadow-2xs">
                                                <span>Step <span x-text="emp.current_step"></span></span>
                                                <span class="text-amber-700 font-semibold" x-text="'(' + Number(emp.years_of_service).toFixed(1) + ' yrs service)'"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Col 2: Team 3 Appraisal Rating -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-3 py-1.5 rounded-xl text-xs font-black inline-block shadow-2xs"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-900 border border-emerald-200': emp.rating === 'Outstanding' || emp.rating === '5.0',
                                                  'bg-blue-100 text-blue-900 border border-blue-200': emp.rating === 'Very Satisfactory' || emp.rating === '4.5',
                                                  'bg-purple-100 text-purple-900 border border-purple-200': emp.rating === 'Satisfactory' || emp.rating === '3.5',
                                                  'bg-amber-100 text-amber-900 border border-amber-200': emp.rating === 'Needs Improvement' || emp.rating === '2.5',
                                                  'bg-rose-100 text-rose-900 border border-rose-200': emp.rating === 'Unsatisfactory' || emp.rating === '1.5'
                                              }"
                                              x-text="emp.rating">
                                        </span>
                                    </td>

                                    <!-- Col 3: Team 3 Promotion Track (Read-Only) -->
                                    <td class="py-3.5 px-4 text-center">
                                        <template x-if="emp.is_promoted">
                                            <div class="inline-flex flex-col items-center gap-1">
                                                <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-purple-100 text-purple-950 border border-purple-300 shadow-2xs">
                                                    Promoted: <span x-text="emp.promoted_position"></span>
                                                </span>
                                                <span class="text-[10px] font-bold text-purple-700 font-mono"
                                                      x-text="'+PHP ' + Number(emp.promoted_salary - emp.current_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' / mo'">
                                                </span>
                                            </div>
                                        </template>
                                        <template x-if="!emp.is_promoted">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500 font-mono">
                                                Standard Grade Track
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Col 4: Raise Calibrator (Merit + Step) -->
                                    <td class="py-3.5 px-4 text-center">
                                        <template x-if="emp.is_promoted">
                                            <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-black bg-purple-50 text-purple-900 border border-purple-200">
                                                <span>15.0% Promotional Floor</span>
                                            </div>
                                        </template>
                                        <template x-if="!emp.is_promoted">
                                            <div class="flex flex-col items-center gap-1.5">
                                                <div class="flex items-center justify-center gap-2">
                                                    <div class="flex items-center gap-1 bg-gray-50 px-2 py-1 rounded-lg border border-gray-200">
                                                        <span class="text-[10px] font-extrabold text-gray-500 uppercase">Merit:</span>
                                                        <input type="number" step="0.5" min="0" max="20" x-model.number="emp.merit_pct"
                                                               class="w-12 bg-white border border-gray-300 rounded px-1 py-0.5 text-xs font-black text-center text-purple-950 focus:outline-none focus:border-[#F44336]">
                                                        <span class="font-bold text-gray-400 text-[10px]">%</span>
                                                    </div>
                                                    <div class="flex items-center gap-1 bg-amber-50/70 px-2 py-1 rounded-lg border border-amber-200">
                                                        <span class="text-[10px] font-extrabold text-amber-800 uppercase">Step:</span>
                                                        <input type="number" step="0.5" min="0" max="15" x-model.number="emp.tenure_pct" :disabled="emp.is_max_step"
                                                               :class="emp.is_max_step ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white border-amber-300 text-amber-950'"
                                                               class="w-12 border rounded px-1 py-0.5 text-xs font-black text-center focus:outline-none focus:border-amber-500">
                                                        <span class="font-bold text-amber-600 text-[10px]">%</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] font-black text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200"
                                                          x-text="'Total: +' + getEmployeeTotalRaise(emp) + '%'">
                                                    </span>
                                                    <span x-show="!emp.is_max_step && emp.tenure_pct > 0" class="text-[10px] font-bold text-amber-700 font-mono" x-text="'(Step ' + emp.current_step + ' -> ' + emp.next_step + ')'"></span>
                                                    <span x-show="emp.is_max_step" class="text-[10px] font-bold text-purple-700">(Max Step Ceiling)</span>
                                                </div>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Col 5: Salary Progression (Baseline -> Proposed) -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="text-right space-y-0.5">
                                            <div class="text-[11px] text-gray-400 font-mono line-through"
                                                 x-text="'PHP ' + Number(emp.current_salary).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                            </div>
                                            <div class="text-sm font-black font-outfit text-purple-950"
                                                 x-text="'PHP ' + Number(getEmployeeNewSalary(emp)).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                            </div>
                                            <div class="text-[10px] font-bold text-emerald-700 font-mono"
                                                 x-text="'+PHP ' + Number(getEmployeeNewSalary(emp) - emp.current_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' / mo'">
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Col 6: Monthly Employer CTC Load -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="text-right space-y-0.5">
                                            <div class="text-xs font-black font-outfit text-blue-950"
                                                 x-text="'PHP ' + Number(getEmployeeNewSalary(emp) * 1.135).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                            </div>
                                            <div class="text-[10px] font-bold text-emerald-800 font-mono"
                                                 x-text="'+PHP ' + Number((getEmployeeNewSalary(emp) - emp.current_salary) * 1.135).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' load'">
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Col 7: Status & Action -->
                                    <td class="py-3.5 px-4 text-center">
                                        <template x-if="emp.is_promoted">
                                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold text-purple-800 bg-purple-50 border border-purple-200">
                                                Team 3 Promo Applied
                                            </span>
                                        </template>
                                        <template x-if="!emp.is_promoted">
                                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold text-gray-500 bg-gray-50 border border-gray-200">
                                                Active Track
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Inline Submission Footer -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-gray-100 mt-4">
                    <span class="text-xs text-gray-500 font-medium">Ready to commit compensation plans into active employee payroll profiles.</span>
                    <form action="{{ route('compensation.merit-promotions.complete') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plans_json" :value="JSON.stringify(filteredEmployees.map(e => ({ ...e, raise_pct: getEmployeeTotalRaise(e), new_salary: getEmployeeNewSalary(e), new_position: e.is_promoted ? e.promoted_position : null })))">
                        <button type="submit" 
                                class="bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-black px-6 py-2.5 rounded-xl transition-all shadow-sm">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: TENURE STEP MATRIX (STEPS 1–7) CONFIGURATION -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'tenure-matrix'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Salary Grade Step Increment Tables (Steps 1–7)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Multi-year tenure longevity rate ladders configured per job position.</p>
                    </div>

                    <!-- Grade Selector Pills -->
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
                    <div x-show="selectedGradeId == '{{ $grade->id }}'" class="space-y-4">
                        <div class="flex items-center justify-between bg-gray-50 p-3.5 rounded-xl border border-gray-200/60">
                            <div>
                                <span class="text-xs font-black text-gray-900">{{ $grade->position_name }}</span>
                                <span class="text-[11px] text-gray-500 font-mono ml-2">Grade Base: PHP {{ number_format((float)$grade->min_salary, 2) }} – PHP {{ number_format((float)$grade->max_salary, 2) }}</span>
                            </div>
                            <span class="text-xs font-bold text-amber-800 bg-amber-100/60 px-2.5 py-1 rounded-lg">
                                Standard Increment: +3.00% / Step
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                        <th class="py-3.5 px-4">Step Level</th>
                                        <th class="py-3.5 px-4 text-center">Required Service</th>
                                        <th class="py-3.5 px-4 text-right">Step Base Monthly Rate</th>
                                        <th class="py-3.5 px-4 text-right">Increment Percentage</th>
                                        <th class="py-3.5 px-4 text-right">Estimated Monthly CTC</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    @php
                                        $baseSalary = (float)$grade->min_salary;
                                    @endphp
                                    @for($stepNum = 1; $stepNum <= 7; $stepNum++)
                                        @php
                                            $stepRecord = $grade->steps->firstWhere('step_number', $stepNum);
                                            $yearsReq = $stepRecord ? (float)$stepRecord->years_required : ($stepNum - 1) * 1.0;
                                            $stepSalary = $stepRecord ? (float)$stepRecord->step_salary : round($baseSalary * pow(1.03, $stepNum - 1), 2);
                                            $incPct = $stepRecord ? (float)$stepRecord->increment_percentage : ($stepNum === 1 ? 0.0 : 3.0);
                                            $estCtc = round($stepSalary * 1.135, 2);
                                        @endphp
                                        <tr class="hover:bg-gray-50/75 transition-colors">
                                            <td class="py-4 px-4 font-black text-sm text-gray-900 flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-900 text-[11px] font-black inline-flex items-center justify-center">
                                                    {{ $stepNum }}
                                                </span>
                                                <span>Step {{ $stepNum }}</span>
                                                @if($stepNum === 1)
                                                    <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-bold">Entry Level</span>
                                                @elseif($stepNum === 7)
                                                    <span class="text-[10px] bg-purple-100 text-purple-900 px-1.5 py-0.5 rounded font-bold">Max Ceiling</span>
                                                @endif
                                            </td>
                                            <td class="py-4 px-4 text-center font-bold text-gray-700">
                                                {{ number_format($yearsReq, 1) }} {{ $yearsReq == 1.0 ? 'Year' : 'Years' }}
                                            </td>
                                            <td class="py-4 px-4 text-right font-mono font-black text-gray-900 text-sm">
                                                PHP {{ number_format($stepSalary, 2) }}
                                            </td>
                                            <td class="py-4 px-4 text-right font-mono font-bold text-emerald-700">
                                                {{ $stepNum === 1 ? '— (Baseline)' : '+' . number_format($incPct, 2) . '%' }}
                                            </td>
                                            <td class="py-4 px-4 text-right font-mono font-bold text-purple-900">
                                                PHP {{ number_format($estCtc, 2) }}
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: 5-TIER POLICY MATRIX REFERENCE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'matrix'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Official 5-Tier Merit Increase Matrix</h2>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">Corporate policy guidelines and performance-to-percentage increment calibrations.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-200 text-emerald-900">Tier 5 (Top Tier)</span>
                        <div class="text-base font-black text-emerald-950">5.0 Outstanding</div>
                        <div class="text-lg font-black font-outfit text-emerald-800">+8.0% to +12.0%</div>
                        <p class="text-[11px] text-emerald-900 opacity-90">Default benchmark: 10.0% merit increase with leadership fast-track consideration.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 space-y-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-200 text-blue-900">Tier 4</span>
                        <div class="text-base font-black text-blue-950">4.0 – 4.9 Exceeds</div>
                        <div class="text-lg font-black font-outfit text-blue-800">+5.0% to +8.0%</div>
                        <p class="text-[11px] text-blue-900 opacity-90">Default benchmark: 6.5% merit increase across professional staff.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 space-y-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-200 text-purple-900">Tier 3 (Standard)</span>
                        <div class="text-base font-black text-purple-950">3.0 – 3.9 Meets</div>
                        <div class="text-lg font-black font-outfit text-purple-800">+2.0% to +5.0%</div>
                        <p class="text-[11px] text-purple-900 opacity-90">Default benchmark: 3.5% cost-of-living and standard performance merit.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-200 text-amber-900">Tier 2</span>
                        <div class="text-base font-black text-amber-950">2.0 – 2.9 Needs Imp.</div>
                        <div class="text-lg font-black font-outfit text-amber-800">+0.0% to +2.0%</div>
                        <p class="text-[11px] text-amber-900 opacity-90">Default benchmark: 1.0% statutory adjustment with development plan.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 space-y-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-200 text-rose-900">Tier 1 (PIP)</span>
                        <div class="text-base font-black text-rose-950">&lt; 2.0 Unsatisfactory</div>
                        <div class="text-lg font-black font-outfit text-rose-800">0.0% Increase</div>
                        <p class="text-[11px] text-rose-900 opacity-90">No raise awarded. Automatically triggers 90-day Performance Improvement Plan (PIP).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: APPROVED ADJUSTMENTS QUEUE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'proposals'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Merit & Promotion Adjustment Records</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Historical and active merit adjustments with financial budget approval statuses.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-400 font-extrabold uppercase">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4 text-right">Old Rate</th>
                                <th class="py-3 px-4 text-right">New Rate</th>
                                <th class="py-3 px-4 text-right">Monthly CTC</th>
                                <th class="py-3 px-4 text-center">Budget Status</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($adjustments as $adj)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div>{{ $adj->display_name }}</div>
                                        <span class="text-gray-400 font-mono text-[11px]">{{ $adj->display_position }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-purple-50 text-purple-800">
                                            {{ strtoupper(str_replace('_', ' ', $adj->type)) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-gray-600">
                                        PHP {{ number_format((float)$adj->old_rate, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-purple-950">
                                        PHP {{ number_format((float)$adj->new_rate, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-800">
                                        PHP {{ number_format((float)($adj->monthly_ctc ?: $adj->new_rate * 1.135), 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900">
                                            {{ $adj->budget_impact_status ?? 'BUDGET_APPROVED' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            {{ ucfirst($adj->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-400 font-bold">
                                        No past merit adjustments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($adjustments->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
