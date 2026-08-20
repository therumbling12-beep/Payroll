@extends('layouts.app')

@php
    $pageTitle = 'Christmas Bonus Policy & Allocation';
    $currentPage = 'benefits.christmas-bonus';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Christmas Bonus Policy & Year-End Allocation</h1>
            <p class="text-xs text-gray-500 mt-1">Management of annual year-end Christmas bonuses, tenure pro-rating for mid-year hires, and release workflows.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('benefits.christmas-bonus.export', ['year' => $currentYear, 'department_id' => $departmentId]) }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 px-3.5 py-2 rounded-xl shadow-2xs hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Allocation CSV
            </a>
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
        showBatchModal: false,
        showConfigModal: false,
        batchYear: '{{ $currentYear }}',
        batchAmount: '{{ $stats['christmas_bonus_amount'] }}',
        batchMinMonths: '{{ $stats['christmas_bonus_min_months'] }}',
    }" class="space-y-6 pb-12">

        <!-- 4 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Standard Bonus Amount</p>
                <p class="text-2xl font-black font-outfit text-rose-600 mt-1">PHP {{ number_format($stats['christmas_bonus_amount'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Full bonus for &ge; {{ $stats['christmas_bonus_min_months'] }} mos tenure</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Qualified / Entitled (Yr {{ $currentYear }})</p>
                <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">{{ $stats['qualified_count'] }} / {{ $stats['total_active'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Includes pro-rated mid-year hires</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Projected Year Outlay</p>
                <p class="text-2xl font-black font-outfit text-indigo-600 mt-1">PHP {{ number_format($stats['total_projected_outlay'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Calculated with tenure pro-rating</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Actual Disbursed</p>
                <p class="text-2xl font-black font-outfit text-purple-700 mt-1">PHP {{ number_format($stats['total_actual_outlay'], 2) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $stats['total_disbursements_count'] }} records in Year {{ $currentYear }}</p>
            </div>
        </div>

        <!-- Filter Controls & Action Buttons -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form action="{{ route('benefits.christmas-bonus') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
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
                <button @click="showBatchModal = true" type="button" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Batch Generate
                </button>

                @if($stats['pending_count'] > 0)
                    <form action="{{ route('benefits.christmas-bonus.approve') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="bonus_year" value="{{ $currentYear }}">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            HR Approve ({{ $stats['pending_count'] }})
                        </button>
                    </form>
                @endif

                @if($stats['approved_count'] > 0)
                    <form action="{{ route('benefits.christmas-bonus.release') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="bonus_year" value="{{ $currentYear }}">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-3.5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Push to Payroll ({{ $stats['approved_count'] }})
                        </button>
                    </form>
                @endif

                <button @click="showConfigModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                    Policy
                </button>
            </div>
        </div>

        <!-- Christmas Bonus Roster Table -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Employee</th>
                            <th class="py-3.5 px-4">Department / Position</th>
                            <th class="py-3.5 px-4">Hire Date / Service</th>
                            <th class="py-3.5 px-4 text-center">Tenure (Months)</th>
                            <th class="py-3.5 px-4 text-center">Eligibility / Pro-Rata</th>
                            <th class="py-3.5 px-4 text-right">Calculated Bonus</th>
                            <th class="py-3.5 px-4 text-center">Disbursement Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($roster as $row)
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

                                <!-- Hire Date -->
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-gray-800">{{ $row['employee']->hire_date ? $row['employee']->hire_date->format('M j, Y') : 'N/A' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $row['employee']->tenure_text }}</div>
                                </td>

                                <!-- Tenure in Months -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-gray-800">
                                    {{ $row['months_tenure'] }} mos
                                </td>

                                <!-- Eligibility / Pro-Rata -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($row['is_qualified'] && !$row['is_prorated'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Qualified (Full)
                                        </span>
                                    @elseif($row['is_qualified'] && $row['is_prorated'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Pro-Rated ({{ $row['months_tenure'] }}/12)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Probationary (&lt; 1 mo)
                                        </span>
                                    @endif
                                </td>

                                <!-- Calculated Bonus -->
                                <td class="py-3.5 px-4 text-right font-mono font-black font-outfit text-sm {{ $row['calculated_bonus_amount'] > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    PHP {{ number_format($row['calculated_bonus_amount'], 2) }}
                                </td>

                                <!-- Disbursement Status -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($row['status'] === 'released_to_payroll')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disbursed to Payroll
                                        </span>
                                    @elseif($row['status'] === 'hr_approved')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            HR Approved
                                        </span>
                                    @elseif($row['status'] === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                            Unprocessed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-400 text-xs">No employees found for Year {{ $currentYear }}.</td>
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

        <!-- ========================================================================= -->
        <!-- MODAL: BATCH GENERATE CHRISTMAS BONUS ALLOCATIONS -->
        <!-- ========================================================================= -->
        <div x-show="showBatchModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showBatchModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Batch Generate Bonus Allocations</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Calculates full & pro-rated bonuses based on service tenure</p>
                    </div>
                    <button @click="showBatchModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.christmas-bonus.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Target Calendar Year *</label>
                        <input type="number" name="bonus_year" x-model="batchYear" min="2020" max="2099" required class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Standard Full Bonus Amount (PHP) *</label>
                        <input type="number" step="100" min="0" max="50000" name="bonus_amount" x-model="batchAmount" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tenure Threshold for Full Bonus (Months) *</label>
                        <input type="number" min="0" max="60" name="min_months" x-model="batchMinMonths" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">Staff with &lt; this threshold receive pro-rated payouts based on months served.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showBatchModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Generate Allocations</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: CONFIGURE CHRISTMAS BONUS POLICY -->
        <!-- ========================================================================= -->
        <div x-show="showConfigModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showConfigModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Configure Christmas Bonus Policy</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Fixed amount and tenure eligibility rules</p>
                    </div>
                    <button @click="showConfigModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('benefits.christmas-bonus.settings') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Standard Bonus Amount (PHP) *</label>
                        <input type="number" step="100" min="0" max="50000" name="christmas_bonus_amount" value="{{ $stats['christmas_bonus_amount'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Minimum Service Required (Months) *</label>
                        <input type="number" min="0" max="60" name="christmas_bonus_min_months" value="{{ $stats['christmas_bonus_min_months'] }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-900 focus:outline-none focus:border-gray-900">
                        <p class="text-[10px] text-gray-400 mt-1">Employees with service &ge; this threshold qualify for full bonus.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="christmas_bonus_enabled" value="1" id="christmas_bonus_enabled" {{ $stats['christmas_bonus_enabled'] ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="christmas_bonus_enabled" class="text-xs font-bold text-gray-700">Enable Christmas Bonus for upcoming Year-End release</label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showConfigModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm">Save Policy Settings</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
