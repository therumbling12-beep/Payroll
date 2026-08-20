@extends('layouts.app')

@php
    $pageTitle = 'Service Incentive Leave (SIL)';
    $currentPage = 'benefits.sil';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Service Incentive Leave (SIL) Tracker</h1>
            <p class="text-xs text-gray-500 mt-1">DOLE Labor Code Art. 95 statutory 5-day annual paid leave entitlements, leave logging, and cash commutation.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('benefits.sil.export', ['year' => $currentYear, 'department_id' => $departmentId]) }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 px-3.5 py-2 rounded-xl shadow-2xs hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Roster CSV
            </a>

            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                DOLE Art. 95 Compliant
            </span>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js State -->
    <div x-data="{
        showRecordSilModal: false,
        showConvertModal: false,
        showResetModal: false,
        showConfigModal: false,

        selectedEmployee: null,
        selectedEmployeeName: '',
        selectedRemaining: 0,
        selectedDailyRate: 0,

        silDaysToTake: 1,
        silLeaveDate: '{{ now()->format('Y-m-d') }}',
        silNotes: '',

        convertDays: 1,
        get projectedCashPayout() {
            return (this.convertDays * this.selectedDailyRate).toFixed(2);
        },

        openRecordSil(empId, empName, remaining) {
            this.selectedEmployee = empId;
            this.selectedEmployeeName = empName;
            this.selectedRemaining = remaining;
            this.silDaysToTake = Math.min(1, remaining);
            this.silNotes = '';
            this.showRecordSilModal = true;
        },

        openConvertModal(empId, empName, remaining, dailyRate) {
            this.selectedEmployee = empId;
            this.selectedEmployeeName = empName;
            this.selectedRemaining = remaining;
            this.selectedDailyRate = dailyRate;
            this.convertDays = remaining;
            this.showConvertModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Entitled Employees (Yr {{ $stats['year'] }})</p>
                <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">{{ $stats['entitled_sil_count'] }} / {{ $stats['total_active'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Tenure &ge; 1.0 year in active service</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total SIL Pool Granted</p>
                <p class="text-2xl font-black font-outfit text-indigo-600 mt-1">{{ $stats['total_sil_pool_days'] }} Days</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $stats['standard_sil_days'] }} days statutory entitlement</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Leaves Utilized / Converted</p>
                <p class="text-2xl font-black font-outfit text-rose-600 mt-1">{{ $stats['total_sil_days_used'] }} Used / {{ $stats['total_sil_days_converted'] }} Converted</p>
                <p class="text-[11px] text-gray-500 mt-1">PHP {{ number_format($stats['total_converted_amount'], 2) }} commuted</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Available Balance Pool</p>
                <p class="text-2xl font-black font-outfit text-emerald-700 mt-1">{{ $stats['total_sil_days_remaining'] }} Days</p>
                <p class="text-[11px] text-gray-500 mt-1">Eligible for leave or cash conversion</p>
            </div>
        </div>

        <!-- Filter Controls & Action Buttons -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form action="{{ route('benefits.sil') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search employee name or code..." 
                           class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-gray-900">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <select name="department_id" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                    <option value="all">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>

                <select name="year" class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-gray-800 font-bold focus:outline-none focus:border-gray-900">
                    @for($y = (int) date('Y') + 1; $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                    @endfor
                </select>

                <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm">
                    Filter Roster
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <button @click="showResetModal = true" type="button" 
                        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 font-bold text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-2xs flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Year Rollover
                </button>

                <button @click="showConfigModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                    Policy Settings
                </button>
            </div>
        </div>

        <!-- SIL Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Employee</th>
                            <th class="py-3.5 px-4">Department / Position</th>
                            <th class="py-3.5 px-4">Hire Date / Tenure</th>
                            <th class="py-3.5 px-4 text-center">Entitlement</th>
                            <th class="py-3.5 px-4 text-center">Used / Converted</th>
                            <th class="py-3.5 px-4 text-center">Remaining Balance</th>
                            <th class="py-3.5 px-4 text-right">Cash Conversion Value</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($silRoster as $row)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <!-- Employee -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-gray-900">{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $row['employee']->employee_code }}</div>
                                </td>

                                <!-- Department / Position -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ $row['employee']->department?->name ?? 'General Fleet' }}
                                    </span>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ $row['employee']->position }}</div>
                                </td>

                                <!-- Tenure -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-gray-800">{{ $row['tenure_text'] }}</div>
                                    <div class="text-[10px] text-gray-400">Hired: {{ $row['employee']->hire_date ? $row['employee']->hire_date->format('M j, Y') : 'N/A' }}</div>
                                </td>

                                <!-- SIL Entitlement -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($row['is_entitled'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $row['entitled_days'] }} Days
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                            0 Days (Under 1 Year)
                                        </span>
                                    @endif
                                </td>

                                <!-- Used / Converted -->
                                <td class="py-3.5 px-4 text-center font-mono">
                                    <div class="font-bold text-rose-600">{{ $row['used_days'] }}d Used</div>
                                    @if($row['cash_converted_days'] > 0)
                                        <div class="text-[10px] text-indigo-600 font-semibold">{{ $row['cash_converted_days'] }}d Converted (₱{{ number_format($row['cash_converted_amount'], 2) }})</div>
                                    @endif
                                    @if($row['last_leave_date'])
                                        <div class="text-[9px] text-gray-400 font-normal">Last: {{ \Carbon\Carbon::parse($row['last_leave_date'])->format('M j, Y') }}</div>
                                    @endif
                                </td>

                                <!-- Remaining Balance -->
                                <td class="py-3.5 px-4 text-center font-black font-outfit text-sm {{ $row['remaining_days'] > 0 ? 'text-emerald-700' : 'text-gray-400' }}">
                                    {{ $row['remaining_days'] }} Days
                                </td>

                                <!-- Cash Conversion Value -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold">
                                    @if($row['remaining_days'] > 0)
                                        <span class="text-emerald-700">PHP {{ number_format($row['cash_conversion_value'], 2) }}</span>
                                        <div class="text-[10px] text-gray-400 font-normal">₱{{ number_format($row['daily_rate'], 2) }}/day</div>
                                    @else
                                        <span class="text-gray-400">PHP 0.00</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($row['is_entitled'] && $row['remaining_days'] > 0)
                                            <button @click="openRecordSil({{ $row['employee']->id }}, '{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}', {{ $row['remaining_days'] }})" 
                                                    class="bg-gray-900 hover:bg-black text-white font-bold text-[10px] px-2.5 py-1.5 rounded-xl transition-all shadow-2xs">
                                                Log Leave
                                            </button>
                                            <button @click="openConvertModal({{ $row['employee']->id }}, '{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}', {{ $row['remaining_days'] }}, {{ $row['daily_rate'] }})" 
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] px-2.5 py-1.5 rounded-xl transition-all shadow-2xs">
                                                Convert Cash
                                            </button>
                                        @elseif($row['is_entitled'])
                                            <span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-lg border border-gray-200">Exhausted</span>
                                        @else
                                            <span class="text-[10px] font-bold text-gray-400">Probationary</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-gray-400 text-xs">No employee SIL records found for Year {{ $currentYear }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>

        <!-- Statutory Notice Card -->
        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-bold">Statutory Service Incentive Leave Mandate (DOLE Art. 95)</p>
                <p class="text-[11px] text-amber-800/80 mt-0.5">
                    Every employee who has rendered at least one year of service is entitled to a yearly service incentive leave of five (5) days with pay. The unused portion of SIL may be commuted to its cash equivalent at the end of the year or upon separation from the company based on the employee's current daily rate.
                </p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: LOG SERVICE INCENTIVE LEAVE (SIL) -->
        <!-- ========================================================================= -->
        <div x-show="showRecordSilModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showRecordSilModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Record SIL Leave Usage</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Employee: ' + selectedEmployeeName"></p>
                    </div>
                    <button @click="showRecordSilModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.sil.record') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" :value="selectedEmployee">
                    <input type="hidden" name="year" value="{{ $currentYear }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Number of Days Taken *</label>
                        <input type="number" name="days_taken" x-model="silDaysToTake" min="1" :max="selectedRemaining" required class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1" x-text="'Available balance: ' + selectedRemaining + ' day(s)'"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Effective Leave Date *</label>
                        <input type="date" name="leave_date" x-model="silLeaveDate" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Remarks / Reason</label>
                        <textarea name="notes" rows="2" x-model="silNotes" placeholder="e.g. Scheduled annual rest leave (DOLE Art. 95)..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showRecordSilModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Leave Record</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: CONVERT UNUSED SIL TO CASH (DOLE ART. 95) -->
        <!-- ========================================================================= -->
        <div x-show="showConvertModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showConvertModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Convert Unused SIL to Cash</h2>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="'Employee: ' + selectedEmployeeName"></p>
                    </div>
                    <button @click="showConvertModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.sil.convert-cash') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="employee_id" :value="selectedEmployee">
                    <input type="hidden" name="year" value="{{ $currentYear }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Days to Convert *</label>
                        <input type="number" name="days_to_convert" x-model="convertDays" min="1" :max="selectedRemaining" required class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1" x-text="'Max convertible: ' + selectedRemaining + ' day(s)'"></p>
                    </div>

                    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200 space-y-1">
                        <div class="flex justify-between text-xs text-emerald-900">
                            <span>Daily Rate:</span>
                            <span class="font-mono font-bold" x-text="'PHP ' + selectedDailyRate.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-emerald-900">
                            <span>Days to Convert:</span>
                            <span class="font-mono font-bold" x-text="convertDays + ' day(s)'"></span>
                        </div>
                        <div class="flex justify-between text-sm font-black text-emerald-950 pt-2 border-t border-emerald-200">
                            <span>Total Cash Value:</span>
                            <span class="font-mono" x-text="'PHP ' + projectedCashPayout"></span>
                        </div>
                    </div>

                    <p class="text-[10px] text-gray-400">
                        Pursuant to DOLE Art. 95, unused SIL is commuted to its equivalent cash value based on the employee's current salary rate.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showConvertModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Confirm Cash Conversion</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ANNUAL YEAR RESET / ROLLOVER -->
        <!-- ========================================================================= -->
        <div x-show="showResetModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showResetModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Annual SIL Year Rollover</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Initialize 5-day annual entitlement pool for a calendar year</p>
                    </div>
                    <button @click="showResetModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.sil.reset-year') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Calendar Year *</label>
                        <input type="number" name="target_year" value="{{ (int) date('Y') + 1 }}" min="2020" max="2099" required class="w-full text-xs font-black bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">This will initialize fresh SIL records for all active tenured employees for the specified year.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showResetModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Initialize Annual Pool</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: CONFIGURE SIL POLICY -->
        <!-- ========================================================================= -->
        <div x-show="showConfigModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showConfigModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Configure SIL Entitlement Policy</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Annual paid leave days granted per tenured employee</p>
                    </div>
                    <button @click="showConfigModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.sil.settings') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Annual Entitlement Days *</label>
                        <input type="number" min="1" max="30" name="sil_annual_days" value="{{ $stats['standard_sil_days'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800">
                        <p class="text-[10px] text-gray-400 mt-1">DOLE statutory minimum: 5 days with pay after 1 year tenure.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showConfigModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Policy Setting</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
