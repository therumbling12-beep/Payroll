@extends('layouts.app')

@php
    $pageTitle = 'Flexible Compensation & Starting Salary Determination';
    $currentPage = 'compensation.salary-bands';

    $minFloor = $salaryGrades->min('min_salary') ?? 19630.00;
    $maxCeiling = $salaryGrades->max('max_salary') ?? 350000.00;
    $avgGrowth = $salaryGrades->avg('annual_growth_rate') ?? 7.5;
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Flexible Compensation & Starting Salary Determination</h1>
            <p class="text-xs text-gray-500 mt-1">Locality minimum wage floor (₱{{ number_format($localityMinimumWage, 2) }}/day), 6-factor candidate starting salary scoring, and flexible direct merit increase adjustments (docs/no.md §1).</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Flexible Pay Scale Active
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
        activeTab: 'scale',
        editModalOpen: false,
        calcModalOpen: false,
        meritModalOpen: false,
        activeGrade: { id: null, position: '', code: '', level: '', min: 0, max: 0, mid: 0, growth: 0, effectivity: '' },
        
        // 6-Factor Candidate Salary Determination State (docs/no.md Lines 25-35)
        calcGradeId: '{{ $salaryGrades->first()?->id ?? 1 }}',
        expScore: 2,
        skillScore: 3,
        eduScore: 3,
        certScore: 3,
        prevSalaryScore: 3,
        interviewScore: 4,
        calcResult: null,
        calcLoading: false,

        // Direct Merit Increase Modal State (docs/no.md Line 51)
        selectedEmp: { id: null, name: '', code: '', position: '', daily: 0, monthly: 0 },
        meritDailyRate: 0,
        meritMonthlyRate: 0,
        meritPercentage: 5.0,
        meritJustification: '',

        openEdit(id, code, level, position, min, max, growth, effectivity) {
            this.activeGrade = {
                id: id,
                code: code || 'PG-1',
                level: level || 'Entry Level',
                position: position,
                min: parseFloat(min),
                max: parseFloat(max),
                mid: (parseFloat(min) + parseFloat(max)) / 2,
                growth: parseFloat(growth || 5),
                effectivity: effectivity || '{{ date('Y-m-d') }}'
            };
            this.editModalOpen = true;
        },
        recalcMid() {
            this.activeGrade.mid = (parseFloat(this.activeGrade.min || 0) + parseFloat(this.activeGrade.max || 0)) / 2;
        },
        openCalculator(gradeId = null) {
            if (gradeId) {
                this.calcGradeId = gradeId;
            }
            this.calcModalOpen = true;
            this.evaluateCandidateSalary();
        },
        evaluateCandidateSalary() {
            this.calcLoading = true;
            fetch('{{ route('compensation.salary-determination') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    salary_grade_id: this.calcGradeId,
                    experience: parseInt(this.expScore),
                    skills: parseInt(this.skillScore),
                    education: parseInt(this.eduScore),
                    certifications: parseInt(this.certScore),
                    previous_salary: parseInt(this.prevSalaryScore),
                    interview_performance: parseInt(this.interviewScore)
                })
            })
            .then(r => r.json())
            .then(data => {
                this.calcResult = data;
                this.calcLoading = false;
            })
            .catch(() => {
                this.calcLoading = false;
            });
        },
        openDirectMerit(id, name, code, position, daily, monthly) {
            var dailyRate = parseFloat(daily || 755.00);
            var monthlyRate = parseFloat(monthly || (dailyRate * 26.0));
            this.selectedEmp = {
                id: id,
                name: name,
                code: code,
                position: position || 'Staff',
                daily: dailyRate,
                monthly: monthlyRate
            };
            this.meritPercentage = 5.0;
            this.meritDailyRate = (dailyRate * 1.05).toFixed(2);
            this.meritMonthlyRate = (this.meritDailyRate * 26.0).toFixed(2);
            this.meritJustification = 'Quarterly performance and merit adjustment';
            this.meritModalOpen = true;
        },
        recalcMeritRates() {
            if (this.meritPercentage > 0 && this.selectedEmp.daily > 0) {
                this.meritDailyRate = (this.selectedEmp.daily * (1 + (parseFloat(this.meritPercentage) / 100))).toFixed(2);
                this.meritMonthlyRate = (this.meritDailyRate * 26.0).toFixed(2);
            }
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Market Benchmark Scales -->
                <button type="button" @click="activeTab = 'scale'" 
                        :class="activeTab === 'scale' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" :class="activeTab === 'scale' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Market Benchmark Reference Scales
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $salaryGrades->count() }} Scales</span>
                </button>

                <!-- Tab 2: Personnel Range Visualizer -->
                <button type="button" @click="activeTab = 'roster'" 
                        :class="activeTab === 'roster' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" :class="activeTab === 'roster' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Personnel Distribution Visualizer
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $employees->total() }}</span>
                </button>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="button" @click="openCalculator()" 
                        class="bg-purple-900 hover:bg-purple-950 text-white text-xs font-black px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    6-Factor Candidate Calculator
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: PG-1 TO PG-9 SALARY SCALE & CALIBRATION MATRIX -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'scale'" x-transition class="space-y-6">

            <!-- Executive KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Job Grades Count -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Defined Grades</span>
                        <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit text-gray-900">
                        {{ $salaryGrades->count() }} Pay Grades
                    </div>
                    <p class="text-xs text-gray-500 font-medium">PG-1 (Entry Level) to PG-9 (C-Suite)</p>
                </div>

                <!-- Card 2: Minimum Floor Base -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Locality Minimum Wage</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit text-gray-900">
                        PHP {{ number_format($localityMinimumWage ?? 755.00, 2) }} <span class="text-xs text-gray-400 font-bold">/ day</span>
                    </div>
                    <p class="text-xs text-emerald-700 font-bold">
                        PHP {{ number_format($localityMonthlyFloor ?? (($localityMinimumWage ?? 755.00) * 26.0), 2) }} / mo statutory baseline (docs/no.md §1)
                    </p>
                </div>

                <!-- Card 3: Executive Ceiling -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">C-Suite Ceiling (PG-9)</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit text-gray-900">
                        PHP {{ number_format($maxCeiling, 2) }}
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Chief Executive & President</p>
                </div>

                <!-- Card 4: Average Annual Growth -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Average Growth Rate</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit text-emerald-700">
                        +{{ number_format($avgGrowth, 1) }}% / yr
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Tenure escalation benchmark</p>
                </div>

            </div>

            <!-- Master Salary Grade Scale Table with Visual Spread Bars -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Market Benchmark Reference Scales</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Reference salary benchmarks for market positioning. Starting salaries are determined flexibly via the 6-factor candidate scoring engine anchored on the locality minimum wage.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Grade & Level</th>
                                <th class="py-3.5 px-4">Position Scope & Samples</th>
                                <th class="py-3.5 px-4 text-right">Band Minimum</th>
                                <th class="py-3.5 px-4 text-center w-48">Band Spread & Midpoint</th>
                                <th class="py-3.5 px-4 text-right">Band Maximum</th>
                                <th class="py-3.5 px-4 text-center">Annual Growth</th>
                                <th class="py-3.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @foreach($salaryGrades as $grade)
                                @php
                                    $min = (float) $grade->min_salary;
                                    $max = (float) $grade->max_salary;
                                    $mid = ($min + $max) / 2;
                                    $p25 = $min + (0.25 * ($max - $min));
                                    $p75 = $min + (0.75 * ($max - $min));
                                    $isCompliant = $grade->isMinimumWageCompliant();
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-4 px-4 font-black text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-lg bg-gray-900 text-white font-mono text-xs font-extrabold shadow-2xs">
                                                {{ $grade->grade_code ?? ('PG-' . $loop->iteration) }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-purple-50 text-purple-800 border border-purple-200">
                                                {{ $grade->job_level ?? 'Standard' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-black text-sm text-gray-900">{{ $grade->position_name }}</div>
                                        <span class="text-[11px] text-gray-500 font-medium block mt-0.5">
                                            {{ $grade->sample_positions ?? 'Standard organizational roles' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right font-extrabold font-outfit text-gray-700 text-sm">
                                        PHP {{ number_format($min, 2) }}
                                        @if($isCompliant)
                                            <span class="text-[10px] font-bold text-emerald-700 block">NCR-27 Valid</span>
                                        @else
                                            <span class="text-[10px] font-bold text-rose-600 block">Below Floor</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-[10px] text-gray-400 font-mono">
                                                <span>25%: {{ number_format($p25 / 1000, 1) }}k</span>
                                                <span class="font-bold text-blue-700">Mid: {{ number_format($mid / 1000, 1) }}k</span>
                                                <span>75%: {{ number_format($p75 / 1000, 1) }}k</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden flex">
                                                <div class="bg-blue-300 h-2 w-1/4"></div>
                                                <div class="bg-blue-600 h-2 w-1/4"></div>
                                                <div class="bg-blue-600 h-2 w-1/4"></div>
                                                <div class="bg-purple-400 h-2 w-1/4"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black font-outfit text-gray-900 text-sm">
                                        PHP {{ number_format($max, 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-extrabold text-emerald-700 font-mono text-xs">
                                        +{{ number_format((float)$grade->annual_growth_rate, 1) }}% / yr
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="openCalculator({{ $grade->id }})"
                                                    class="text-xs font-bold text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-2.5 py-1.5 rounded-xl transition-all">
                                                Score
                                            </button>
                                            <button type="button" @click="openEdit({{ $grade->id }}, '{{ $grade->grade_code }}', '{{ $grade->job_level ?? 'Standard' }}', '{{ addslashes($grade->position_name) }}', {{ (float)$grade->min_salary }}, {{ (float)$grade->max_salary }}, {{ (float)($grade->annual_growth_rate ?? 5) }}, '{{ $grade->effectivity_date ? $grade->effectivity_date->format('Y-m-d') : date('Y-m-d') }}')" 
                                                    class="text-xs font-extrabold text-white bg-[#F44336] hover:bg-[#D32F2F] px-3 py-1.5 rounded-xl transition-all shadow-2xs">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: PERSONNEL DISTRIBUTION VISUALIZER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'roster'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Personnel Salary Distribution Across Bands</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Compa-ratio and range penetration visualizer for active personnel.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-400 font-extrabold uppercase">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Position</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-right">Current Salary</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($employees as $emp)
                                @php
                                    $isDriver = str_contains(strtolower($emp->position ?? ''), 'driver');
                                    $salary = (float) ($emp->monthly_rate ?: ($emp->daily_rate ? $emp->daily_rate * 26 : ($isDriver ? 28000.00 : 25000.00)));
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div>{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                        <span class="text-gray-400 font-mono text-xs">{{ $emp->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-700">{{ $emp->position }}</td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-800 font-bold text-[11px]">
                                            {{ $emp->department?->name ?? 'General' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP {{ number_format($salary, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            {{ ucfirst($emp->employment_status ?? 'regular') }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button type="button" @click="openDirectMerit({{ $emp->id }}, '{{ addslashes($emp->first_name . ' ' . $emp->last_name) }}', '{{ $emp->employee_code }}', '{{ addslashes($emp->position ?? 'Staff') }}', {{ (float)($emp->daily_rate ?? 755.00) }}, {{ (float)($emp->monthly_rate ?? 19630.00) }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 font-bold text-[11px] transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            </svg>
                                            Direct Merit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($employees->hasPages())
                    <div class="pt-4 border-t border-gray-100">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: 6-FACTOR CANDIDATE SALARY DETERMINATION CALCULATOR (docs/no.md Lines 25-35) -->
        <!-- ========================================================================= -->
        <div x-show="calcModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="calcModalOpen = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">6-Factor Candidate Salary Determination Calculator</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Weighted candidate scoring anchored to the ₱755.00/day minimum wage floor (docs/no.md §1).</p>
                    </div>
                    <button @click="calcModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Grade Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Target Position / Benchmark Scale</label>
                        <select x-model="calcGradeId" @change="evaluateCandidateSalary()"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            @foreach($salaryGrades as $g)
                                <option value="{{ $g->id }}">
                                    {{ $g->grade_code ?? ('PG-' . $loop->iteration) }} — {{ $g->position_name }} (PHP {{ number_format((float)$g->min_salary, 0) }} - PHP {{ number_format((float)$g->max_salary, 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 6 Factors Grid (docs/no.md Lines 28-34) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                        <!-- Factor 1: Relevant Experience (25%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>1. Relevant Experience (25%)</span>
                                <span class="text-purple-800 font-mono" x-text="expScore + '/6'"></span>
                            </div>
                            <select x-model="expScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Entry (0 to 1 Year)</option>
                                <option value="2">2 - Junior (1 to 2 Years)</option>
                                <option value="3">3 - Intermediate (2 to 4 Years)</option>
                                <option value="4">4 - Experienced (4 to 7 Years)</option>
                                <option value="5">5 - Senior (7 to 10 Years)</option>
                                <option value="6">6 - Veteran Lead (10+ Years)</option>
                            </select>
                        </div>

                        <!-- Factor 2: Technical & Job Skills (20%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>2. Technical & Job Skills (20%)</span>
                                <span class="text-purple-800 font-mono" x-text="skillScore + '/6'"></span>
                            </div>
                            <select x-model="skillScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Foundational knowledge</option>
                                <option value="2">2 - Basic functional skills</option>
                                <option value="3">3 - Standard proficient skills</option>
                                <option value="4">4 - Advanced domain competencies</option>
                                <option value="5">5 - Specialized mastery</option>
                                <option value="6">6 - Elite subject matter expert</option>
                            </select>
                        </div>

                        <!-- Factor 3: Educational Attainment (15%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>3. Educational Attainment (15%)</span>
                                <span class="text-purple-800 font-mono" x-text="eduScore + '/6'"></span>
                            </div>
                            <select x-model="eduScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - High School Graduate</option>
                                <option value="2">2 - Vocational / TESDA NC II</option>
                                <option value="3">3 - College Graduate / Bachelor's</option>
                                <option value="4">4 - Bachelor with Honors</option>
                                <option value="5">5 - Master's Degree</option>
                                <option value="6">6 - Post-Graduate / Doctorate</option>
                            </select>
                        </div>

                        <!-- Factor 4: Professional Certifications (15%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>4. Professional Certifications (15%)</span>
                                <span class="text-purple-800 font-mono" x-text="certScore + '/6'"></span>
                            </div>
                            <select x-model="certScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - No external certifications</option>
                                <option value="2">2 - Basic safety / local certification</option>
                                <option value="3">3 - Industry accredited certification</option>
                                <option value="4">4 - Multiple professional credentials</option>
                                <option value="5">5 - Advanced specialized master license</option>
                                <option value="6">6 - Top-tier international credentials</option>
                            </select>
                        </div>

                        <!-- Factor 5: Previous Salary Benchmark (15%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>5. Previous Salary Benchmark (15%)</span>
                                <span class="text-purple-800 font-mono" x-text="prevSalaryScore + '/6'"></span>
                            </div>
                            <select x-model="prevSalaryScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Baseline minimum wage previous pay</option>
                                <option value="2">2 - Modest entry rate</option>
                                <option value="3">3 - Median market rate benchmark</option>
                                <option value="4">4 - Above-average historical rate</option>
                                <option value="5">5 - Premium historical package</option>
                                <option value="6">6 - High executive benchmark</option>
                            </select>
                        </div>

                        <!-- Factor 6: Interview Assessment (10%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>6. Interview Assessment (10%)</span>
                                <span class="text-purple-800 font-mono" x-text="interviewScore + '/6'"></span>
                            </div>
                            <select x-model="interviewScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Marginal pass</option>
                                <option value="2">2 - Satisfactory responses</option>
                                <option value="3">3 - Solid cultural & technical fit</option>
                                <option value="4">4 - Strong communicator & problem solver</option>
                                <option value="5">5 - Outstanding leadership presence</option>
                                <option value="6">6 - Exceptional visionary performance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Live Calculation Result Card -->
                    <template x-if="calcResult">
                        <div class="p-4 bg-purple-50/60 rounded-2xl border border-purple-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black text-purple-900 uppercase block tracking-wider">Candidate Score & Placement</span>
                                    <div class="text-lg font-black font-outfit text-purple-950 mt-0.5 flex items-center gap-2">
                                        <span x-text="calcResult.total_score + ' / 6.00'"></span>
                                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-purple-200 text-purple-900 font-bold" x-text="calcResult.placement_label"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-gray-400 uppercase block">DOLE Guard</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="calcResult.minimum_wage_guard.is_compliant ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                          x-text="calcResult.minimum_wage_guard.status">
                                    </span>
                                </div>
                            </div>

                            <!-- Formula Box -->
                            <div class="p-2.5 bg-white rounded-xl border border-purple-100 font-mono text-[11px] text-gray-800"
                                 x-text="calcResult.formula">
                            </div>

                            <!-- Recommended Salary Banner -->
                            <div class="p-3 bg-purple-950 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold text-purple-300 block uppercase">Recommended Starting Offer:</span>
                                    <span class="text-xl font-black font-outfit text-white"
                                          x-text="'PHP ' + Number(calcResult.recommended_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' /mo'">
                                    </span>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[10px] font-bold text-purple-300 block uppercase">Daily Rate Floor:</span>
                                    <span class="text-sm font-bold font-mono text-emerald-300"
                                          x-text="'PHP ' + Number(calcResult.recommended_daily_rate).toLocaleString(undefined, {minimumFractionDigits: 2}) + '/day'">
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-end pt-2">
                        <button type="button" @click="calcModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                            Close Calculator
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: EDIT PAY SCALE BENCHMARK -->
        <!-- ========================================================================= -->
        <div x-show="editModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="editModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Edit Market Benchmark Reference Scale</h3>
                    <button @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form :action="'{{ route('compensation.salary-bands.update', ['grade' => '__ID__']) }}'.replace('__ID__', activeGrade.id)" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pay Grade & Position</label>
                        <input type="text" :value="activeGrade.code + ' - ' + activeGrade.position" readonly 
                               class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-600 cursor-not-allowed">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Band Minimum (PHP)</label>
                            <input type="number" step="100" name="min_salary" x-model="activeGrade.min" @input="recalcMid()" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Band Maximum (PHP)</label>
                            <input type="number" step="100" name="max_salary" x-model="activeGrade.max" @input="recalcMid()" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 flex justify-between items-center font-bold">
                        <span class="text-blue-900">Calculated Midpoint:</span>
                        <span class="font-outfit text-blue-950 text-sm font-black" x-text="'PHP ' + Number(activeGrade.mid).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Annual Growth Rate (%)</label>
                            <input type="number" step="0.1" name="annual_growth_rate" x-model="activeGrade.growth"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Effectivity Date</label>
                            <input type="date" name="effectivity_date" x-model="activeGrade.effectivity"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="editModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: DIRECT MERIT INCREASE (docs/no.md Line 51) -->
        <!-- ========================================================================= -->
        <div x-show="meritModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="meritModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Direct Merit Increase (No Promotion Needed)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Flexible merit adjustment based on performance and loyalty (docs/no.md §1).</p>
                    </div>
                    <button @click="meritModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form :action="'{{ route('compensation.direct-merit', ['employee' => '__ID__']) }}'.replace('__ID__', selectedEmp.id)" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Target Personnel</label>
                        <div class="p-3 bg-purple-50 rounded-2xl border border-purple-100 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-gray-900 text-sm block" x-text="selectedEmp.name"></span>
                                <span class="text-gray-500 font-mono text-[11px]" x-text="selectedEmp.code + ' • ' + selectedEmp.position"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-gray-400 block">Current Base</span>
                                <span class="font-mono font-black text-purple-900" x-text="'PHP ' + Number(selectedEmp.daily).toLocaleString(undefined, {minimumFractionDigits: 2}) + '/day'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Merit Percentage (%)</label>
                            <input type="number" step="0.5" min="0.1" max="100" x-model="meritPercentage" @input="recalcMeritRates()"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">New Daily Rate (PHP)</label>
                            <input type="number" step="0.01" min="755.00" name="new_daily_rate" x-model="meritDailyRate" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 flex justify-between items-center font-bold">
                        <span class="text-emerald-900 text-xs">Equivalent Monthly Salary (26-day basis):</span>
                        <span class="font-outfit text-emerald-950 font-black text-sm" x-text="'PHP ' + Number(meritDailyRate * 26).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Merit Justification / Performance Notes</label>
                        <textarea name="justification" x-model="meritJustification" rows="2" required
                                  placeholder="e.g. Excellent safety score, exceptional attendance and zero incidents..."
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs font-medium text-gray-900 focus:outline-none focus:border-[#F44336]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="meritModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Apply Merit Increase
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
