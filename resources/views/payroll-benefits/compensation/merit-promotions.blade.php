@extends('layouts.app')

@php
    $pageTitle = 'Merit & Promotions Planning Desk';
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
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Merit & Promotion Planning Desk</h1>
            <p class="text-xs text-gray-500 mt-1">Calibrate 5-tier merit increases using read-only Team 3 ratings, promotion advancement rules (15%), and compute retroactive pay differentials.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                5-Tier Merit Review Live
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
        activeTab: 'table', {{-- 'table', 'matrix', 'proposals' --}}
        selectedDept: '{{ $deptId ?? 'all' }}',
        retroModalOpen: false,
        promoModalOpen: false,
        activeRetroEmployee: null,
        activePromoEmployee: null,
        
        // Retroactive Form State
        retroNewMonthly: 30000,
        retroEffectiveDate: '{{ date('Y-m-01') }}',
        retroDaysWorked: 13,
        retroResult: null,
        retroLoading: false,

        // Promotion Form State
        promoTargetGradeId: '{{ $salaryGrades->first()?->id ?? 1 }}',
        promoResult: null,
        promoLoading: false,

        employeesData: {{ Js::from($employees->map(function($e) use ($gradeMap) {
            $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
            $currentSalary = (float)($isDriver ? ($e->daily_rate * 26) : ($e->monthly_rate ?: 25000.00));
            $posKey = strtolower(trim($e->position));
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
                'raise_pct' => $suggestedPct,
                'new_salary' => round($currentSalary * (1 + ($suggestedPct / 100)), 2),
                'selected' => true,
            ];
        })) }},

        get filteredEmployees() {
            if (this.selectedDept === 'all' || !this.selectedDept) {
                return this.employeesData;
            }
            return this.employeesData.filter(e => e.department_id == this.selectedDept);
        },

        get totalIncrementalSalary() {
            return this.filteredEmployees.reduce((acc, emp) => {
                if (!emp.selected) return acc;
                return acc + (emp.current_salary * (emp.raise_pct / 100));
            }, 0);
        },

        get totalIncrementalMonthlyCTC() {
            // Incremental Base + ~13.5% employer statutory load
            return this.totalIncrementalSalary * 1.135;
        },

        get totalAnnualFinancialRequisition() {
            // (Monthly CTC x 12) + 13th month liability
            return (this.totalIncrementalMonthlyCTC * 12) + this.totalIncrementalSalary;
        },

        openRetroModal(emp) {
            this.activeRetroEmployee = emp;
            this.retroNewMonthly = Math.round(emp.current_salary * (1 + (emp.raise_pct / 100)));
            this.retroModalOpen = true;
            this.calculateRetroDiff();
        },

        calculateRetroDiff() {
            if (!this.activeRetroEmployee) return;
            this.retroLoading = true;
            fetch('{{ route('compensation.retroactive.calculate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: this.activeRetroEmployee.id,
                    new_monthly_rate: Number(this.retroNewMonthly) || 0,
                    effective_date: this.retroEffectiveDate,
                    days_worked: parseInt(this.retroDaysWorked) || 13
                })
            })
            .then(r => r.json())
            .then(data => {
                this.retroResult = data;
                this.retroLoading = false;
            })
            .catch(() => {
                this.retroLoading = false;
            });
        },

        openPromoModal(emp) {
            this.activePromoEmployee = emp;
            this.promoModalOpen = true;
            this.calculatePromoPreview();
        },

        calculatePromoPreview() {
            if (!this.activePromoEmployee) return;
            this.promoLoading = true;
            fetch('{{ route('compensation.merit-promotions.calculate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: this.activePromoEmployee.id,
                    type: 'promotion',
                    new_grade_id: this.promoTargetGradeId
                })
            })
            .then(r => r.json())
            .then(data => {
                this.promoResult = data;
                this.promoLoading = false;
            })
            .catch(() => {
                this.promoLoading = false;
            });
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Merit Planning Table -->
                <button type="button" @click="activeTab = 'table'" 
                        :class="activeTab === 'table' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Merit Calibrator (5-Tier Matrix)
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $employees->count() }}</span>
                </button>

                <!-- Tab 2: 5-Tier Policy Reference -->
                <button type="button" @click="activeTab = 'matrix'" 
                        :class="activeTab === 'matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    5-Tier Policy Matrix Matrix
                </button>

                <!-- Tab 3: History & Past Proposals -->
                <button type="button" @click="activeTab = 'proposals'" 
                        :class="activeTab === 'proposals' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
            
            <!-- Card 1: Selected Headcount -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Evaluated Headcount</span>
                <div class="text-2xl font-black font-outfit text-gray-900" x-text="filteredEmployees.length + ' Personnel'"></div>
                <p class="text-xs text-gray-500 font-medium">Active merit cycle review</p>
            </div>

            <!-- Card 2: Incremental Monthly Salary -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Monthly Salary Increase</span>
                <div class="text-2xl font-black font-outfit text-purple-900" x-text="'PHP ' + Number(totalIncrementalSalary).toLocaleString(undefined, {minimumFractionDigits: 2})"></div>
                <p class="text-xs text-gray-500 font-medium">Net base pay increment</p>
            </div>

            <!-- Card 3: Incremental Monthly CTC -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Monthly Employer CTC</span>
                <div class="text-2xl font-black font-outfit text-blue-900" x-text="'PHP ' + Number(totalIncrementalMonthlyCTC).toLocaleString(undefined, {minimumFractionDigits: 2})"></div>
                <p class="text-xs text-blue-700 font-bold">Includes SSS, PhilHealth & EC</p>
            </div>

            <!-- Card 4: Team 5 Annual Financial Requisition -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Annual Budget Requisition</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900">
                        Team 5 Sync
                    </span>
                </div>
                <div class="text-2xl font-black font-outfit text-emerald-800" x-text="'PHP ' + Number(totalAnnualFinancialRequisition).toLocaleString(undefined, {minimumFractionDigits: 2})"></div>
                <p class="text-xs text-gray-500 font-medium">Annualized burden + 13th month</p>
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

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-400 font-extrabold uppercase">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Department & Position</th>
                                <th class="py-3 px-4 text-center">Team 3 Rating (Read-Only)</th>
                                <th class="py-3 px-4 text-right">Current Salary</th>
                                <th class="py-3 px-4 text-center w-36">Merit Increase (%)</th>
                                <th class="py-3 px-4 text-right">Proposed Salary</th>
                                <th class="py-3 px-4 text-right">Monthly Incremental CTC</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="emp in filteredEmployees" :key="emp.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div x-text="emp.name"></div>
                                        <span class="text-gray-400 font-mono text-[11px]" x-text="emp.code"></span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-800" x-text="emp.position"></div>
                                        <span class="text-[11px] text-gray-400" x-text="emp.department"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-900': emp.rating === 'Outstanding' || emp.rating === '5.0',
                                                  'bg-blue-100 text-blue-900': emp.rating === 'Very Satisfactory' || emp.rating === '4.5',
                                                  'bg-purple-100 text-purple-900': emp.rating === 'Satisfactory' || emp.rating === '3.5',
                                                  'bg-amber-100 text-amber-900': emp.rating === 'Needs Improvement' || emp.rating === '2.5',
                                                  'bg-rose-100 text-rose-900': emp.rating === 'Unsatisfactory' || emp.rating === '1.5'
                                              }"
                                              x-text="emp.rating">
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP <span x-text="Number(emp.current_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <input type="number" step="0.5" min="0" max="20" x-model="emp.raise_pct"
                                                   class="w-16 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1 text-xs font-black text-center text-purple-950 focus:outline-none focus:border-[#F44336]">
                                            <span class="font-bold text-gray-500">%</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-purple-950">
                                        PHP <span x-text="Number(emp.current_salary * (1 + (emp.raise_pct / 100))).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-emerald-800">
                                        +PHP <span x-text="Number((emp.current_salary * (emp.raise_pct / 100)) * 1.135).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="openRetroModal(emp)" 
                                                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-all">
                                                Retro Pay
                                            </button>
                                            <button type="button" @click="openPromoModal(emp)"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 transition-all">
                                                Compute Promotion Rate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Batch Approval Submission -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <span class="text-xs text-gray-500 font-medium">Ready to commit selected compensation plans into active employee payroll profiles.</span>
                    <form action="{{ route('compensation.merit-promotions.complete') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plans_json" :value="JSON.stringify(filteredEmployees.map(e => ({ ...e, new_salary: Math.min(e.max_salary, Math.round(e.current_salary * (1 + (e.raise_pct / 100)))) })))">
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-6 py-2.5 rounded-xl transition-all shadow-sm">
                            Commit & Submit Batch for Finance Validation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: 5-TIER POLICY MATRIX REFERENCE -->
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

        <!-- ========================================================================= -->
        <!-- MODAL: RETROACTIVE PAY CALCULATOR -->
        <!-- ========================================================================= -->
        <div x-show="retroModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="retroModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Retroactive Pay Calculation Engine</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Daily rate conversion (Daily Rate / 26) and prior rendered days differential.</p>
                    </div>
                    <button @click="retroModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <template x-if="activeRetroEmployee">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 font-bold text-gray-900 flex justify-between">
                            <span x-text="activeRetroEmployee.name + ' (' + activeRetroEmployee.position + ')'"></span>
                            <span class="font-mono text-purple-900" x-text="'Current: PHP ' + Number(activeRetroEmployee.current_salary).toLocaleString()"></span>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1">New Monthly Rate (PHP)</label>
                            <input type="number" step="500" x-model="retroNewMonthly" @input="calculateRetroDiff()"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-black text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1">Prior Days Rendered</label>
                            <input type="number" min="1" max="60" x-model="retroDaysWorked" @input="calculateRetroDiff()"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-black text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Retroactive Effective Date</label>
                        <input type="date" x-model="retroEffectiveDate" @change="calculateRetroDiff()"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <!-- Live Calculation Result Card -->
                    <template x-if="retroResult">
                        <div class="p-4 bg-blue-50/70 rounded-2xl border border-blue-200 space-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <div>
                                    <span class="text-[10px] font-black text-blue-900 uppercase block">Daily Rate Conversion</span>
                                    <div class="font-mono font-bold text-blue-950 mt-0.5">
                                        Old: PHP <span x-text="Number(retroResult.old_daily_rate).toFixed(2)"></span> -> New: PHP <span x-text="Number(retroResult.new_daily_rate).toFixed(2)"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-blue-900 uppercase block">Daily Differential</span>
                                    <div class="font-mono font-black text-blue-900 text-sm">
                                        +PHP <span x-text="Number(retroResult.daily_differential).toFixed(2)"></span> / day
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-blue-950 text-white rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-blue-200">Total Retroactive Differential Pay:</span>
                                <span class="text-xl font-black font-outfit text-blue-100"
                                      x-text="'PHP ' + Number(retroResult.retroactive_pay).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                </span>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-end pt-2">
                        <button type="button" @click="retroModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                            Close Calculator
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: PROMOTION ADVANCEMENT CALCULATOR -->
        <!-- ========================================================================= -->
        <div x-show="promoModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="promoModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Promotion Salary & CTC Calibrator (Team 3 Integration)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Calculates financial compensation floor for approved promotion: MAX(New Grade Min, Current Salary x 1.15).</p>
                    </div>
                    <button @click="promoModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <template x-if="activePromoEmployee">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 font-bold text-gray-900 flex justify-between">
                            <span x-text="activePromoEmployee.name + ' (' + activePromoEmployee.position + ')'"></span>
                            <span class="font-mono text-purple-900" x-text="'Current: PHP ' + Number(activePromoEmployee.current_salary).toLocaleString()"></span>
                        </div>
                    </template>

                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Target Higher Pay Grade</label>
                        <select x-model="promoTargetGradeId" @change="calculatePromoPreview()"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            @foreach($salaryGrades as $sg)
                                <option value="{{ $sg->id }}">
                                    {{ $sg->grade_code ?? ('PG-' . $loop->iteration) }} — {{ $sg->position_name }} (PHP {{ number_format((float)$sg->min_salary, 0) }} - PHP {{ number_format((float)$sg->max_salary, 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Live Promotion Preview -->
                    <template x-if="promoResult">
                        <div class="p-4 bg-purple-50/70 rounded-2xl border border-purple-200 space-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <div>
                                    <span class="text-[10px] font-black text-purple-900 uppercase block">15% Standard Raise Floor</span>
                                    <div class="font-mono font-bold text-purple-950 mt-0.5">
                                        PHP <span x-text="Number(promoResult.fifteen_percent_floor).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-purple-900 uppercase block">New Grade Minimum</span>
                                    <div class="font-mono font-bold text-purple-950 mt-0.5">
                                        PHP <span x-text="Number(promoResult.new_grade_min).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-purple-950 text-white rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-purple-200">Promoted Starting Salary:</span>
                                <span class="text-xl font-black font-outfit text-purple-100"
                                      x-text="'PHP ' + Number(promoResult.promoted_salary).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                </span>
                            </div>

                            <div class="p-2.5 bg-white rounded-xl border border-purple-100 font-mono text-[11px] text-gray-800"
                                 x-text="promoResult.formula">
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-end pt-2">
                        <button type="button" @click="promoModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
