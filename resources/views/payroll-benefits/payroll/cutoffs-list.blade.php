@extends('layouts.app')

@php
    $pageTitle = 'Payroll Cut-offs';
    $currentPage = 'payroll.salary-computation';
@endphp

@section('content')

    <div x-data="{ showRunModal: false, showTransparencyModal: false }">

        <!-- Page Header & Action Controls -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Payroll Cut-offs & Run Folders</h1>
                <p class="text-xs text-gray-500 mt-0.5">Select a payroll cutoff period folder to inspect employee records, or execute a new batch payroll run.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Formula Transparency Button -->
                <button @click="showTransparencyModal = true" type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Formula Transparency Guide
                </button>

                <!-- Re-Run / New Batch Computation Button -->
                <button @click="showRunModal = true" type="button" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2 whitespace-nowrap shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Payroll Run
                </button>
            </div>
        </div>

        <!-- Cutoffs Grid View -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($cutoffs as $cutoff)
                @php
                    $parts = explode('_', $cutoff->cutoff_period);
                    if (count($parts) === 2) {
                        $startDate = \Carbon\Carbon::parse($parts[0]);
                        $endDay = $parts[1];
                        $formattedPeriod = $startDate->format('F 1') . ' – ' . $startDate->format('F ') . $endDay . ', ' . $startDate->format('Y');
                        $cutoffTag = ($endDay == '15' ? '1st Cutoff' : '2nd Cutoff');
                    } else {
                        $formattedPeriod = $cutoff->cutoff_period;
                        $cutoffTag = 'Payroll Run';
                    }

                    $batchRecord = $batches->get($cutoff->cutoff_period);
                    $statusLabel = $batchRecord ? $batchRecord->status->label() : 'Draft / Unsubmitted';
                    $statusClasses = $batchRecord ? $batchRecord->status->badgeClasses() : 'bg-gray-100 text-gray-700 border-gray-200';
                @endphp

                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group">
                    <div>
                        <!-- Tag & Icon -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider border {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                            <button @click="showTransparencyModal = true" class="text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-100 hover:bg-blue-100 px-2 py-1 rounded-lg transition-colors flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Formula
                            </button>
                        </div>

                        <!-- Cutoff Title -->
                        <h3 class="text-base font-bold font-outfit text-gray-900 mb-1 group-hover:text-[#F44336] transition-colors">
                            {{ $formattedPeriod }}
                        </h3>
                        <p class="text-[11px] text-gray-400 mb-5">Raw Period Code: <span class="font-mono text-gray-600">{{ $cutoff->cutoff_period }}</span></p>

                        <!-- Summary Stats Grid -->
                        <div class="grid grid-cols-2 gap-3 bg-gray-50/80 rounded-xl p-3.5 mb-5 border border-gray-100">
                            <div>
                                <span class="text-[10px] text-gray-400 font-semibold block uppercase">Employees</span>
                                <span class="text-sm font-extrabold text-gray-900 font-outfit">{{ number_format($cutoff->total_employees) }} Drivers/Staff</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 font-semibold block uppercase">Total Net Pay</span>
                                <span class="text-sm font-extrabold text-emerald-600 font-outfit">₱{{ number_format($cutoff->total_net, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Link -->
                    <a href="{{ route('payroll.salary-computation.show', $cutoff->cutoff_period) }}" class="w-full bg-gray-900 hover:bg-[#F44336] text-white font-bold text-xs py-2.5 px-4 rounded-xl transition-all shadow-xs flex items-center justify-center gap-2 group-hover:shadow-md">
                        <span>Open Computation Folder</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Execute Batch Computation Modal -->
        <div x-show="showRunModal" 
             x-transition 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
             style="display: none;"
             x-data="{ selectedPeriod: '2026-07-01_15' }">
            
            <div @click.away="showRunModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-extrabold font-outfit text-gray-900">Payroll Run</h3>
                        <p class="text-xs text-gray-400">Configure target cutoff period, department scope, and compliance options.</p>
                    </div>
                    <button @click="showRunModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>

                <form action="{{ route('payroll.batch-compute') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Cutoff Period Date *</label>
                        <select name="period" x-model="selectedPeriod" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="2026-07-01_15">July 1 – July 15, 2026 (1st Cutoff)</option>
                            <option value="2026-07-16_31">July 16 – July 31, 2026 (2nd Cutoff)</option>
                            <option value="2026-08-01_15">August 1 – August 15, 2026 (1st Cutoff)</option>
                            <option value="2026-08-16_31">August 16 – August 31, 2026 (2nd Cutoff)</option>
                            <option value="2026-09-01_15">September 1 – September 15, 2026 (1st Cutoff)</option>
                            <option value="2026-09-16_30">September 16 – September 30, 2026 (2nd Cutoff)</option>
                            <option value="custom">📅 Custom Cutoff Date Range...</option>
                        </select>
                    </div>

                    <!-- Custom Date Selection Picker -->
                    <div x-show="selectedPeriod === 'custom'" x-transition class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-200">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Start Date *</label>
                            <input type="date" name="start_date" class="w-full text-xs bg-white border border-gray-200 rounded-lg p-2 font-semibold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-1">End Date *</label>
                            <input type="date" name="end_date" class="w-full text-xs bg-white border border-gray-200 rounded-lg p-2 font-semibold text-gray-900 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>

                    <!-- Department Scope Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Employee Scope *</label>
                        <select name="department_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 font-semibold text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="all">Entire Company (All Active Staff & Drivers)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- AI Compliance Audit Toggle Switch -->
                    <div class="flex items-center justify-between bg-blue-50/60 border border-blue-100 rounded-xl p-3.5">
                        <div>
                            <span class="text-xs font-bold text-blue-900 block flex items-center gap-1.5">
                                🤖 Enable AI Regulatory Compliance Audit
                            </span>
                            <span class="text-[10px] text-blue-700">Scan generated records instantly for DOLE compliance risks.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="run_ai_audit" value="1" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-800 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-amber-900">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Batch Run Notice
                        </div>
                        <p class="text-[11px] text-amber-700">Calculating will pull attendance records, compute SSS, PhilHealth, Pag-IBIG & BIR tax, and log audit trail entries into the database.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 mt-6">
                        <button type="button" @click="showRunModal = false" class="text-xs font-bold text-gray-500 hover:text-gray-700 px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Run Batch Calculation Now
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formula Transparency Modal -->
        <div x-show="showTransparencyModal" 
             x-transition 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
             style="display: none;">
            
            <div @click.away="showTransparencyModal = false" class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-extrabold font-outfit text-gray-900">Philippine Statutory Payroll Calculation Formulas</h3>
                        <p class="text-xs text-gray-400">Full legal formula breakdown & transparency rules used by the Payroll Engine.</p>
                    </div>
                    <button @click="showTransparencyModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Formula 1: Gross Pay -->
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <div class="font-extrabold text-gray-900 text-xs mb-1 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px]">1</span>
                            Gross Pay Derivation
                        </div>
                        <p class="text-gray-600 text-[11px] mb-2">Calculates total earnings before any statutory or company deductions.</p>
                        <div class="bg-white border border-gray-200 rounded-lg p-2.5 font-mono text-gray-800 font-bold">
                            Gross Pay = Base Salary + Trip Commissions + Performance Bonuses
                        </div>
                    </div>

                    <!-- Formula 2: Statutory Deductions -->
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <div class="font-extrabold text-gray-900 text-xs mb-1 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-[10px]">2</span>
                            Government Statutory Contributions & Caps
                        </div>
                        <ul class="space-y-2 text-[11px] text-gray-700">
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-gray-900">• SSS:</span> 
                                <span>Employee share is 4.5% of Monthly Salary Credit (MSC), capped at maximum <strong>₱1,350.00</strong> per month (₱675.00 per cutoff).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-gray-900">• PhilHealth:</span> 
                                <span>5% total premium divided equally (2.5% employee share), capped at maximum <strong>₱2,500.00</strong> per month (₱1,250.00 per cutoff).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-gray-900">• Pag-IBIG (HDMF):</span> 
                                <span>Fixed employee contribution capped at <strong>₱200.00</strong> per month (₱100.00 per cutoff).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="font-bold text-gray-900">• BIR Withholding Tax (TRAIN Law):</span> 
                                <span>Calculated on Taxable Income (Gross - SSS - PhilHealth - PagIBIG). 20% tax rate applies to monthly taxable income exceeding ₱20,833.33 (or ₱10,416.67 per cutoff).</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Formula 3: Net Pay -->
                    <div class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-4">
                        <div class="font-extrabold text-emerald-900 text-xs mb-1 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px]">3</span>
                            Final Take-Home Net Pay Formula
                        </div>
                        <div class="bg-white border border-emerald-200 rounded-lg p-2.5 font-mono text-emerald-800 font-bold">
                            Net Pay = (Gross Pay - Total Statutory & HMO Deductions) + Non-Taxable Reimbursements
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 pt-4 mt-6">
                    <button type="button" @click="showTransparencyModal = false" class="bg-gray-900 text-white font-bold text-xs px-5 py-2.5 rounded-xl">
                        Got it, Close Guide
                    </button>
                </div>
            </div>
        </div>

    </div>

@endsection
