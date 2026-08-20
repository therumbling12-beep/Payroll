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
            <p class="text-xs text-gray-500 mt-0.5">View your transparent compensation breakdown, submit reimbursement claims, and track live approval status.</p>
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
        showClaimModal: false,
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

        <!-- Left Column: Profile Summary & Bank Setup -->
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
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->department->name ?? 'General Fleet' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Email:</span>
                        <span class="font-medium text-gray-800">{{ $selectedEmployee->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="font-bold text-emerald-700 uppercase text-[10px]">{{ $selectedEmployee->employment_status }}</span>
                    </div>
                </div>
            </div>

            <!-- Service Incentive Leave (SIL) Balance Card (DOLE Art. 95) -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 font-outfit">Service Incentive Leave</h3>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                        DOLE Art. 95
                    </span>
                </div>

                @if($silRecord && $silRecord->entitled_days > 0)
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="bg-emerald-50/70 border border-emerald-100 rounded-xl p-3">
                            <span class="text-[10px] font-bold text-emerald-700 uppercase block">Available Balance</span>
                            <span class="text-xl font-black font-outfit text-emerald-900">{{ $silRecord->remaining_days }} Days</span>
                        </div>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                            <span class="text-[10px] font-bold text-gray-400 uppercase block">Annual Entitlement</span>
                            <span class="text-xl font-black font-outfit text-gray-800">{{ $silRecord->entitled_days }} Days</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-[11px] text-gray-500 border-t border-gray-50 pt-2.5">
                        <div class="flex justify-between">
                            <span>Leaves Taken This Year:</span>
                            <span class="font-bold text-rose-600 font-mono">{{ $silRecord->used_days }} day(s)</span>
                        </div>
                        @if($silRecord->cash_converted_days > 0)
                            <div class="flex justify-between">
                                <span>Commuted to Cash:</span>
                                <span class="font-bold text-indigo-600 font-mono">{{ $silRecord->cash_converted_days }} day(s) (₱{{ number_format($silRecord->cash_converted_amount, 2) }})</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span>Cash Equivalent Value:</span>
                            <span class="font-bold text-emerald-700 font-mono">PHP {{ number_format($silRecord->remaining_days * ($selectedEmployee->daily_rate ?: ($selectedEmployee->monthly_rate ? $selectedEmployee->monthly_rate / 26 : 0)), 2) }}</span>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100 text-center">
                        <p class="text-xs font-bold text-gray-700">Not Yet Qualified for SIL</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">SIL requires at least 1.0 full year of active service (Current: {{ $selectedEmployee->tenure_text }}).</p>
                    </div>
                @endif
            </div>

            <!-- Christmas Bonus (Year-End Gratuity) Projection Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 font-outfit">Christmas Bonus</h3>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                        Year-End {{ date('Y') }}
                    </span>
                </div>

                @if($christmasBonusProjection && $christmasBonusProjection['is_qualified'])
                    <div class="bg-rose-50/70 border border-rose-100 rounded-xl p-3 mb-3">
                        <span class="text-[10px] font-bold text-rose-700 uppercase block">Projected Bonus Payout</span>
                        <span class="text-xl font-black font-outfit text-rose-900">PHP {{ number_format($christmasBonusProjection['calculated_bonus_amount'], 2) }}</span>
                        <span class="text-[10px] text-rose-700/80 mt-0.5 block">
                            @if($christmasBonusProjection['is_prorated'])
                                Pro-rated based on {{ $christmasBonusProjection['months_tenure'] }} months service
                            @else
                                Full annual qualification (&ge; 6 mos tenure)
                            @endif
                        </span>
                    </div>

                    <div class="space-y-1.5 text-[11px] text-gray-500 border-t border-gray-50 pt-2.5">
                        <div class="flex justify-between">
                            <span>Standard Full Bonus:</span>
                            <span class="font-bold text-gray-700 font-mono">PHP {{ number_format($christmasBonusProjection['base_bonus_amount'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Service Tenure:</span>
                            <span class="font-bold text-gray-800 font-mono">{{ $christmasBonusProjection['months_tenure'] }} month(s)</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Status:</span>
                            <span class="font-bold text-emerald-700 uppercase text-[10px]">
                                {{ $christmasBonusProjection['is_prorated'] ? 'Pro-Rated Qualified' : 'Fully Qualified' }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100 text-center">
                        <p class="text-xs font-bold text-gray-700">Probationary Bonus Status</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Christmas bonus requires at least 1 month of service in the current year.</p>
                    </div>
                @endif
            </div>

            @if($driverPoolHistory)
                <!-- Driver Accident Insurance Pool & Claims Card -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-gray-900 font-outfit">Driver Accident Insurance Pool</h3>
                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">
                            Driver Pool Coverage
                        </span>
                    </div>

                    <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-3 mb-3">
                        <span class="text-[10px] font-bold text-amber-700 uppercase block">Total Pool Credit (With Match)</span>
                        <span class="text-xl font-black font-outfit text-amber-900">PHP {{ number_format($driverPoolHistory['total_pool_credit'], 2) }}</span>
                        <span class="text-[10px] text-amber-700/80 mt-0.5 block">
                            Includes PHP {{ number_format($driverPoolHistory['company_match_total'], 2) }} company matching
                        </span>
                    </div>

                    <div class="space-y-1.5 text-[11px] text-gray-500 border-t border-gray-50 pt-2.5">
                        <div class="flex justify-between">
                            <span>Your Contributions:</span>
                            <span class="font-bold text-gray-700 font-mono">PHP {{ number_format($driverPoolHistory['total_contributed'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Accident Claims Filed:</span>
                            <span class="font-bold text-gray-800 font-mono">{{ $driverPoolHistory['claims_count'] }} claim(s)</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Claims Paid:</span>
                            <span class="font-bold text-emerald-700 font-mono">PHP {{ number_format($driverPoolHistory['claims_disbursed_total'], 2) }}</span>
                        </div>
                    </div>

                    @if($driverPoolHistory['claims']->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Active Claim Tracker</span>
                            <div class="space-y-2">
                                @foreach($driverPoolHistory['claims']->take(3) as $c)
                                    <div class="bg-gray-50 rounded-xl p-2.5 flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-mono font-bold text-gray-800">{{ $c->incident_number }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $c->incident_date ? $c->incident_date->format('M j, Y') : 'N/A' }}</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $c->workflow_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($c->workflow_status === 'returned' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                                {{ str_replace('_', ' ', $c->workflow_status) }}
                                            </span>
                                            <div class="font-mono font-bold text-gray-900 mt-0.5">₱{{ number_format((float) $c->bill_amount, 2) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Security Bank Payroll Account Setup Card -->
            <div x-data="{
                bankProofPreview: null,
                bankFileName: '',
                handleProofUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.bankFileName = file.name;
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = (e) => { this.bankProofPreview = e.target.result; };
                            reader.readAsDataURL(file);
                        } else {
                            this.bankProofPreview = null;
                        }
                    }
                }
            }" class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 font-outfit">Security Bank Account Setup</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">Submit your Security Bank ATM or account slip for direct salary deposits.</p>
                    </div>
                    @if($selectedEmployee->payment_mode === 'bank')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            Direct Deposit Active
                        </span>
                    @elseif($bankSubmission && $bankSubmission->status === 'pending')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Under HR Verification
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-700">
                            Physical Cash Payout
                        </span>
                    @endif
                </div>

                @if($bankSubmission && $bankSubmission->status === 'rejected')
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                        <div class="font-black text-[11px] uppercase flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Submission Returned by HR
                        </div>
                        <p class="text-[11px]">{{ $bankSubmission->rejection_reason }}</p>
                    </div>
                @endif

                @if($selectedEmployee->payment_mode === 'bank')
                    <div class="p-3.5 bg-emerald-50/50 border border-emerald-100 rounded-xl space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Bank Provider:</span>
                            <span class="font-bold text-gray-900">{{ $selectedEmployee->bank_name ?? 'Security Bank Corporation' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Account Number:</span>
                            @php
                                $acct = $selectedEmployee->bank_account_number ?: ($selectedEmployee->bank_account_no ?: '');
                                $masked = strlen($acct) >= 4 ? str_repeat('*', max(0, strlen($acct) - 4)) . substr($acct, -4) : $acct;
                            @endphp
                            <span class="font-mono font-black text-emerald-900">{{ $masked }}</span>
                        </div>
                        <div class="text-[10px] text-gray-400 pt-1 border-t border-emerald-100">
                            Your weekly payroll net pay is disbursed directly via Security Bank Corporate Batch Transfer.
                        </div>
                    </div>
                @else
                    <form action="{{ route('ess.bank-account.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                        <input type="hidden" name="bank_name" value="Security Bank Corporation">

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Security Bank Account Number *</label>
                            <input type="text" name="account_number" required placeholder="e.g. 0012-3456-7890" 
                                   value="{{ old('account_number', $bankSubmission?->account_number ?? '') }}"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-mono font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            <span class="text-[10px] text-gray-400 block mt-1">Enter the 10-20 digit account number from your Security Bank ATM or deposit slip.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">ATM Card / Account Slip Photo Proof</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-3 text-center bg-gray-50/50 hover:bg-gray-50 transition cursor-pointer relative">
                                <input type="file" name="proof_document" accept="image/*,.pdf" @change="handleProofUpload($event)"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                
                                <template x-if="!bankProofPreview && !bankFileName">
                                    <div class="space-y-1">
                                        <svg class="w-6 h-6 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="text-[11px] font-bold text-gray-600 block">Tap to snap or upload ATM card photo</span>
                                        <span class="text-[10px] text-gray-400 block">JPG, PNG, or PDF up to 5MB</span>
                                    </div>
                                </template>

                                <template x-if="bankProofPreview">
                                    <div class="space-y-1.5">
                                        <img :src="bankProofPreview" class="h-24 mx-auto rounded-lg object-cover border border-gray-200">
                                        <span class="text-[10px] font-mono font-bold text-gray-700 block" x-text="bankFileName"></span>
                                    </div>
                                </template>

                                <template x-if="!bankProofPreview && bankFileName">
                                    <div class="space-y-1">
                                        <span class="text-xs font-bold text-emerald-700" x-text="bankFileName"></span>
                                        <span class="text-[10px] text-gray-400 block">File attached ready to submit</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-black py-2.5 px-4 rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Submit Security Bank Details to HR
                        </button>
                    </form>
                @endif
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

                    <!-- STEP 1: CLAIM TYPE & CATEGORY DETAILS -->
                    <div x-show="claimStep === 1" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Claim Type *</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button type="button" @click="claimType = 'expense'; claimCategoryId = ''" 
                                        :class="claimType === 'expense' ? 'bg-gray-900 text-white font-black' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 font-bold border border-gray-200'"
                                        class="p-2.5 text-xs rounded-xl transition-all text-center cursor-pointer">
                                    Fuel / Expense
                                </button>
                                <button type="button" @click="claimType = 'medical'; claimCategoryId = ''" 
                                        :class="claimType === 'medical' ? 'bg-gray-900 text-white font-black' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 font-bold border border-gray-200'"
                                        class="p-2.5 text-xs rounded-xl transition-all text-center cursor-pointer">
                                    Medical Outpatient
                                </button>
                                <button type="button" @click="claimType = 'accident'; claimCategoryId = ''" 
                                        :class="claimType === 'accident' ? 'bg-gray-900 text-white font-black' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 font-bold border border-gray-200'"
                                        class="p-2.5 text-xs rounded-xl transition-all text-center cursor-pointer">
                                    Driver Relief
                                </button>
                                <button type="button" @click="claimType = 'maternity'; claimCategoryId = ''" 
                                        :class="claimType === 'maternity' ? 'bg-gray-900 text-white font-black' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 font-bold border border-gray-200'"
                                        class="p-2.5 text-xs rounded-xl transition-all text-center cursor-pointer">
                                    Maternity RA 11210
                                </button>
                            </div>
                        </div>

                        <!-- Standard Category Dropdown -->
                        <div x-show="claimType === 'expense'">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Expense Category *</label>
                            <select name="category_id" x-model="claimCategoryId" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                                <option value="">Select Expense Category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ strtoupper($cat->tax_classification) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Amount & Date -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Claim Amount (PHP) *</label>
                                <input type="number" step="0.01" min="1" name="amount" x-model="claimAmount" placeholder="e.g. 1500.00" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Expense / Invoice Date *</label>
                                <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                            </div>
                        </div>

                        <!-- Fuel Verification Auto-Checker -->
                        <div x-show="isFuelSelected" class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-blue-950 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Gas Cost Auto-Checker
                                </span>
                                <span class="text-[10px] font-mono text-blue-700">Tolerance: &plusmn;{{ $fuelSettings['tolerance_pct'] ?? 15 }}%</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <label class="block text-[10px] text-gray-500 font-bold">Distance (KM) *</label>
                                    <input type="number" step="0.1" min="0" name="distance_traveled_km" x-model="claimDistance" placeholder="180" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 font-mono font-bold text-gray-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 font-bold">Pump Price (PHP)</label>
                                    <input type="number" step="0.1" name="fuel_pump_price" x-model="claimFuelPrice" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 font-mono text-gray-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 font-bold">Efficiency (km/L)</label>
                                    <input type="number" step="0.1" name="vehicle_fuel_efficiency_kpl" x-model="claimFuelEfficiency" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 font-mono text-gray-800">
                                </div>
                            </div>

                            <div x-show="claimDistance > 0 && claimAmount > 0" class="p-2.5 rounded-xl border text-[11px]"
                                 :class="isWithinTolerance ? 'bg-emerald-100 border-emerald-300 text-emerald-900' : 'bg-amber-100 border-amber-300 text-amber-900'">
                                <div class="flex justify-between font-bold">
                                    <span x-text="isWithinTolerance ? 'Reasonable Gas Cost (Auto-Approved)' : 'Gas Cost Exceeds Expected Benchmark'"></span>
                                    <span x-text="'Variance: ' + variancePercentage + '%'"></span>
                                </div>
                                <div class="text-[10px] opacity-80 mt-0.5" x-text="'Expected fuel cost: PHP ' + expectedFuelCost + ' (' + estimatedFuelLiters + ' Liters)'"></div>
                            </div>
                        </div>

                        <!-- Merchant & Official Receipt No -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Official Receipt (OR) Number *</label>
                                <input type="text" name="receipt_number" placeholder="e.g. OR-984210" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 font-mono text-gray-900 focus:outline-none focus:border-gray-900">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Merchant / Vendor Name</label>
                                <input type="text" name="merchant_name" placeholder="e.g. Petron, Mercury Drug" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                            </div>
                        </div>

                        <div class="flex justify-end pt-3 border-t border-gray-100">
                            <button type="button" @click="claimStep = 2" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-6 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                Next: Upload Proof
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: PROOF & RECEIPT DROPZONE -->
                    <div x-show="claimStep === 2" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Upload Official Receipt or Medical Slip *</label>
                            
                            <!-- Drag & Drop Dropzone -->
                            <div @dragover.prevent="" @drop.prevent="handleReceiptUpload($event)"
                                 class="border-2 border-dashed border-gray-300 hover:border-gray-900 bg-gray-50/50 rounded-2xl p-6 text-center cursor-pointer transition-all relative">
                                <input type="file" name="receipt_file" x-ref="receiptInput" @change="handleReceiptUpload($event)" accept="image/*,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                
                                <div x-show="!receiptFileName" class="space-y-2">
                                    <div class="w-10 h-10 rounded-2xl bg-gray-200 text-gray-600 flex items-center justify-center mx-auto">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs font-black text-gray-800">Drag & Drop Receipt Here, or <span class="text-blue-600 underline">Browse</span></p>
                                    <p class="text-[10px] text-gray-400">Supports JPG, PNG, PDF receipts up to 10MB</p>
                                </div>

                                <div x-show="receiptFileName" class="space-y-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-xs font-bold text-gray-900" x-text="receiptFileName"></span>
                                        <span class="text-[10px] text-gray-400 font-mono" x-text="'(' + receiptFileSize + ')'"></span>
                                        <button type="button" @click.stop="clearReceipt()" class="text-rose-600 hover:text-rose-800 font-bold text-xs ml-2 cursor-pointer">&times; Remove</button>
                                    </div>
                                    <template x-if="receiptPreview">
                                        <img :src="receiptPreview" class="max-h-36 mx-auto rounded-xl border border-gray-200 shadow-sm">
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Claim Justification / Remarks</label>
                            <textarea name="description" rows="2" placeholder="Briefly describe the business purpose of this expense..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" @click="claimStep = 1" class="text-xs font-bold text-gray-600 hover:text-gray-900 px-3 py-2 flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Back
                            </button>
                            <button type="button" @click="claimStep = 3" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-6 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                Next: Live Review
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: LIVE REVIEW & FINAL SUBMISSION -->
                    <div x-show="claimStep === 3" class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Claimant:</span>
                                <span class="font-bold text-gray-900">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Claim Type:</span>
                                <span class="font-black uppercase text-indigo-700" x-text="claimType"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Total Filing Amount:</span>
                                <span class="font-black font-outfit text-sm text-gray-900" x-text="'PHP ' + (parseFloat(claimAmount || 0)).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Attached Receipt:</span>
                                <span class="font-mono text-emerald-700" x-text="receiptFileName || 'No file attached'"></span>
                            </div>
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

    </div>
    @endif

@endsection
