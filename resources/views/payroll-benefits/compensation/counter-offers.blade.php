@extends('layouts.app')

@php
    $pageTitle = 'Counter Offers & Offer Packages Desk';
    $currentPage = 'compensation.counter-offers';

    // Map salary grades for quick lookup
    $gradeMap = [];
    foreach ($salaryGrades as $sg) {
        $gradeMap[strtolower(trim($sg->position_name))] = $sg;
    }
@endphp

@push('styles')
<style>
    .comp-bar-track {
        background-color: #e2e8f0;
        height: 10px;
        border-radius: 9999px;
        position: relative;
    }
    .penetration-dot-brand {
        position: absolute;
        top: -3px;
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background-color: #F44336;
        border: 2.5px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        transform: translateX(-50%);
    }
</style>
@endpush

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Counter Offers & Applicant Offer Desk</h1>
            <p class="text-xs text-gray-500 mt-1">Formulate credential-based salary packages (Mode A), itemized Total Cost to Company (CTC) packages (Mode B), and enforce internal wage distortion guards.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Retention & Offer Desk Active
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

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Component with Clean Tabbed Wizard Architecture -->
    <div x-data="{
        mainTab: 'wizard', {{-- 'wizard' or 'queue' --}}
        wizardStep: 1, {{-- 1: Target, 2: Package, 3: Review --}}
        
        subjectType: 'employee', {{-- 'employee' or 'applicant' --}}
        mode: 'mode_a', {{-- 'mode_a' (Auto) or 'mode_b' (Manual) --}}
        selectedEmployeeId: '{{ $employees->first()?->id ?? '' }}',
        applicantName: 'Juan Dela Cruz',
        applicantPosition: 'Operations Dispatcher',
        selectedGradeId: '{{ $salaryGrades->first()?->id ?? 1 }}',
        competitorCompany: 'Grab / Competitor Fleet',
        competitorOffer: 35000,
        expectedSalary: 35000,
        
        // Mode A Factors
        education: 3,
        experience: 2,
        skills: 3,
        marketBenchmark: 3,
        internalEquity: 3,

        // Mode B Components
        basicSalary: 32000,
        transportAllowance: 1500,
        mealAllowance: 1500,
        commsAllowance: 500,
        signingBonus: 5000,
        hmoTier: 'Individual',
        urgencyDays: 5,
        reason: 'Counter offer formulated to retain key operational talent against competitor market offer.',

        // Live Calculated Results
        calculationResult: null,
        calculationLoading: false,
        breakdownModalOpen: false,
        activeBreakdownItem: null,

        employeesData: {{ Js::from($employees->mapWithKeys(function($e) {
            $isDriver = str_contains(strtolower($e->position ?? ''), 'driver');
            $salary = (float)($isDriver ? ($e->daily_rate * 26) : $e->monthly_rate);
            return [$e->id => [
                'id' => $e->id,
                'name' => $e->first_name . ' ' . $e->last_name,
                'code' => $e->employee_code,
                'position' => $e->position,
                'department' => $e->department?->name ?? 'Operations',
                'salary' => $salary,
                'rating' => $e->performance_rating ?? 'Satisfactory',
                'initials' => strtoupper(substr($e->first_name, 0, 1) . substr($e->last_name, 0, 1)),
            ]];
        })) }},

        salaryGradesData: {{ Js::from($salaryGrades->mapWithKeys(function($sg) {
            return [$sg->id => [
                'id' => $sg->id,
                'code' => $sg->grade_code,
                'level' => $sg->job_level,
                'position' => $sg->position_name,
                'min' => (float)$sg->min_salary,
                'max' => (float)$sg->max_salary,
                'mid' => (float)(($sg->min_salary + $sg->max_salary) / 2),
                'growth' => (float)$sg->annual_growth_rate,
            ]];
        })) }},

        get currentSubject() {
            if (this.subjectType === 'employee') {
                return this.employeesData[this.selectedEmployeeId] || {
                    name: 'Select Employee',
                    code: 'EMP-0000',
                    position: 'Staff',
                    department: 'Operations',
                    salary: 25000,
                    rating: 'Satisfactory',
                    initials: 'EM'
                };
            }
            return {
                name: this.applicantName || 'Applicant Candidate',
                code: 'CANDIDATE',
                position: this.applicantPosition || 'Operations Dispatcher',
                department: 'External Recruitment',
                salary: 0,
                rating: 'Satisfactory',
                initials: 'AP'
            };
        },

        get currentGrade() {
            if (this.salaryGradesData[this.selectedGradeId]) {
                return this.salaryGradesData[this.selectedGradeId];
            }
            return { id: 1, code: 'PG-3', level: 'Intermediate', position: 'Dispatcher', min: 20000, max: 30000, mid: 25000, growth: 6.0 };
        },

        get proposedBase() {
            if (this.calculationResult) {
                if (this.mode === 'mode_a') {
                    return this.calculationResult.proposed_base_salary || 25000;
                }
                return this.basicSalary || 25000;
            }
            return this.basicSalary || 25000;
        },

        get compaRatio() {
            const sal = Number(this.proposedBase) || 1;
            const mid = this.currentGrade.mid || 1;
            return ((sal / mid) * 100).toFixed(1);
        },

        get rangePenetration() {
            const sal = Number(this.proposedBase) || 0;
            const min = this.currentGrade.min || 0;
            const max = this.currentGrade.max || 1;
            const pct = Math.min(100, Math.max(0, ((sal - min) / (max - min)) * 100));
            return pct.toFixed(0);
        },

        get totalAllowances() {
            return (Number(this.transportAllowance) || 0) + (Number(this.mealAllowance) || 0) + (Number(this.commsAllowance) || 0);
        },

        calculateLivePackage() {
            this.calculationLoading = true;
            fetch('{{ route('compensation.counter-offers.calculate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    mode: this.mode,
                    salary_grade_id: this.selectedGradeId,
                    employee_id: this.subjectType === 'employee' ? this.selectedEmployeeId : null,
                    competitor_offer: Number(this.competitorOffer) || 0,
                    education: parseInt(this.education),
                    experience: parseInt(this.experience),
                    skills: parseInt(this.skills),
                    market_benchmark: parseInt(this.marketBenchmark),
                    internal_equity: parseInt(this.internalEquity),
                    basic_salary: Number(this.basicSalary) || 0,
                    transport_allowance: Number(this.transportAllowance) || 0,
                    meal_allowance: Number(this.mealAllowance) || 0,
                    comms_allowance: Number(this.commsAllowance) || 0,
                    signing_bonus: Number(this.signingBonus) || 0
                })
            })
            .then(r => r.json())
            .then(data => {
                this.calculationResult = data;
                if (this.mode === 'mode_a' && data.proposed_base_salary) {
                    this.basicSalary = data.proposed_base_salary;
                }
                this.calculationLoading = false;
            })
            .catch(() => {
                this.calculationLoading = false;
            });
        },

        openBreakdown(item) {
            this.activeBreakdownItem = item;
            this.breakdownModalOpen = true;
        },

        init() {
            this.calculateLivePackage();
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Main Tab 1: Formulation Wizard -->
                <button type="button" @click="mainTab = 'wizard'" 
                        :class="mainTab === 'wizard' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Offer Formulation Wizard
                </button>

                <!-- Main Tab 2: Negotiation History & Queue -->
                <button type="button" @click="mainTab = 'queue'" 
                        :class="mainTab === 'queue' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Negotiation Queue & History
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $adjustments->total() }}</span>
                </button>
            </div>

            <!-- Active Target Indicator -->
            <div class="hidden sm:flex items-center gap-2 text-xs font-semibold bg-white/80 border border-gray-200 px-3 py-1.5 rounded-xl shadow-2xs">
                <span class="text-gray-400">Target:</span>
                <span class="font-extrabold text-gray-900" x-text="currentSubject.name"></span>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-lg" 
                      :class="subjectType === 'employee' ? 'bg-gray-100 text-gray-800' : 'bg-purple-100 text-purple-900'" 
                      x-text="subjectType === 'employee' ? 'Staff Retention' : 'Applicant Hire'"></span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 1: 3-STEP OFFER FORMULATION WIZARD -->
        <!-- ========================================================================= -->
        <div x-show="mainTab === 'wizard'" x-transition class="space-y-6">

            <!-- Step Progress Indicator -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm">
                <div class="grid grid-cols-3 gap-2">
                    
                    <!-- Step 1 -->
                    <button type="button" @click="wizardStep = 1" 
                            :class="wizardStep === 1 ? 'border-[#F44336] bg-red-50/50 text-[#F44336]' : (wizardStep > 1 ? 'border-emerald-500 bg-emerald-50/30 text-emerald-700' : 'border-gray-200 text-gray-400')" 
                            class="p-3 rounded-xl border-2 text-left transition-all flex items-center gap-3">
                        <div :class="wizardStep === 1 ? 'bg-[#F44336] text-white' : (wizardStep > 1 ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600')" 
                             class="w-7 h-7 rounded-full font-black text-xs flex items-center justify-center flex-shrink-0">
                            1
                        </div>
                        <div>
                            <div class="text-xs font-black">Select Target</div>
                            <div class="text-[11px] font-medium opacity-80" x-text="subjectType === 'employee' ? 'Employee Profile' : 'Candidate Details'"></div>
                        </div>
                    </button>

                    <!-- Step 2 -->
                    <button type="button" @click="wizardStep = 2; calculateLivePackage()" 
                            :class="wizardStep === 2 ? 'border-[#F44336] bg-red-50/50 text-[#F44336]' : (wizardStep > 2 ? 'border-emerald-500 bg-emerald-50/30 text-emerald-700' : 'border-gray-200 text-gray-400')" 
                            class="p-3 rounded-xl border-2 text-left transition-all flex items-center gap-3">
                        <div :class="wizardStep === 2 ? 'bg-[#F44336] text-white' : (wizardStep > 2 ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600')" 
                             class="w-7 h-7 rounded-full font-black text-xs flex items-center justify-center flex-shrink-0">
                            2
                        </div>
                        <div>
                            <div class="text-xs font-black">Configure Mode & Package</div>
                            <div class="text-[11px] font-medium opacity-80">Mode A / Mode B & CTC</div>
                        </div>
                    </button>

                    <!-- Step 3 -->
                    <button type="button" @click="wizardStep = 3; calculateLivePackage()" 
                            :class="wizardStep === 3 ? 'border-[#F44336] bg-red-50/50 text-[#F44336]' : 'border-gray-200 text-gray-400'" 
                            class="p-3 rounded-xl border-2 text-left transition-all flex items-center gap-3">
                        <div :class="wizardStep === 3 ? 'bg-[#F44336] text-white' : 'bg-gray-200 text-gray-600'" 
                             class="w-7 h-7 rounded-full font-black text-xs flex items-center justify-center flex-shrink-0">
                            3
                        </div>
                        <div>
                            <div class="text-xs font-black">Review & Submit</div>
                            <div class="text-[11px] font-medium opacity-80">Distortion Guard & Budget</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Form Wrapper -->
            <form action="{{ route('compensation.adjustments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="counter_offer">
                <input type="hidden" name="subject_type" :value="subjectType">
                <input type="hidden" name="mode" :value="mode">

                <!-- ========================================================================= -->
                <!-- STEP 1: TARGET SELECTION -->
                <!-- ========================================================================= -->
                <div x-show="wizardStep === 1" class="space-y-6">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                        
                        <!-- Toggle Employee vs Applicant -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Subject Type</label>
                            <div class="grid grid-cols-2 gap-3 max-w-md">
                                <button type="button" @click="subjectType = 'employee'; calculateLivePackage()" 
                                        :class="subjectType === 'employee' ? 'bg-gray-900 text-white font-black' : 'bg-gray-100 text-gray-700 font-bold hover:bg-gray-200'" 
                                        class="py-2.5 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2">
                                    Existing Staff (Retention)
                                </button>
                                <button type="button" @click="subjectType = 'applicant'; calculateLivePackage()" 
                                        :class="subjectType === 'applicant' ? 'bg-gray-900 text-white font-black' : 'bg-gray-100 text-gray-700 font-bold hover:bg-gray-200'" 
                                        class="py-2.5 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2">
                                    External Applicant (Hire)
                                </button>
                            </div>
                        </div>

                        <!-- Employee Dropdown -->
                        <div x-show="subjectType === 'employee'" class="space-y-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Select Target Employee</label>
                            <select name="employee_id" x-model="selectedEmployeeId" @change="calculateLivePackage()"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">
                                        {{ $e->first_name }} {{ $e->last_name }} ({{ $e->employee_code }}) — {{ $e->position }} [{{ $e->department?->name ?? 'Ops' }}]
                                    </option>
                                @endforeach
                            </select>

                            <!-- Read-Only Performance Integration Card (Team 3) -->
                            <div class="p-3 bg-purple-50/60 rounded-xl border border-purple-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-purple-950 font-bold">Team 3 Performance Rating:</span>
                                    <span class="font-extrabold text-purple-900 ml-1" x-text="currentSubject.rating"></span>
                                    <span class="text-[10px] text-purple-600 block">(Read-only data consumed from Team 3 Performance Management)</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-purple-200 text-purple-900 font-mono font-black text-xs">
                                    Locked Score
                                </span>
                            </div>
                        </div>

                        <!-- Applicant Inputs -->
                        <div x-show="subjectType === 'applicant'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Candidate Full Name</label>
                                <input type="text" name="applicant_name" x-model="applicantName"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Applying Position</label>
                                <input type="text" name="applicant_position" x-model="applicantPosition"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>

                        <!-- Target Pay Grade Selection -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Target Pay Grade Level</label>
                            <select x-model="selectedGradeId" @change="calculateLivePackage()"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                @foreach($salaryGrades as $sg)
                                    <option value="{{ $sg->id }}">
                                        {{ $sg->grade_code ?? ('PG-' . $loop->iteration) }} ({{ $sg->job_level }}) — {{ $sg->position_name }} [PHP {{ number_format((float)$sg->min_salary, 0) }} - PHP {{ number_format((float)$sg->max_salary, 0) }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Competitor Offer Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Competitor / Competing Company</label>
                                <input type="text" name="competitor_company" x-model="competitorCompany" placeholder="e.g. Grab Philippines, Lalamove"
                                       class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-medium text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Competitor Monthly Offer (PHP)</label>
                                <input type="number" step="500" name="competitor_offer" x-model="competitorOffer" @input="calculateLivePackage()"
                                       class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-black text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>

                        <!-- Next Step Button -->
                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button type="button" @click="wizardStep = 2; calculateLivePackage()"
                                    class="bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-black px-6 py-2.5 rounded-xl transition-all shadow-sm">
                                Proceed to Package Formulation
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- STEP 2: CONFIGURE MODE & PACKAGE -->
                <!-- ========================================================================= -->
                <div x-show="wizardStep === 2" class="space-y-6">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                        
                        <!-- Mode Selector (Mode A vs Mode B) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Operational Mode Selection</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button type="button" @click="mode = 'mode_a'; calculateLivePackage()" 
                                        :class="mode === 'mode_a' ? 'border-[#F44336] bg-red-50/50 text-[#F44336]' : 'border-gray-200 text-gray-600 hover:bg-gray-50'" 
                                        class="p-4 rounded-2xl border-2 text-left transition-all">
                                    <div class="font-black text-xs">Mode A: Automated Credential Engine</div>
                                    <p class="text-[11px] text-gray-500 mt-1">Calculates offer with 5-factor scoring and auto-enforces the statutory cap: MIN(Pay Grade Max, Competing Offer x 1.10).</p>
                                </button>
                                <button type="button" @click="mode = 'mode_b'; calculateLivePackage()" 
                                        :class="mode === 'mode_b' ? 'border-[#F44336] bg-red-50/50 text-[#F44336]' : 'border-gray-200 text-gray-600 hover:bg-gray-50'" 
                                        class="p-4 rounded-2xl border-2 text-left transition-all">
                                    <div class="font-black text-xs">Mode B: Manual Itemized CTC Builder</div>
                                    <p class="text-[11px] text-gray-500 mt-1">Itemize Base, Allowances (Transport, Meal, Comms), and Signing Bonus with live Employer CTC computation.</p>
                                </button>
                            </div>
                        </div>

                        <!-- MODE A: 5-FACTOR CREDENTIAL INPUTS -->
                        <div x-show="mode === 'mode_a'" class="space-y-4 bg-purple-50/40 p-4 rounded-2xl border border-purple-200">
                            <h3 class="text-xs font-black uppercase text-purple-950">Mode A 5-Factor Candidate Weights</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Education (25% Weight)</label>
                                    <select x-model="education" @change="calculateLivePackage()"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium">
                                        <option value="1">1 - High School Graduate</option>
                                        <option value="2">2 - Vocational / TESDA</option>
                                        <option value="3">3 - College Graduate</option>
                                        <option value="4">4 - Bachelor with Honors</option>
                                        <option value="5">5 - Master's Degree</option>
                                        <option value="6">6 - Doctoral Degree</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Experience (35% Weight)</label>
                                    <select x-model="experience" @change="calculateLivePackage()"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium">
                                        <option value="1">1 - 0 to 1 Year</option>
                                        <option value="2">2 - 1 to 3 Years</option>
                                        <option value="3">3 - 3 to 5 Years</option>
                                        <option value="4">4 - 5 to 8 Years</option>
                                        <option value="5">5 - 8 to 12 Years</option>
                                        <option value="6">6 - 12+ Years</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Skills Fit (20% Weight)</label>
                                    <select x-model="skills" @change="calculateLivePackage()"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium">
                                        <option value="1">1 - Foundational</option>
                                        <option value="2">2 - Basic</option>
                                        <option value="3">3 - Standard Competency</option>
                                        <option value="4">4 - Proficient</option>
                                        <option value="5">5 - Advanced Specialist</option>
                                        <option value="6">6 - Subject Matter Expert</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Market Benchmark (10% Weight)</label>
                                    <select x-model="marketBenchmark" @change="calculateLivePackage()"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-medium">
                                        <option value="1">1 - Below Market</option>
                                        <option value="2">2 - Low Market Tier</option>
                                        <option value="3">3 - Standard Market Rate</option>
                                        <option value="4">4 - Competitive Rate</option>
                                        <option value="5">5 - High Demand</option>
                                        <option value="6">6 - Premium Market Leader</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- MODE B: ITEMIZE ALLOWANCES & SIGNING BONUS -->
                        <div x-show="mode === 'mode_b'" class="space-y-4 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                            <h3 class="text-xs font-black uppercase text-gray-900">Mode B Itemized Components</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Basic Monthly (PHP)</label>
                                    <input type="number" step="500" name="new_rate" x-model="basicSalary" @input="calculateLivePackage()"
                                           class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-black text-gray-900 focus:outline-none focus:border-[#F44336]">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Transport Allowance (PHP)</label>
                                    <input type="number" step="100" name="transport_allowance" x-model="transportAllowance" @input="calculateLivePackage()"
                                           class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Meal Allowance (PHP)</label>
                                    <input type="number" step="100" name="meal_allowance" x-model="mealAllowance" @input="calculateLivePackage()"
                                           class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Comms Allowance (PHP)</label>
                                    <input type="number" step="100" name="comms_allowance" x-model="commsAllowance" @input="calculateLivePackage()"
                                           class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-2">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">One-Time Signing Bonus (PHP)</label>
                                    <input type="number" step="500" name="signing_bonus" x-model="signingBonus" @input="calculateLivePackage()"
                                           class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-black text-gray-900 focus:outline-none focus:border-[#F44336]">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">HMO Tier Entitlement</label>
                                    <select name="hmo_tier" x-model="hmoTier"
                                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                        <option value="Individual">Individual Standard (MBL 150k)</option>
                                        <option value="Individual+1">Individual + 1 Dependent (MBL 200k)</option>
                                        <option value="Family">Family Comprehensive (MBL 300k)</option>
                                        <option value="Executive">Executive Platinum (MBL 500k)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- LIVE CTC & COMPA-RATIO VISUALIZER -->
                        <template x-if="calculationResult">
                            <div class="p-5 bg-purple-950 text-white rounded-3xl space-y-4 shadow-xl">
                                <div class="flex items-center justify-between border-b border-purple-800 pb-3">
                                    <div>
                                        <span class="text-[10px] font-black text-purple-300 uppercase tracking-wider block">Total Cost to Company (CTC) Projection</span>
                                        <div class="text-xl font-black font-outfit text-white mt-0.5">
                                            PHP <span x-text="Number(calculationResult.ctc.monthly_ctc).toLocaleString(undefined, {minimumFractionDigits: 2})"></span> / month
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-black text-purple-300 uppercase tracking-wider block">Annual Financial Burden</span>
                                        <div class="text-lg font-black font-outfit text-emerald-400 mt-0.5">
                                            PHP <span x-text="Number(calculationResult.ctc.annual_ctc).toLocaleString(undefined, {minimumFractionDigits: 2})"></span> / year
                                        </div>
                                    </div>
                                </div>

                                <!-- Statutory & Allowance Breakdown -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono">
                                    <div class="bg-purple-900/60 p-2.5 rounded-xl border border-purple-800">
                                        <span class="text-purple-300 text-[10px] block">Base Salary</span>
                                        <span class="font-bold text-white" x-text="'PHP ' + Number(calculationResult.ctc.base_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="bg-purple-900/60 p-2.5 rounded-xl border border-purple-800">
                                        <span class="text-purple-300 text-[10px] block">Allowances</span>
                                        <span class="font-bold text-white" x-text="'PHP ' + Number(calculationResult.ctc.total_allowances).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="bg-purple-900/60 p-2.5 rounded-xl border border-purple-800">
                                        <span class="text-purple-300 text-[10px] block">ER Statutory Total</span>
                                        <span class="font-bold text-white" x-text="'PHP ' + Number(calculationResult.ctc.employer_statutory.total).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="bg-purple-900/60 p-2.5 rounded-xl border border-purple-800">
                                        <span class="text-purple-300 text-[10px] block">13th Month Reserve</span>
                                        <span class="font-bold text-white" x-text="'PHP ' + Number(calculationResult.ctc.thirteenth_month_liability).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>

                                <!-- Range Penetration Slider -->
                                <div class="space-y-1.5 pt-1">
                                    <div class="flex justify-between text-[11px] font-bold text-purple-200">
                                        <span>Band Range Penetration: <strong class="text-white" x-text="rangePenetration + '%'"></strong></span>
                                        <span>Compa-Ratio: <strong class="text-white" x-text="compaRatio + '%'"></strong></span>
                                    </div>
                                    <div class="w-full bg-purple-900 rounded-full h-2.5 overflow-hidden flex">
                                        <div class="bg-emerald-400 h-2.5 transition-all" :style="'width: ' + rangePenetration + '%'"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Step 2 Navigation -->
                        <div class="flex justify-between pt-4 border-t border-gray-100">
                            <button type="button" @click="wizardStep = 1"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-5 py-2.5 rounded-xl transition-all">
                                Back to Target
                            </button>
                            <button type="button" @click="wizardStep = 3; calculateLivePackage()"
                                    class="bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-black px-6 py-2.5 rounded-xl transition-all shadow-sm">
                                Proceed to Final Review
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- STEP 3: REVIEW, WAGE DISTORTION GUARD & SUBMISSION -->
                <!-- ========================================================================= -->
                <div x-show="wizardStep === 3" class="space-y-6">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                        
                        <!-- Internal Equity & Wage Distortion Alert Banner -->
                        <template x-if="calculationResult && calculationResult.internal_equity">
                            <div class="p-4 rounded-2xl border text-xs space-y-2"
                                 :class="calculationResult.internal_equity.status === 'WAGE_DISTORTION_WARNING' ? 'bg-amber-50 border-amber-200 text-amber-950' : 'bg-emerald-50 border-emerald-200 text-emerald-950'">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="uppercase tracking-wider text-[10px] font-black">Internal Equity & Wage Distortion Guard</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="calculationResult.internal_equity.status === 'WAGE_DISTORTION_WARNING' ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900'"
                                          x-text="calculationResult.internal_equity.status">
                                    </span>
                                </div>
                                <p class="font-medium" x-text="calculationResult.internal_equity.message"></p>
                                <div class="flex gap-4 text-[11px] font-mono text-gray-600 pt-1">
                                    <span>Peer Count: <strong x-text="calculationResult.internal_equity.peer_count"></strong></span>
                                    <span>Peer Median: <strong x-text="'PHP ' + Number(calculationResult.internal_equity.peer_median_salary).toLocaleString(undefined, {minimumFractionDigits: 2})"></strong></span>
                                    <span>Variance: <strong x-text="calculationResult.internal_equity.variance_percentage + '%'"></strong></span>
                                </div>
                            </div>
                        </template>

                        <!-- Final Proposal Summary Table -->
                        <div class="border border-gray-200 rounded-2xl overflow-hidden text-xs">
                            <table class="w-full text-left border-collapse">
                                <tbody class="divide-y divide-gray-100">
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-gray-500 font-bold w-1/3">Target Subject</th>
                                        <td class="py-3 px-4 font-black text-gray-900" x-text="currentSubject.name + ' (' + currentSubject.position + ')'"></td>
                                    </tr>
                                    <tr>
                                        <th class="py-3 px-4 text-gray-500 font-bold">Pay Grade Band</th>
                                        <td class="py-3 px-4 font-bold text-gray-900" x-text="currentGrade.code + ' (' + currentGrade.level + ') — PHP ' + Number(currentGrade.min).toLocaleString() + ' to PHP ' + Number(currentGrade.max).toLocaleString()"></td>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-gray-500 font-bold">Proposed Base Salary</th>
                                        <td class="py-3 px-4 font-black text-purple-900 text-sm" x-text="'PHP ' + Number(proposedBase).toLocaleString(undefined, {minimumFractionDigits: 2})"></td>
                                    </tr>
                                    <tr>
                                        <th class="py-3 px-4 text-gray-500 font-bold">Total Monthly Allowances</th>
                                        <td class="py-3 px-4 font-bold text-gray-900" x-text="'PHP ' + Number(totalAllowances).toLocaleString(undefined, {minimumFractionDigits: 2})"></td>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-gray-500 font-bold">Total Monthly CTC</th>
                                        <td class="py-3 px-4 font-black text-gray-900" x-text="calculationResult ? ('PHP ' + Number(calculationResult.ctc.monthly_ctc).toLocaleString(undefined, {minimumFractionDigits: 2})) : 'PHP 0.00'"></td>
                                    </tr>
                                    <tr>
                                        <th class="py-3 px-4 text-gray-500 font-bold">Total Annual CTC Burden</th>
                                        <td class="py-3 px-4 font-black text-emerald-800" x-text="calculationResult ? ('PHP ' + Number(calculationResult.ctc.annual_ctc).toLocaleString(undefined, {minimumFractionDigits: 2})) : 'PHP 0.00'"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Justification Reason -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Proposal Business Justification</label>
                            <textarea name="reason" rows="3" x-model="reason" required
                                      class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs font-medium text-gray-900 focus:outline-none focus:border-[#F44336]"></textarea>
                        </div>

                        <!-- Submission Controls -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <button type="button" @click="wizardStep = 2"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-5 py-2.5 rounded-xl transition-all">
                                Back to Configuration
                            </button>
                            <button type="submit"
                                    class="bg-purple-950 hover:bg-black text-white text-xs font-black px-6 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2">
                                Submit Counter Offer for Approval
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 2: NEGOTIATION QUEUE & AUDIT TABLE -->
        <!-- ========================================================================= -->
        <div x-show="mainTab === 'queue'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Counter Offer & Applicant Offer Negotiation Queue</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Active compensation adjustment requisitions with financial budget impact statuses.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-400 font-extrabold uppercase">
                                <th class="py-3 px-4">Subject</th>
                                <th class="py-3 px-4">Mode</th>
                                <th class="py-3 px-4 text-right">Proposed Rate</th>
                                <th class="py-3 px-4 text-right">Monthly CTC</th>
                                <th class="py-3 px-4 text-right">Annual CTC</th>
                                <th class="py-3 px-4 text-center">Distortion Status</th>
                                <th class="py-3 px-4 text-center">Budget Status</th>
                                <th class="py-3 px-4 text-center">Decision Status</th>
                                <th class="py-3 px-4 text-right">Action</th>
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
                                        <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-gray-100 text-gray-700">
                                            {{ strtoupper(str_replace('_', ' ', $adj->mode ?? 'mode_a')) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP {{ number_format((float)$adj->new_rate, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-extrabold font-outfit text-purple-900">
                                        PHP {{ number_format((float)($adj->monthly_ctc ?: $adj->new_rate * 1.15), 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-emerald-800">
                                        PHP {{ number_format((float)($adj->annual_ctc ?: $adj->new_rate * 14), 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        @if($adj->isWageDistorted())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900">
                                                Distortion Warning
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900">
                                                Equity Aligned
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900">
                                            {{ $adj->budget_impact_status ?? 'BUDGET_APPROVED' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                                            {{ ucfirst($adj->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($adj->status === 'pending')
                                                <form action="{{ route('compensation.adjustments.approve', $adj) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-2.5 py-1.5 rounded-lg">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('compensation.adjustments.reject', $adj) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-2.5 py-1.5 rounded-lg">
                                                        Reject
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-400 font-bold">
                                        No counter offer or applicant offer adjustments currently queued.
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
