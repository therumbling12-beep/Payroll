@extends('layouts.ess')

@php
    $pageTitle = 'Employee Self Service (ESS)';
    $currentPage = 'ess.dashboard';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Employee Self-Service (ESS) Portal</h1>
            <p class="text-xs text-gray-500 mt-0.5">View your transparent compensation breakdown, digital HMO healthcare card, enrolled dependents, and benefit applications.</p>
        </div>
        
        <!-- Employee Selector Dropdown -->
        <form action="{{ route('ess.dashboard') }}" method="GET" class="flex items-center gap-3">
            <label class="text-xs font-semibold text-gray-500">Select Employee:</label>
            <select name="employee_id" onchange="this.form.submit()" class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-gray-900 shadow-sm font-medium">
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $selectedEmployee && $selectedEmployee->id == $emp->id ? 'selected' : '' }}>
                        {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Annual Open Enrollment Announcement Banner -->
    @if(isset($isOpenEnrollmentActive) && $isOpenEnrollmentActive)
        <div class="mb-6 p-5 bg-gradient-to-r from-blue-900 to-indigo-900 rounded-2xl text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4 border border-blue-800">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-blue-500/30 border border-blue-400/40 text-blue-200 text-[10px] font-black uppercase rounded-full tracking-wider">
                        Annual Open Enrollment Active
                    </span>
                    <span class="text-xs text-blue-200 font-medium">
                        Sign-up is open from {{ \Carbon\Carbon::parse($openEnrollmentWindow['start_date'] ?? date('Y-11-01'))->format('F j') }} to {{ \Carbon\Carbon::parse($openEnrollmentWindow['end_date'] ?? date('Y-11-30'))->format('F j, Y') }}
                    </span>
                </div>
                <h3 class="text-base font-black font-outfit text-white">Healthcare Plan Elections & Family Dependent Registration</h3>
                <p class="text-xs text-blue-200">Submit a healthcare tier change or register direct family dependents for the upcoming plan year.</p>
            </div>
            <button @click="showApplyModal = true" type="button" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-100 text-gray-900 font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-md flex-shrink-0">
                <span>Apply or Add Family</span>
            </button>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($selectedEmployee)
    <div x-data="{
        showApplyModal: false,
        showCardModal: false,
        showClaimModal: false,
        showLoaModal: false,
        showApeModal: false,
        showBeneficiaryModal: false,
        patientType: 'employee',
        claimStep: 1,
        claimType: 'expense',
        claimCategoryId: '',
        claimDistance: 0,
        claimFuelPrice: {{ $fuelSettings['pump_price'] ?? 65.0 }},
        claimFuelEfficiency: {{ $fuelSettings['efficiency'] ?? 10.0 }},
        claimTolerancePct: {{ $fuelSettings['tolerance_pct'] ?? 15.0 }},
        claimAmount: '',
        receiptPreview: null,
        receiptFileName: '',
        receiptFileSize: '',
        medicalUtilized: {{ (float) ($medicalUtilized ?? 0) }},
        medicalCap: {{ (float) ($medicalCap ?? 10000.0) }},
        maternityType: 'normal_caesarean',
        maternityCalculations: {{ Js::from($maternityCalculations ?? []) }},
        get remainingMedicalCap() {
            return Math.max(0, this.medicalCap - this.medicalUtilized);
        },
        get medicalNonTaxable() {
            const amt = parseFloat(this.claimAmount || 0);
            return Math.min(amt, this.remainingMedicalCap).toFixed(2);
        },
        get medicalTaxable() {
            const amt = parseFloat(this.claimAmount || 0);
            return Math.max(0, amt - this.remainingMedicalCap).toFixed(2);
        },
        get currentMaternity() {
            return this.maternityCalculations[this.maternityType] || {};
        },
        dependents: [],
        addDependent() {
            this.dependents.push({ full_name: '', relationship: 'Child', birth_date: '', gender: 'Male' });
        },
        removeDependent(index) {
            this.dependents.splice(index, 1);
        },
        get isFuelSelected() {
            const cat = this.claimCategories.find(c => c.id == this.claimCategoryId);
            return (cat && cat.code === 'FUEL-EXP') || (cat && cat.name.toLowerCase().includes('fuel'));
        },
        claimCategories: {{ Js::from($categories) }},
        get estimatedFuelLiters() {
            if (!this.claimDistance || this.claimDistance <= 0 || !this.claimFuelEfficiency || this.claimFuelEfficiency <= 0) return '0.00';
            return (this.claimDistance / this.claimFuelEfficiency).toFixed(2);
        },
        get expectedFuelCost() {
            return (parseFloat(this.estimatedFuelLiters) * this.claimFuelPrice).toFixed(2);
        },
        get isWithinTolerance() {
            if (!this.claimAmount || this.claimAmount <= 0 || !this.claimDistance || this.claimDistance <= 0) return false;
            const expected = parseFloat(this.expectedFuelCost);
            if (expected <= 0) return false;
            const variance = ((parseFloat(this.claimAmount) - expected) / expected) * 100;
            return variance <= this.claimTolerancePct;
        },
        get variancePercentage() {
            if (!this.claimAmount || !this.claimDistance || this.claimDistance <= 0) return 0;
            const expected = parseFloat(this.expectedFuelCost);
            if (expected <= 0) return 0;
            return (((parseFloat(this.claimAmount) - expected) / expected) * 100).toFixed(1);
        },
        handleReceiptUpload(e) {
            const file = e.target.files ? e.target.files[0] : (e.dataTransfer ? e.dataTransfer.files[0] : null);
            if (!file) return;
            this.receiptFileName = file.name;
            this.receiptFileSize = (file.size / 1024).toFixed(1) + ' KB';
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => { this.receiptPreview = ev.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.receiptPreview = null;
            }
        },
        clearReceipt() {
            this.receiptPreview = null;
            this.receiptFileName = '';
            this.receiptFileSize = '';
            if (this.$refs.receiptInput) this.$refs.receiptInput.value = '';
        }
    }" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Profile, HMO Card, & Bank Setup -->
        <div class="space-y-6">

            <!-- Profile Summary Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gray-900 text-white flex items-center justify-center text-lg font-bold shadow-md">
                        {{ strtoupper(substr($selectedEmployee->first_name, 0, 1) . substr($selectedEmployee->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 font-outfit">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</h2>
                        <span class="inline-block px-2.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-semibold rounded-full mt-0.5">{{ $selectedEmployee->position }}</span>
                    </div>
                </div>
                <div class="border-t border-gray-50 pt-4 space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Employee Code:</span>
                        <span class="font-mono font-semibold text-gray-700">{{ $selectedEmployee->employee_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Department:</span>
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->department->name ?? 'General' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Email:</span>
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Salary Grade:</span>
                        <span class="font-bold text-gray-900 font-mono">PG-{{ $selectedEmployee->salary_grade ?? 1 }}</span>
                    </div>
                </div>
            </div>

            <!-- Digital HMO Card / Benefits Section -->
            @if($hmo)
                <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-950 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 bg-emerald-950/60 border border-emerald-800/50 px-2.5 py-1 rounded-full">
                            {{ $hmo->hmo_provider }} Corporate E-Card
                        </span>
                        <span class="text-[10px] font-mono text-gray-400 uppercase font-bold">{{ $hmo->coverage_tier }}</span>
                    </div>

                    <div>
                        <p class="text-[9px] text-gray-400 uppercase tracking-widest font-mono">HMO Member Card Number</p>
                        <p class="text-lg font-black font-mono tracking-wider mt-0.5">{{ $hmo->hmo_card_number }}</p>
                    </div>

                    <div class="border-t border-white/10 pt-3 flex justify-between items-center text-xs">
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase">Annual MBL Limit</p>
                            <p class="font-black text-white font-outfit text-sm">PHP {{ number_format((float)($hmo->annual_limit ?: $hmo->mbl_amount), 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] text-gray-400 uppercase">Remaining MBL</p>
                            <p class="font-black text-emerald-400 font-outfit text-sm">PHP {{ number_format($hmo->remainingBalance(), 2) }}</p>
                        </div>
                    </div>

                    @if($hmo->isLowBalance())
                        <div class="p-2.5 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-[11px] font-bold flex items-center justify-between">
                            <span>Notice: Remaining MBL is below 20%</span>
                            <span class="font-mono text-rose-200">PHP {{ number_format($hmo->remainingBalance(), 2) }} left</span>
                        </div>
                    @endif

                    @if($hmo->isExpiringSoon())
                        <div class="p-2.5 bg-amber-500/20 border border-amber-500/40 rounded-xl text-amber-300 text-[11px] font-bold flex items-center justify-between">
                            <span>Renewal due in {{ $hmo->daysUntilExpiry() }} days</span>
                            <span class="text-[10px] uppercase underline">30-Day Cycle</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2">
                        <button @click="showCardModal = true" type="button" class="w-full bg-white hover:bg-gray-100 text-gray-900 font-black text-xs py-2.5 rounded-xl transition-all shadow-sm cursor-pointer text-center">
                            Digital E-Card
                        </button>
                        <button @click="showLoaModal = true" type="button" class="w-full bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black text-xs py-2.5 rounded-xl transition-all shadow-sm cursor-pointer text-center">
                            Request LOA
                        </button>
                    </div>
                </div>

                <!-- Enrolled Dependents Widget -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-900 font-outfit">Enrolled Family Dependents</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700">
                            {{ $hmo->dependents->count() }} Registered
                        </span>
                    </div>

                    <div class="space-y-2 text-xs">
                        @forelse($hmo->dependents as $dep)
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200/60 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-gray-900">{{ $dep->full_name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $dep->relationship }} • {{ $dep->birth_date?->format('M j, Y') ?? 'N/A' }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $dep->status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $dep->status }}
                                </span>
                            </div>
                        @empty
                            <p class="text-[11px] text-gray-400 text-center py-2">No family dependents enrolled under this policy.</p>
                        @endforelse
                    </div>
                </div>

            @else
                <!-- No HMO: Apply for Coverage CTA -->
                <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-6 text-center space-y-3 shadow-2xs">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black font-outfit text-gray-900">Apply for HMO Healthcare Policy</h3>
                        <p class="text-[11px] text-gray-500 mt-1">Enroll yourself and your qualified dependents into TripWise corporate medical insurance.</p>
                    </div>
                    <button @click="showApplyModal = true" type="button" class="w-full bg-gray-900 hover:bg-black text-white text-xs font-black py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                        Start HMO Application
                    </button>
                </div>
            @endif

            <!-- Annual Physical Exam (APE) Widget -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black font-outfit text-gray-900 uppercase tracking-wider">Annual Physical Exam (APE)</h3>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $apeExam ? $apeExam->attendanceBadgeClasses() : 'bg-gray-100 text-gray-500' }}">
                        {{ $apeExam ? ucfirst($apeExam->attendance_status) : 'Unscheduled' }}
                    </span>
                </div>

                @if($apeExam)
                    <div class="p-3 bg-gray-50 rounded-xl space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date & Slot:</span>
                            <span class="font-bold text-gray-900">{{ $apeExam->schedule_date->format('M j, Y') }} ({{ $apeExam->time_slot }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Facility:</span>
                            <span class="font-semibold text-gray-800">{{ $apeExam->facility_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Medical Clearance:</span>
                            <span class="font-black text-emerald-700">{{ $apeExam->clearanceLabel() }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-[11px] text-gray-500">Annual occupational wellness check. Batch appointments are designated during company campaigns.</p>
                @endif

                <button @click="showApeModal = true" type="button" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-800 font-bold text-xs py-2 rounded-xl border border-gray-200 transition-all cursor-pointer">
                    {{ $apeExam ? 'Reschedule APE Appointment' : 'Book APE Clinic Appointment' }}
                </button>
            </div>

            <!-- Corporate Group Life & Disability Widget -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black font-outfit text-gray-900 uppercase tracking-wider">Group Life & Disability</h3>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $groupLife ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500' }}">
                        {{ $groupLife ? 'Active Insured' : 'Pending Enrollment' }}
                    </span>
                </div>

                @if($groupLife)
                    <div class="p-3 bg-purple-50/50 border border-purple-100 rounded-xl space-y-1.5 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Sum Assured:</span>
                            <span class="font-black font-outfit text-sm text-purple-700">PHP {{ number_format((float)$groupLife->sum_assured, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Primary Beneficiary:</span>
                            <span class="font-bold text-gray-900">{{ $groupLife->beneficiary_primary_name }} ({{ $groupLife->beneficiary_primary_relation }})</span>
                        </div>
                    </div>
                @else
                    <p class="text-[11px] text-gray-500">100% company-subsidized Group Term Life and Accidental Death & Dismemberment (AD&D) coverage.</p>
                @endif

                <button @click="showBeneficiaryModal = true" type="button" class="w-full bg-gray-50 hover:bg-gray-100 text-gray-800 font-bold text-xs py-2 rounded-xl border border-gray-200 transition-all cursor-pointer">
                    Update Beneficiary Designation
                </button>
            </div>

            <!-- Bank Deposit Setup Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 font-outfit mb-1">Bank Deposit Information</h3>
                <p class="text-[11px] text-gray-400 mb-4">Configure your preferred mode of payment for salary releases.</p>

                <form action="{{ route('ess.bank-details') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Payment Mode</label>
                        <select name="payment_method" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                            <option value="bank" {{ strtolower($selectedEmployee->payment_method ?? 'bank') === 'bank' ? 'selected' : '' }}>Direct Bank Deposit</option>
                            <option value="cash" {{ strtolower($selectedEmployee->payment_method ?? '') === 'cash' ? 'selected' : '' }}>Cash Payroll Disbursement</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bank Provider Name</label>
                        <input type="text" name="bank_name" value="{{ $selectedEmployee->bank_name ?? 'BDO Unibank' }}" placeholder="e.g. BDO, BPI, UnionBank" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bank Account / Reference No.</label>
                        <input type="text" name="bank_account_number" value="{{ $selectedEmployee->bank_account_number ?? $selectedEmployee->bank_account_no ?? '1092-3849-2849' }}" placeholder="Account Number" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <button type="submit" class="w-full bg-gray-900 text-white text-xs font-bold py-2.5 px-4 rounded-xl hover:bg-black transition shadow-sm">
                        Update Bank Account Info
                    </button>
                </form>
            </div>

        </div>

        <!-- Right Column: Payslip Breakdown & Claims History -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Latest Payslip Breakdown -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 font-outfit">Latest Computation Payslip</h3>
                        <p class="text-[11px] text-gray-400">Transparent statutory deductions & gross breakdown.</p>
                    </div>
                    @if($latestComputation)
                        <span class="px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-full uppercase">
                            {{ $latestComputation->status }}
                        </span>
                    @endif
                </div>

                @if($latestComputation)
                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200/60 space-y-3 text-xs">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200 font-bold text-gray-800">
                        <span>Cutoff Period:</span>
                        <span class="font-mono text-indigo-600">{{ $latestComputation->cutoff_period }}</span>
                    </div>

                    <!-- Earnings -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex justify-between text-gray-600">
                            <span>Base Pay:</span>
                            <span class="font-semibold text-gray-800">PHP {{ number_format($latestComputation->base_pay, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Driver Trip Earnings:</span>
                            <span class="font-semibold text-gray-800">PHP {{ number_format($latestComputation->trip_earnings, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Performance Bonus:</span>
                            <span class="font-semibold text-gray-800">PHP {{ number_format($latestComputation->performance_bonus, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Approved Claims & Reimbursements:</span>
                            <span class="font-semibold text-emerald-600">PHP {{ number_format($latestComputation->reimbursements ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-900 font-bold pt-2 border-t border-gray-200/60">
                            <span>Gross Earnings:</span>
                            <span class="text-sm">PHP {{ number_format($latestComputation->gross_pay + ($latestComputation->reimbursements ?? 0), 2) }}</span>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="space-y-1.5 pt-2 border-t border-gray-200">
                        @if(str_contains($selectedEmployee->position ?? '', 'Driver'))
                            <span class="font-bold text-amber-600 text-[11px] uppercase tracking-wider block mb-1">TNVS Platform Deductions</span>
                            <div class="flex justify-between text-gray-600">
                                <span>Platform Commission Fee (20%):</span>
                                <span class="font-mono text-red-500">-PHP {{ number_format($latestComputation->platform_fee_deduction ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Statutory Government Contributions:</span>
                                <span class="font-mono text-gray-400">PHP 0.00 (Independent Partner)</span>
                            </div>
                        @else
                            <span class="font-bold text-red-600 text-[11px] uppercase tracking-wider block mb-1">Statutory & Benefit Deductions</span>
                            <div class="flex justify-between text-gray-600">
                                <span>SSS Contribution:</span>
                                <span class="font-mono text-red-500">-PHP {{ number_format($latestComputation->sss_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>PhilHealth Contribution:</span>
                                <span class="font-mono text-red-500">-PHP {{ number_format($latestComputation->philhealth_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Pag-IBIG Contribution:</span>
                                <span class="font-mono text-red-500">-PHP {{ number_format($latestComputation->pagibig_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Withholding Tax (BIR):</span>
                                <span class="font-mono text-red-500">-PHP {{ number_format($latestComputation->withholding_tax ?? 0, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-900 font-bold pt-2 border-t border-gray-200/60">
                            <span>Total Deductions:</span>
                            <span class="text-red-600">PHP {{ number_format($latestComputation->total_deductions, 2) }}</span>
                        </div>
                    </div>

                    <!-- Net Pay -->
                    <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-gray-200 shadow-sm mt-3">
                        <span class="font-extrabold text-gray-900 text-sm font-outfit">TAKE HOME NET PAY:</span>
                        <span class="text-lg font-black text-emerald-600 font-mono">PHP {{ number_format($latestComputation->net_pay, 2) }}</span>
                    </div>
                </div>
                @else
                    <div class="p-6 bg-gray-50 rounded-2xl text-center text-xs text-gray-400">
                        No salary computations generated for this employee yet.
                    </div>
                @endif
            </div>

            <!-- My Claims & Driver Incentives Tracker -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 font-outfit">My Claims & Driver Incentives</h3>
                        <p class="text-[11px] text-gray-400">Track the live approval stages and payroll inclusion of your filed reimbursements and rewards.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showClaimModal = true; claimStep = 1;" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-3.5 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            File Claim & Upload Receipt
                        </button>
                        <span class="px-2.5 py-1 bg-gray-100 text-gray-700 text-[10px] font-bold rounded-full">
                            {{ $claims->count() }} Records
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($claims as $claim)
                        @php
                            $claimStatus = $claim->approval_status ?? $claim->status;
                            $isPaid = in_array($claimStatus, ['paid', 'payroll_queued'], true);
                            $isFinanceDone = (bool) $claim->finance_approved_at || $isPaid;
                            $isHrDone = (bool) $claim->hr_approved_at || $isFinanceDone;
                        @endphp
                        <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-200/70 hover:border-gray-300 transition-all space-y-3">
                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($claim->type === 'incentive')
                                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase rounded-full">Ride Incentive</span>
                                    @elseif($claim->type === 'expense')
                                        <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-black uppercase rounded-full">Expense Reimbursement</span>
                                    @elseif($claim->type === 'maternity')
                                        <span class="px-2.5 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-black uppercase rounded-full">Maternity Benefit</span>
                                    @elseif($claim->type === 'performance')
                                        <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-black uppercase rounded-full">Performance Reward</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-teal-100 text-teal-800 text-[10px] font-black uppercase rounded-full">Medical Assistance</span>
                                    @endif

                                    <span class="text-[11px] font-mono text-gray-500 font-semibold">
                                        {{ $claim->receipt_number ?: '#CLM-' . str_pad((string)$claim->id, 5, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>

                                <div class="text-right">
                                    <span class="text-sm font-black font-outfit text-gray-900">PHP {{ number_format((float)$claim->amount, 2) }}</span>
                                </div>
                            </div>

                            <!-- Description & Receipt Preview -->
                            <div class="flex items-start justify-between gap-3 text-xs">
                                <div class="text-gray-600">
                                    <p class="font-medium text-gray-800">{{ $claim->description ?: ($claim->categoryModel->name ?? 'Work-Related Claim') }}</p>
                                    @if($claim->categoryModel)
                                        <p class="text-[10px] text-gray-400 mt-0.5">Category: {{ $claim->categoryModel->name }}</p>
                                    @endif
                                </div>
                                @if($claim->attachment_path)
                                    <a href="/storage/{{ $claim->attachment_path }}" target="_blank" class="flex-shrink-0 text-[10px] font-bold text-gray-700 hover:text-gray-900 bg-white border border-gray-200 px-2 py-1 rounded-lg flex items-center gap-1 shadow-2xs">
                                        <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View Receipt
                                    </a>
                                @endif
                            </div>

                            <!-- Visual Delivery-Style 4-Step Progress Tracker Line -->
                            <div class="pt-2 border-t border-gray-200/60">
                                <div class="grid grid-cols-4 gap-1.5 text-center">
                                    <!-- Step 1: Filed -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✓</div>
                                        <span class="text-[9px] font-bold text-gray-800 mt-1">1. Submitted</span>
                                    </div>

                                    <!-- Step 2: HR Validated -->
                                    <div class="flex flex-col items-center">
                                        @if($isHrDone)
                                            <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✓</div>
                                            <span class="text-[9px] font-bold text-emerald-700 mt-1">2. HR Approved</span>
                                        @elseif($claimStatus === 'pending_hr' || $claimStatus === 'pending')
                                            <div class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold animate-pulse shadow-xs">2</div>
                                            <span class="text-[9px] font-bold text-amber-700 mt-1">2. HR Review</span>
                                        @elseif($claimStatus === 'rejected')
                                            <div class="w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✕</div>
                                            <span class="text-[9px] font-bold text-rose-700 mt-1">Declined</span>
                                        @else
                                            <div class="w-5 h-5 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center text-[10px] font-bold">2</div>
                                            <span class="text-[9px] font-medium text-gray-400 mt-1">2. HR Review</span>
                                        @endif
                                    </div>

                                    <!-- Step 3: Finance Budget -->
                                    <div class="flex flex-col items-center">
                                        @if($isFinanceDone)
                                            <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✓</div>
                                            <span class="text-[9px] font-bold text-emerald-700 mt-1">3. Finance OK</span>
                                        @elseif($claimStatus === 'pending_finance' || $claimStatus === 'pending_admin')
                                            <div class="w-5 h-5 rounded-full bg-purple-500 text-white flex items-center justify-center text-[10px] font-bold animate-pulse shadow-xs">3</div>
                                            <span class="text-[9px] font-bold text-purple-700 mt-1">3. Finance Check</span>
                                        @else
                                            <div class="w-5 h-5 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center text-[10px] font-bold">3</div>
                                            <span class="text-[9px] font-medium text-gray-400 mt-1">3. Finance OK</span>
                                        @endif
                                    </div>

                                    <!-- Step 4: Included in Payslip -->
                                    <div class="flex flex-col items-center">
                                        @if($isPaid)
                                            <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">✓</div>
                                            <span class="text-[9px] font-bold text-emerald-700 mt-1">4. In Payslip</span>
                                        @else
                                            <div class="w-5 h-5 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center text-[10px] font-bold">4</div>
                                            <span class="text-[9px] font-medium text-gray-400 mt-1">4. In Payslip</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Plain-English Status Note with Estimated Turnaround -->
                            <div class="p-2.5 rounded-xl text-[11px] font-medium border flex items-center justify-between {{ $claim->status_badge_class }}">
                                <span>
                                    <span class="font-bold">{{ $claim->status_label }}</span> •
                                    @if($claimStatus === 'pending_hr' || $claimStatus === 'pending')
                                        Waiting for HR verification (Estimated review: 1-2 business days)
                                    @elseif($claimStatus === 'pending_finance' || $claimStatus === 'pending_admin')
                                        HR Approved • Forwarded to Finance for budget clearance
                                    @elseif($claimStatus === 'approved')
                                        Approved • Scheduled for inclusion in Cutoff {{ $claim->cutoff_period ?? 'Upcoming' }} Payslip
                                    @elseif($claimStatus === 'paid' || $claimStatus === 'payroll_queued')
                                        Disbursed • Credited in your recent payslip
                                    @elseif($claimStatus === 'revision_required')
                                        Action Needed: {{ $claim->hr_remarks ?? 'Please provide a clear receipt' }}
                                    @elseif($claimStatus === 'rejected')
                                        Declined: {{ $claim->hr_remarks ?? 'Non-qualifying claim' }}
                                    @endif
                                </span>
                                <span class="text-[10px] font-mono opacity-75">Filed {{ $claim->created_at->format('M j') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 bg-gray-50 rounded-2xl text-center text-xs text-gray-400 space-y-1">
                            <p class="font-bold text-gray-600">No Claims or Incentives Found</p>
                            <p class="text-[11px]">When your reimbursements or ride incentives are submitted, they will appear here with live tracking.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ESS HMO ENROLLMENT APPLICATION -->
        <!-- ========================================================================= -->
        <div x-show="showApplyModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showApplyModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Apply for Corporate HMO Coverage</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Submit personal details and dependent documents for HR Team 4 verification</p>
                    </div>
                    <button @click="showApplyModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('ess.hmo.apply') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Preferred HMO Plan Tier *</label>
                            <select name="coverage_tier" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                @if(isset($availableHmoPlans) && $availableHmoPlans->isNotEmpty())
                                    @foreach($availableHmoPlans as $plan)
                                        <option value="{{ $plan->tier_name }}">{{ $plan->tier_name }} (PHP {{ number_format((float)$plan->mbl_amount, 2) }} MBL - {{ $plan->room_label }})</option>
                                    @endforeach
                                @else
                                    <option value="Basic">Basic Plan (PHP 100,000.00 MBL)</option>
                                    <option value="Plus">Plus Plan (PHP 150,000.00 MBL)</option>
                                    <option value="Premium">Premium Plan (PHP 200,000.00 MBL)</option>
                                @endif
                                <option value="Driver Fleet Care">Driver Fleet Care</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">HMO Provider</label>
                            <input type="text" name="hmo_provider" value="{{ $hmoConfig['hmo_provider_name'] }}" readonly class="w-full text-xs bg-gray-100 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-600 font-bold">
                        </div>
                    </div>

                    <!-- File Uploads: ID & Marriage Cert -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Upload Valid ID Photo</label>
                            <input type="file" name="id_photo" accept="image/*,.pdf" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-gray-800">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Marriage Certificate (If Covering Spouse)</label>
                            <input type="file" name="marriage_cert" accept="image/*,.pdf" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-gray-800">
                        </div>
                    </div>

                    <!-- Dynamic Dependents Builder -->
                    <div class="border-t border-gray-100 pt-3 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-800">Qualified Dependents to Cover</span>
                            <button type="button" @click="addDependent()" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                + Add Dependent
                            </button>
                        </div>

                        <template x-for="(dep, idx) in dependents" :key="idx">
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-700" x-text="'Dependent #' + (idx + 1)"></span>
                                    <button type="button" @click="removeDependent(idx)" class="text-rose-600 font-bold">&times; Remove</button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <input type="text" :name="'dependents['+idx+'][full_name]'" x-model="dep.full_name" placeholder="Full Legal Name" required class="bg-white border border-gray-200 rounded-lg px-2.5 py-1.5">
                                    <select :name="'dependents['+idx+'][relationship]'" x-model="dep.relationship" class="bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 font-semibold">
                                        <option value="Spouse">Spouse</option>
                                        <option value="Child">Child</option>
                                        <option value="Parent">Parent</option>
                                    </select>
                                    <input type="date" :name="'dependents['+idx+'][birth_date]'" x-model="dep.birth_date" class="bg-white border border-gray-200 rounded-lg px-2 py-1.5">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 font-bold mb-0.5">PSA Birth Certificate</label>
                                    <input type="file" :name="'dependents['+idx+'][birth_cert]'" accept="image/*,.pdf" class="w-full text-[11px]">
                                </div>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Additional Notes</label>
                        <textarea name="notes" rows="2" placeholder="e.g. Regularized employee medical application..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showApplyModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-sm">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: FULL DIGITAL HMO CARD & ACCREDITED HOSPITALS -->
        <!-- ========================================================================= -->
        @if($digitalCardPayload)
        <div x-show="showCardModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showCardModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-5 border border-gray-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Official Digital HMO Health Card</h2>
                    </div>
                    <button @click="showCardModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <!-- Digital Health Card Graphic -->
                <div class="bg-gradient-to-br from-gray-950 via-gray-900 to-gray-800 text-white rounded-2xl p-6 shadow-2xl space-y-4 relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="font-outfit font-black tracking-wider text-xs text-emerald-400">TRIPWISE FLEET HEALTHCARE</span>
                        <span class="text-[10px] font-bold text-gray-300 font-mono">{{ $digitalCardPayload['provider_name'] }}</span>
                    </div>

                    <div>
                        <p class="text-[9px] text-gray-400 uppercase tracking-widest font-mono">Member ID / Card No.</p>
                        <p class="text-xl font-black font-mono tracking-wider mt-0.5">{{ $digitalCardPayload['card_number'] }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-white/10 text-xs">
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase">Employee</p>
                            <p class="font-bold text-white">{{ $digitalCardPayload['employee_name'] }}</p>
                            <p class="text-[10px] text-gray-400 font-mono">{{ $digitalCardPayload['employee_code'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] text-gray-400 uppercase">Plan Tier</p>
                            <p class="font-bold text-emerald-400">{{ $digitalCardPayload['plan_tier'] }}</p>
                            <p class="text-[10px] text-gray-400">Valid: {{ $digitalCardPayload['coverage_end'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- MBL Balance Breakdown -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/60 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 font-semibold">Total Annual MBL Limit:</span>
                        <span class="font-black text-gray-900 font-outfit text-sm">PHP {{ number_format($digitalCardPayload['mbl_limit'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 font-semibold">Claims Utilized to Date:</span>
                        <span class="font-bold text-rose-600 font-outfit">-PHP {{ number_format($digitalCardPayload['mbl_utilized'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-gray-200">
                        <span class="text-gray-700 font-bold">Remaining Available Limit:</span>
                        <span class="font-black text-emerald-700 font-outfit text-base">PHP {{ number_format($digitalCardPayload['mbl_remaining'], 2) }}</span>
                    </div>
                </div>

                <!-- Top Emergency Accredited Hospitals -->
                <div class="space-y-2 text-xs">
                    <h4 class="font-bold text-gray-900">24/7 Emergency Accredited Facilities</h4>
                    <div class="space-y-1.5">
                        @foreach($digitalCardPayload['emergency_facilities'] as $fac)
                            <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between text-[11px]">
                                <div>
                                    <p class="font-bold text-gray-900">{{ $fac->name }}</p>
                                    <p class="text-gray-400 text-[10px]">{{ $fac->region }}</p>
                                </div>
                                <span class="font-mono font-bold text-emerald-700">{{ $fac->contact_number ?: '24/7 ER' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button @click="showCardModal = false" type="button" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs py-2.5 rounded-xl transition-all">Close Digital Card</button>
            </div>
        </div>
        @endif

        <!-- ========================================================================= -->
        <!-- MODAL: ESS FILE CLAIM & UPLOAD RECEIPT (3-STEP GUIDED WIZARD) -->
        <!-- ========================================================================= -->
        <div x-show="showClaimModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showClaimModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-6 space-y-5 border border-gray-100 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">File Claim & Reimbursement</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Submit your official receipt or statutory benefit form for HR validation</p>
                    </div>
                    <button @click="showClaimModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <!-- 3-Step Progression Bar -->
                <div class="grid grid-cols-3 gap-2">
                    <div :class="claimStep >= 1 ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-gray-200 text-gray-400 bg-white'" class="border-2 rounded-xl p-2 text-center transition-all">
                        <span class="block text-[10px] font-mono font-bold uppercase tracking-wider">Step 1</span>
                        <span class="text-xs font-black">Claim Details</span>
                    </div>
                    <div :class="claimStep >= 2 ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-gray-200 text-gray-400 bg-white'" class="border-2 rounded-xl p-2 text-center transition-all">
                        <span class="block text-[10px] font-mono font-bold uppercase tracking-wider">Step 2</span>
                        <span class="text-xs font-black">Proof & Docs</span>
                    </div>
                    <div :class="claimStep >= 3 ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-gray-200 text-gray-400 bg-white'" class="border-2 rounded-xl p-2 text-center transition-all">
                        <span class="block text-[10px] font-mono font-bold uppercase tracking-wider">Step 3</span>
                        <span class="text-xs font-black">Live Review</span>
                    </div>
                </div>

                <form action="{{ route('ess.claims.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                    <input type="hidden" name="type" :value="claimType">

                    <!-- STEP 1: Claim Type & Details -->
                    <div x-show="claimStep === 1" class="space-y-4">
                        <!-- Claim Type Selection Tabs -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Select Claim Type</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 bg-gray-100 p-1 rounded-2xl">
                                <button type="button" @click="claimType = 'expense'" 
                                        :class="claimType === 'expense' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                        class="py-2 px-2 text-[11px] rounded-xl transition-all text-center cursor-pointer">
                                    Travel & Fuel
                                </button>
                                <button type="button" @click="claimType = 'medical'" 
                                        :class="claimType === 'medical' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                        class="py-2 px-2 text-[11px] rounded-xl transition-all text-center cursor-pointer">
                                    Medical Aid
                                </button>
                                <button type="button" @click="claimType = 'maternity'" 
                                        :class="claimType === 'maternity' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                        class="py-2 px-2 text-[11px] rounded-xl transition-all text-center cursor-pointer">
                                    Maternity
                                </button>
                                <button type="button" @click="claimType = 'accident'" 
                                        :class="claimType === 'accident' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                        class="py-2 px-2 text-[11px] rounded-xl transition-all text-center cursor-pointer">
                                    Accident Aid
                                </button>
                            </div>
                        </div>

                        <!-- PANEL A: GENERAL EXPENSE / FUEL / TOLL -->
                        <template x-if="claimType === 'expense'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Expense Category *</label>
                                    <select name="category_id" x-model="claimCategoryId" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                        <option value="">Select Category...</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }} ({{ ucfirst($cat->type) }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- If Fuel Selected: Distance & Consumption Calculator -->
                                <div x-show="isFuelSelected" class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-900">Driver Trip Fuel Details</span>
                                        <span class="text-[11px] text-gray-500 font-mono">Formula: (km ÷ km/L) × Price</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Distance (km)</label>
                                            <input type="number" step="0.1" name="distance_traveled_km" x-model.number="claimDistance" placeholder="e.g. 250" class="w-full text-xs bg-white border border-gray-200 rounded-xl px-3 py-1.5 font-mono font-bold text-gray-900">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Efficiency (km/L)</label>
                                            <input type="number" step="0.1" name="vehicle_fuel_efficiency_kpl" x-model.number="claimFuelEfficiency" class="w-full text-xs bg-white border border-gray-200 rounded-xl px-3 py-1.5 font-mono font-bold text-gray-900">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Pump Price (PHP/L)</label>
                                            <input type="number" step="0.01" name="fuel_pump_price" x-model.number="claimFuelPrice" class="w-full text-xs bg-white border border-gray-200 rounded-xl px-3 py-1.5 font-mono font-bold text-gray-900">
                                        </div>
                                    </div>

                                    <div class="p-3 bg-gray-900 text-white rounded-xl flex items-center justify-between text-xs font-mono">
                                        <span class="text-gray-300">Expected Fuel Cost:</span>
                                        <span class="font-black text-emerald-400" x-text="claimDistance > 0 ? 'PHP ' + expectedFuelCost : 'Enter distance...'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PANEL B: MEDICAL AID WITH DE MINIMIS ANNUAL LIMIT BAR -->
                        <template x-if="claimType === 'medical'">
                            <div class="space-y-4">
                                <!-- De Minimis PHP 10k Annual Cap Tracker -->
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Tax-Free Medical Allowance Progress</span>
                                        <span class="font-mono font-bold text-gray-700">PHP {{ number_format($medicalUtilized, 2) }} / PHP {{ number_format($medicalCap, 2) }}</span>
                                    </div>
                                    <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-600 rounded-full transition-all" style="width: {{ min(100, round(($medicalUtilized / max(1, $medicalCap)) * 100)) }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                                        <span>Annual De Minimis Cap (100% Tax-Exempt)</span>
                                        <span class="font-bold text-emerald-700">Remaining: PHP {{ number_format(max(0, $medicalCap - $medicalUtilized), 2) }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Doctor / Clinic Name *</label>
                                        <input type="text" name="merchant_name" placeholder="e.g. St. Luke's Medical Clinic" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Physician PRC License No.</label>
                                        <input type="text" name="physician_license_no" placeholder="e.g. PRC-0189234" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono text-gray-800 focus:outline-none focus:border-gray-900">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PANEL C: RA 11210 MATERNITY BENEFIT ADVANCE -->
                        <template x-if="claimType === 'maternity'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Maternity Contingency Type *</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <button type="button" @click="maternityType = 'normal_caesarean'"
                                                :class="maternityType === 'normal_caesarean' ? 'border-gray-900 bg-gray-900 text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-700'"
                                                class="border p-2.5 rounded-xl text-left transition-all cursor-pointer">
                                            <p class="font-bold text-xs">105-Day Birth</p>
                                            <p class="text-[10px] opacity-80 mt-0.5">Full Pay Childbirth</p>
                                        </button>
                                        <button type="button" @click="maternityType = 'solo_parent'"
                                                :class="maternityType === 'solo_parent' ? 'border-gray-900 bg-gray-900 text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-700'"
                                                class="border p-2.5 rounded-xl text-left transition-all cursor-pointer">
                                            <p class="font-bold text-xs">120-Day Solo Parent</p>
                                            <p class="text-[10px] opacity-80 mt-0.5">+15 Days RA 8972</p>
                                        </button>
                                        <button type="button" @click="maternityType = 'miscarriage_emergency'"
                                                :class="maternityType === 'miscarriage_emergency' ? 'border-gray-900 bg-gray-900 text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-700'"
                                                class="border p-2.5 rounded-xl text-left transition-all cursor-pointer">
                                            <p class="font-bold text-xs">60-Day Emergency</p>
                                            <p class="text-[10px] opacity-80 mt-0.5">Miscarriage Care</p>
                                        </button>
                                    </div>
                                    <input type="hidden" name="maternity_type" :value="maternityType">
                                </div>

                                <!-- Live RA 11210 Advance Preview Card -->
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-900 font-outfit">RA 11210 Employer Advance Calculation</span>
                                        <span class="px-2 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-black rounded-full" x-text="(currentMaternity.leave_days || 105) + ' Days'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200">
                                        <div>
                                            <p class="text-gray-500 text-[10px]">SSS Advance Share:</p>
                                            <p class="font-bold font-mono text-gray-900" x-text="'PHP ' + Number(currentMaternity.sss_maternity_share || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-[10px]">Company Top-Up:</p>
                                            <p class="font-bold font-mono text-emerald-700" x-text="'PHP ' + Number(currentMaternity.company_salary_differential || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                                        </div>
                                    </div>
                                    <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                                        <span class="font-bold text-gray-800">Total 100% Wage Advance:</span>
                                        <span class="font-black font-mono text-gray-900 text-sm" x-text="'PHP ' + Number(currentMaternity.full_pay_replacement || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Attending OB-GYN Doctor</label>
                                        <input type="text" name="merchant_name" placeholder="e.g. Dr. Maria Santos, MD" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Doctor PRC License No. *</label>
                                        <input type="text" name="physician_license_no" placeholder="e.g. PRC-0094123" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono text-gray-800 focus:outline-none focus:border-gray-900 font-bold">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PANEL D: DRIVER ACCIDENT & ROAD INCIDENT RELIEF -->
                        <template x-if="claimType === 'accident'">
                            <div class="space-y-4">
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-[11px] text-amber-900 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <p>Emergency assistance from the <strong>Driver Insurance Pool</strong> for active on-duty road incidents and emergency medical bills.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Incident Location</label>
                                        <input type="text" name="incident_location" placeholder="e.g. C5 Southbound near Ortigas" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Hospital / Clinic Attended</label>
                                        <input type="text" name="hospital_name" placeholder="e.g. Medical City Emergency Room" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Common Date & Description Inputs -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Expense / Incident Date *</label>
                            <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900 font-bold">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Description / Clinical Purpose</label>
                            <textarea name="description" rows="2" placeholder="Provide any additional notes or clinical context..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" @click="showClaimModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                            <button type="button" @click="if (claimType === 'maternity' && !claimAmount) { claimAmount = currentMaternity.full_pay_replacement || 0; } claimStep = 2" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                Next: Proof & Receipt
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Receipt & File Dropzone -->
                    <div x-show="claimStep === 2" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Receipt / Reference No. *</label>
                                <input type="text" name="receipt_number" required placeholder="e.g. OR-2026-9042 or MAT-1-REF" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Claim Amount (PHP) *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" x-model.number="claimAmount" required placeholder="e.g. 1500.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-black text-gray-900 text-sm focus:outline-none focus:border-gray-900">
                            </div>
                        </div>

                        <!-- Drag and Drop Receipt Box with Live Visual Thumbnail -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <span x-show="claimType === 'maternity'">Attach SSS Mat-1 Notice / Doctor Medical Certificate *</span>
                                <span x-show="claimType === 'accident'">Attach Police Blotter / Repair / Hospital Bill *</span>
                                <span x-show="claimType !== 'maternity' && claimType !== 'accident'">Attach Official Receipt Photo or PDF *</span>
                            </label>
                            
                            <div class="border-2 border-dashed border-gray-200 hover:border-gray-400 rounded-2xl p-4 text-center bg-gray-50 transition-all relative"
                                 @dragover.prevent=""
                                 @drop.prevent="handleReceiptUpload($event)">
                                <input type="file" name="receipt_file" x-ref="receiptInput" @change="handleReceiptUpload($event)" accept="image/*,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                
                                <template x-if="!receiptPreview && !receiptFileName">
                                    <div class="space-y-1.5 py-3">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-gray-200 text-gray-600 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-800">Drag and drop your supporting document here, or browse</p>
                                        <p class="text-[10px] text-gray-400">Supports JPG, PNG, and PDF (Max 10 MB)</p>
                                    </div>
                                </template>

                                <!-- Visual Thumbnail Preview -->
                                <template x-if="receiptPreview || receiptFileName">
                                    <div class="flex items-center gap-3 text-left p-2 bg-white rounded-xl border border-gray-200">
                                        <template x-if="receiptPreview">
                                            <img :src="receiptPreview" alt="Receipt Preview" class="w-14 h-14 object-cover rounded-lg border border-gray-200 shadow-xs flex-shrink-0">
                                        </template>
                                        <template x-if="!receiptPreview">
                                            <div class="w-14 h-14 bg-gray-900 text-white rounded-lg flex items-center justify-center font-mono font-bold text-xs flex-shrink-0">PDF</div>
                                        </template>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-gray-900 truncate" x-text="receiptFileName"></p>
                                            <p class="text-[10px] text-gray-400" x-text="receiptFileSize"></p>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-black rounded-full">Ready for Upload</span>
                                        </div>
                                        <button type="button" @click.stop="clearReceipt()" class="text-xs font-bold text-rose-600 hover:text-rose-800 p-2 cursor-pointer">Remove</button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" @click="claimStep = 1" class="text-xs font-bold text-gray-600 hover:text-gray-900 px-3 py-2 flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Back
                            </button>
                            <button type="button" @click="claimStep = 3" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                Next: Live Review
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Live Review & Submission -->
                    <div x-show="claimStep === 3" class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-3 text-xs">
                            <h4 class="font-black text-gray-900 border-b border-gray-200 pb-2">Claim Summary Breakdown</h4>

                            <div class="flex justify-between">
                                <span class="text-gray-500">Employee:</span>
                                <span class="font-bold text-gray-900">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">Total Claimed Amount:</span>
                                <span class="font-black text-base font-mono text-gray-900" x-text="claimAmount ? 'PHP ' + parseFloat(claimAmount).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'PHP 0.00'"></span>
                            </div>

                            <!-- Live Medical Breakdown -->
                            <template x-if="claimType === 'medical'">
                                <div class="pt-2 border-t border-gray-200 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Non-Taxable Portion:</span>
                                        <span class="font-bold text-emerald-700 font-mono" x-text="'PHP ' + Number(medicalNonTaxable).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <template x-if="parseFloat(medicalTaxable) > 0">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Taxable Portion (Over Cap):</span>
                                            <span class="font-bold text-indigo-700 font-mono" x-text="'PHP ' + Number(medicalTaxable).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Live Maternity Breakdown -->
                            <template x-if="claimType === 'maternity'">
                                <div class="pt-2 border-t border-gray-200 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">SSS Advance Share:</span>
                                        <span class="font-bold text-gray-900 font-mono" x-text="'PHP ' + Number(currentMaternity.sss_maternity_share || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Company Top-Up:</span>
                                        <span class="font-bold text-emerald-700 font-mono" x-text="'PHP ' + Number(currentMaternity.company_salary_differential || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Live Fuel Breakdown -->
                            <template x-if="claimType === 'expense' && isFuelSelected">
                                <div class="pt-2 border-t border-gray-200 space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Gas Cost Checker:</span>
                                        <span class="font-bold font-mono" x-text="'Expected PHP ' + expectedFuelCost + ' (' + claimDistance + ' km)'"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">Variance Check:</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black"
                                              :class="isWithinTolerance ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                              x-text="isWithinTolerance ? 'Auto-Verified (Variance: ' + (variancePercentage >= 0 ? '+' : '') + variancePercentage + '%)' : 'Needs HR Review (Variance: +' + variancePercentage + '%)'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-[11px] text-blue-900 flex items-start gap-2">
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>Once submitted, this claim will enter <strong>HR Review</strong> and appear with live tracking on your dashboard.</p>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" @click="claimStep = 2" class="text-xs font-bold text-gray-600 hover:text-gray-900 px-3 py-2 flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Back
                            </button>
                            <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-6 py-3 rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Submit Claim to HR
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: EMERGENCY HOSPITAL LETTER OF AUTHORIZATION (LOA) REQUEST -->
        <!-- ========================================================================= -->
        <div x-show="showLoaModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showLoaModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></span>
                        <div>
                            <h2 class="text-base font-black font-outfit text-gray-900">Request Emergency Hospital LOA</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Instant cashless hospitalization & emergency specialist authorization</p>
                        </div>
                    </div>
                    <button @click="showLoaModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-900 space-y-1">
                    <p class="font-bold">Emergency 24/7 Hospital Admission Guide</p>
                    <p class="text-[11px] opacity-90">Upon submission, an automated Letter of Authorization (LOA) is generated and transmitted to the accredited hospital's HMO billing department.</p>
                </div>

                <form action="{{ route('ess.loa.request') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <!-- Patient Selector: Self vs Dependent -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Who is the Patient? *</label>
                        <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-2xl">
                            <button type="button" @click="patientType = 'employee'"
                                    :class="patientType === 'employee' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                    class="py-2 text-xs rounded-xl transition-all text-center cursor-pointer">
                                Myself (Employee)
                            </button>
                            <button type="button" @click="patientType = 'dependent'"
                                    :class="patientType === 'dependent' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-600 hover:text-gray-900 font-bold'"
                                    class="py-2 text-xs rounded-xl transition-all text-center cursor-pointer">
                                Enrolled Family Dependent
                            </button>
                        </div>
                        <input type="hidden" name="patient_type" :value="patientType">
                    </div>

                    <!-- Dependent Selector if dependent chosen -->
                    <div x-show="patientType === 'dependent'" class="p-3 bg-gray-50 rounded-2xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Enrolled Dependent *</label>
                        @if($hmo && $hmo->dependents->isNotEmpty())
                            <select name="dependent_id" class="w-full text-xs bg-white border border-gray-200 rounded-xl px-3.5 py-2 font-bold text-gray-800 focus:outline-none focus:border-gray-900">
                                @foreach($hmo->dependents as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->full_name }} ({{ $dep->relationship }})</option>
                                @endforeach
                            </select>
                        @else
                            <p class="text-xs text-amber-700 font-bold">No verified dependents found under your policy. Please enroll your dependent first.</p>
                        @endif
                    </div>

                    <!-- Hospital / Facility Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Accredited Hospital / Medical Center *</label>
                        <select name="hospital_name" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            <option value="">Select Accredited Hospital...</option>
                            @foreach($accreditedFacilities as $facility)
                                <option value="{{ $facility->name }}">{{ $facility->name }} ({{ $facility->region }})</option>
                            @endforeach
                            <option value="St. Luke's Medical Center - Global City">St. Luke's Medical Center - Global City</option>
                            <option value="Makati Medical Center">Makati Medical Center</option>
                            <option value="The Medical City - Ortigas">The Medical City - Ortigas</option>
                            <option value="Cardinal Santos Medical Center">Cardinal Santos Medical Center</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Attending Physician / Specialist</label>
                            <input type="text" name="attending_physician" placeholder="e.g. Dr. Roberto Tan, MD" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Estimated Hospital Cost (PHP)</label>
                            <input type="number" step="0.01" name="estimated_amount" placeholder="e.g. 25000.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Chief Complaint / Admitting Diagnosis *</label>
                        <textarea name="diagnosis" rows="2" required placeholder="Describe symptoms, reason for hospital ER admission, or doctor diagnosis..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Attach Doctor Order / ER Admission Slip (Optional)</label>
                        <input type="file" name="doctor_order_file" accept="image/*,.pdf" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-700">
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <button @click="showLoaModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-6 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                            Generate & Transmit Emergency LOA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ANNUAL PHYSICAL EXAM (APE) CLINIC APPOINTMENT SCHEDULER -->
        <!-- ========================================================================= -->
        <div x-show="showApeModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showApeModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Book Annual Physical Exam (APE)</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Select your preferred accredited diagnostic clinic and appointment schedule</p>
                    </div>
                    <button @click="showApeModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form action="{{ route('ess.ape.schedule') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Diagnostic Facility / Clinic *</label>
                        <select name="facility_name" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            <option value="St. Luke's Medical Center - BGC">St. Luke's Medical Center - BGC (Global City)</option>
                            <option value="Makati Medical Center HealthHub">Makati Medical Center HealthHub</option>
                            <option value="The Medical City - Diagnostic Center Ortigas">The Medical City - Diagnostic Center Ortigas</option>
                            <option value="Hi-Precision Diagnostics Plus - Megamall">Hi-Precision Diagnostics Plus - Megamall</option>
                            <option value="MyHealth Clinic - BGC Branch">MyHealth Clinic - BGC Branch</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Appointment Date *</label>
                            <input type="date" name="schedule_date" value="{{ now()->addDays(3)->toDateString() }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Time Slot *</label>
                            <select name="time_slot" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                <option value="07:00 AM - 09:00 AM">07:00 AM - 09:00 AM (Fasting)</option>
                                <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM (Morning)</option>
                                <option value="01:00 PM - 03:00 PM">01:00 PM - 03:00 PM (Afternoon)</option>
                                <option value="03:00 PM - 05:00 PM">03:00 PM - 05:00 PM (Late Afternoon)</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-2xl text-[11px] text-blue-900 space-y-1">
                        <p class="font-bold">Reminders for Examination Day:</p>
                        <p>1. Fast for 8 to 10 hours prior to your scheduled blood extraction.</p>
                        <p>2. Present your company ID and Digital HMO QR code upon arrival at the triage desk.</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <button @click="showApeModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                            Confirm Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: GROUP LIFE INSURANCE BENEFICIARY DESIGNATION -->
        <!-- ========================================================================= -->
        <div x-show="showBeneficiaryModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showBeneficiaryModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Designate Life Insurance Beneficiaries</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Assign primary and secondary beneficiaries for corporate life coverage</p>
                    </div>
                    <button @click="showBeneficiaryModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <div class="p-3 bg-purple-50 border border-purple-100 rounded-2xl text-xs space-y-1">
                    <span class="font-bold text-purple-900">Active Sum Assured: PHP {{ number_format((float)($groupLife->sum_assured ?? 500000.00), 2) }}</span>
                    <p class="text-[11px] text-purple-700">100% company-subsidized Group Term Life with Accidental Death & Dismemberment (AD&D) rider.</p>
                </div>

                <form action="{{ route('ess.life.beneficiaries') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                    <div class="space-y-3">
                        <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider">Primary Beneficiary (100% Allocation) *</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Full Legal Name *</label>
                                <input type="text" name="beneficiary_primary_name" value="{{ $groupLife->beneficiary_primary_name ?? '' }}" required placeholder="e.g. Maria Montes" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Relationship *</label>
                                <select name="beneficiary_primary_relation" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                    <option value="Spouse" {{ ($groupLife->beneficiary_primary_relation ?? '') === 'Spouse' ? 'selected' : '' }}>Spouse</option>
                                    <option value="Child" {{ ($groupLife->beneficiary_primary_relation ?? '') === 'Child' ? 'selected' : '' }}>Child</option>
                                    <option value="Mother" {{ ($groupLife->beneficiary_primary_relation ?? '') === 'Mother' ? 'selected' : '' }}>Mother</option>
                                    <option value="Father" {{ ($groupLife->beneficiary_primary_relation ?? '') === 'Father' ? 'selected' : '' }}>Father</option>
                                    <option value="Sibling" {{ ($groupLife->beneficiary_primary_relation ?? '') === 'Sibling' ? 'selected' : '' }}>Sibling</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-gray-100">
                        <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider">Secondary / Contingent Beneficiary (Optional)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Full Legal Name</label>
                                <input type="text" name="beneficiary_secondary_name" value="{{ $groupLife->beneficiary_secondary_name ?? '' }}" placeholder="e.g. Juan Montes Jr." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Relationship</label>
                                <select name="beneficiary_secondary_relation" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                    <option value="">None</option>
                                    <option value="Child" {{ ($groupLife->beneficiary_secondary_relation ?? '') === 'Child' ? 'selected' : '' }}>Child</option>
                                    <option value="Spouse" {{ ($groupLife->beneficiary_secondary_relation ?? '') === 'Spouse' ? 'selected' : '' }}>Spouse</option>
                                    <option value="Sibling" {{ ($groupLife->beneficiary_secondary_relation ?? '') === 'Sibling' ? 'selected' : '' }}>Sibling</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <button @click="showBeneficiaryModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">
                            Save Beneficiaries
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @endif

@endsection
