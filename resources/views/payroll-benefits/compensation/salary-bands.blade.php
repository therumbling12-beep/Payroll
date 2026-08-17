@extends('layouts.app')

@php
    $pageTitle = 'Salary Band Management & Candidate Determination';
    $currentPage = 'compensation.salary-bands';

    $minFloor = $salaryGrades->min('min_salary') ?? 19630.00;
    $maxCeiling = $salaryGrades->max('max_salary') ?? 350000.00;
    $avgGrowth = $salaryGrades->avg('annual_growth_rate') ?? 7.5;
@endphp

@push('styles')
<style>
    .comp-bar-track {
        background-color: #e2e8f0;
        height: 10px;
        border-radius: 9999px;
        position: relative;
    }
    .penetration-dot-green {
        position: absolute;
        top: -3px;
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background-color: #059669;
        border: 2.5px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        transform: translateX(-50%);
    }
    .penetration-dot-red {
        position: absolute;
        top: -3px;
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background-color: #e11d48;
        border: 2.5px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        transform: translateX(-50%);
    }
    .penetration-dot-orange {
        position: absolute;
        top: -3px;
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background-color: #d97706;
        border: 2.5px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        transform: translateX(-50%);
    }
</style>
@endpush

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Salary Band Management & Determination</h1>
            <p class="text-xs text-gray-500 mt-1">Manage official PG-1 to PG-9 hierarchy, DOLE NCR-27 minimum wage compliance guards, and 5-factor weighted candidate salary scoring.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Salary Scale Active (PG-1 to PG-9)
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
        bulkModalOpen: false,
        calcModalOpen: false,
        activeGrade: { id: null, position: '', code: '', level: '', min: 0, max: 0, mid: 0, growth: 0, effectivity: '' },
        bulkPercentage: 5.0,
        
        // 5-Factor Candidate Salary Determination State
        calcGradeId: '{{ $salaryGrades->first()?->id ?? 1 }}',
        eduScore: 3,
        expScore: 2,
        skillScore: 3,
        marketScore: 3,
        equityScore: 3,
        calcResult: null,
        calcLoading: false,

        openEdit(grade) {
            this.activeGrade = {
                id: grade.id,
                code: grade.grade_code || 'PG-1',
                level: grade.job_level || 'Entry Level',
                position: grade.position_name,
                min: parseFloat(grade.min_salary),
                max: parseFloat(grade.max_salary),
                mid: (parseFloat(grade.min_salary) + parseFloat(grade.max_salary)) / 2,
                growth: parseFloat(grade.annual_growth_rate || 5),
                effectivity: grade.effectivity_date ? grade.effectivity_date.split('T')[0] : '{{ date('Y-m-d') }}'
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
                    education: parseInt(this.eduScore),
                    experience: parseInt(this.expScore),
                    skills: parseInt(this.skillScore),
                    market_benchmark: parseInt(this.marketScore),
                    internal_equity: parseInt(this.equityScore)
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
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Master Grade Scale -->
                <button type="button" @click="activeTab = 'scale'" 
                        :class="activeTab === 'scale' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" :class="activeTab === 'scale' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    PG-1 to PG-9 Hierarchy & Calibration
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $salaryGrades->count() }} Grades</span>
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

                <!-- Tab 3: History & Audit Log -->
                <button type="button" @click="activeTab = 'history'" 
                        :class="activeTab === 'history' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" :class="activeTab === 'history' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Calibration History Log
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $bandHistory->count() }}</span>
                </button>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="button" @click="openCalculator()" 
                        class="bg-purple-900 hover:bg-purple-950 text-white text-xs font-black px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    5-Factor Candidate Calculator
                </button>

                <button type="button" @click="bulkModalOpen = true" 
                        class="bg-[#1c1c1e] hover:bg-black text-white text-xs font-extrabold px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Bulk Annual Adjustment (%)
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
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Statutory Floor (PG-1)</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit text-gray-900">
                        PHP {{ number_format($minFloor, 2) }}
                    </div>
                    <p class="text-xs text-emerald-700 font-bold">DOLE NCR-27 Compliant (PHP 755/day)</p>
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
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Official Company Salary Scale (PG-1 to PG-9)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Defined minimum base, calculated 25th / 50th / 75th percentiles, and maximum ceilings across all organizational levels.</p>
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
                                            <button type="button" @click="openEdit({{ Js::from($grade) }})" 
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
        <!-- TAB 3: CALIBRATION HISTORY LOG -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'history'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Salary Band Calibration Audit Log</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Historical record of band modifications and bulk annual adjustments.</p>
                </div>

                <div class="divide-y divide-gray-100 text-xs">
                    @forelse($bandHistory as $log)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-gray-900">{{ $log->action }}</span>
                                <p class="text-gray-500 text-[11px]">By {{ $log->user_name }} • IP: {{ $log->ip_address }}</p>
                            </div>
                            <span class="font-mono text-gray-400 text-xs">{{ $log->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400 font-bold">
                            No calibration history logged yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: 5-FACTOR CANDIDATE SALARY DETERMINATION CALCULATOR -->
        <!-- ========================================================================= -->
        <div x-show="calcModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="calcModalOpen = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">5-Factor Candidate Salary Determination Calculator</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Standardized weighted candidate scoring and band percentile mapper.</p>
                    </div>
                    <button @click="calcModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Grade Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Target Pay Grade / Position</label>
                        <select x-model="calcGradeId" @change="evaluateCandidateSalary()"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            @foreach($salaryGrades as $g)
                                <option value="{{ $g->id }}">
                                    {{ $g->grade_code ?? ('PG-' . $loop->iteration) }} — {{ $g->position_name }} (PHP {{ number_format((float)$g->min_salary, 0) }} - PHP {{ number_format((float)$g->max_salary, 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 5 Factors Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                        <!-- Factor 1: Education (25%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>1. Education (25% Weight)</span>
                                <span class="text-purple-800 font-mono" x-text="eduScore + '/6'"></span>
                            </div>
                            <select x-model="eduScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - High School Graduate</option>
                                <option value="2">2 - Vocational / TESDA NC II</option>
                                <option value="3">3 - College Graduate</option>
                                <option value="4">4 - Bachelor with Honors</option>
                                <option value="5">5 - Master's Degree</option>
                                <option value="6">6 - Doctoral Degree</option>
                            </select>
                        </div>

                        <!-- Factor 2: Experience (35%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>2. Experience (35% Weight)</span>
                                <span class="text-purple-800 font-mono" x-text="expScore + '/6'"></span>
                            </div>
                            <select x-model="expScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - 0 to 1 Year</option>
                                <option value="2">2 - 1 to 3 Years</option>
                                <option value="3">3 - 3 to 5 Years</option>
                                <option value="4">4 - 5 to 8 Years</option>
                                <option value="5">5 - 8 to 12 Years</option>
                                <option value="6">6 - 12+ Years</option>
                            </select>
                        </div>

                        <!-- Factor 3: Relevant Skills (20%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>3. Skills Fit (20% Weight)</span>
                                <span class="text-purple-800 font-mono" x-text="skillScore + '/6'"></span>
                            </div>
                            <select x-model="skillScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Entry / Foundational</option>
                                <option value="2">2 - Basic Competencies</option>
                                <option value="3">3 - Standard Competency</option>
                                <option value="4">4 - Proficient Practitioner</option>
                                <option value="5">5 - Advanced Specialist</option>
                                <option value="6">6 - Subject Matter Expert</option>
                            </select>
                        </div>

                        <!-- Factor 4: Market Benchmark (10%) -->
                        <div>
                            <div class="flex justify-between font-bold mb-1">
                                <span>4. Market Rate (10% Weight)</span>
                                <span class="text-purple-800 font-mono" x-text="marketScore + '/6'"></span>
                            </div>
                            <select x-model="marketScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Below Market Floor</option>
                                <option value="2">2 - Low Market Tier</option>
                                <option value="3">3 - Standard Market Rate</option>
                                <option value="4">4 - Competitive Rate</option>
                                <option value="5">5 - High Demand Skillset</option>
                                <option value="6">6 - Premium Market Leader</option>
                            </select>
                        </div>

                        <!-- Factor 5: Internal Equity (10%) -->
                        <div class="sm:col-span-2">
                            <div class="flex justify-between font-bold mb-1">
                                <span>5. Internal Peer Equity (10% Weight)</span>
                                <span class="text-purple-800 font-mono" x-text="equityScore + '/6'"></span>
                            </div>
                            <select x-model="equityScore" @change="evaluateCandidateSalary()"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-800">
                                <option value="1">1 - Aligns with Band Minimum incumbents</option>
                                <option value="2">2 - Aligns with 25th percentile incumbents</option>
                                <option value="3">3 - Aligns with Midpoint incumbents</option>
                                <option value="4">4 - Aligns with Senior incumbents in band</option>
                                <option value="5">5 - Aligns with Top-tier incumbents</option>
                                <option value="6">6 - Exceptional placement justification</option>
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
                            <div class="p-3 bg-purple-950 text-white rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-purple-200">Recommended Starting Offer:</span>
                                <span class="text-xl font-black font-outfit text-purple-100"
                                      x-text="'PHP ' + Number(calcResult.recommended_salary).toLocaleString(undefined, {minimumFractionDigits: 2})">
                                </span>
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
        <!-- MODAL: EDIT SALARY BAND -->
        <!-- ========================================================================= -->
        <div x-show="editModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="editModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Edit Salary Band Configuration</h3>
                    <button @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form :action="'{{ url('/compensation/salary-bands') }}/' + activeGrade.id + '/update'" method="POST" class="space-y-4 text-xs">
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

        <!-- ========================================================================= -->
        <!-- MODAL: BULK BAND INFLATION ADJUSTMENT -->
        <!-- ========================================================================= -->
        <div x-show="bulkModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="bulkModalOpen = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Bulk Annual Band Inflation Adjustment</h3>
                    <button @click="bulkModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <form action="{{ route('compensation.salary-bands.bulk-adjust') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <p class="text-gray-600">
                        Apply a company-wide percentage increase across all salary band minimums and maximums (e.g. for statutory minimum wage inflation).
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Adjustment Percentage (%)</label>
                        <input type="number" step="0.1" min="0.1" max="50" name="percentage" x-model="bulkPercentage" required
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-black text-gray-900 text-base focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="bulkModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">
                            Apply Bulk Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
