@extends('layouts.app')

@php
    $pageTitle = 'Claims Summary Report & Audit Export';
    $currentPage = 'claims.reports';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Claims Summary & Financial Audit Report</h1>
            <p class="text-xs text-gray-500 mt-1">Cross-stream reporting across Driver Fuel & Operations, Ride Incentives, Performance Bonuses, and Maternity Advances.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('claims.export', request()->query()) }}" 
               class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Complete CSV Report
            </a>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    <!-- 4 High-Level KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Disbursed Payout</p>
            <p class="text-2xl font-black font-outfit text-emerald-600 mt-1">PHP {{ number_format($stats['total_disbursed'], 2) }}</p>
            <div class="text-[10px] text-gray-400 mt-1 flex justify-between">
                <span>Non-Taxable: ₱{{ number_format($stats['non_taxable_total'], 2) }}</span>
                <span>Taxable: ₱{{ number_format($stats['taxable_total'], 2) }}</span>
            </div>
        </div>

        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Pending Queue</p>
            <p class="text-2xl font-black font-outfit text-amber-600 mt-1">{{ $stats['pending_count'] }} Claims</p>
            <p class="text-[11px] text-gray-500 mt-1">PHP {{ number_format($stats['pending_amount'], 2) }} Awaiting Sign-off</p>
        </div>

        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">3-Day SLA Compliance</p>
            <p class="text-2xl font-black font-outfit text-indigo-600 mt-1">{{ $stats['sla_on_time_rate'] }}%</p>
            <p class="text-[11px] text-gray-500 mt-1">{{ $stats['overdue_count'] }} Claims Exceeding 3-Day SLA</p>
        </div>

        <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Documented Rejections</p>
            <p class="text-2xl font-black font-outfit text-rose-600 mt-1">{{ $stats['rejected_count'] }}</p>
            <p class="text-[11px] text-gray-500 mt-1">PHP {{ number_format($stats['rejected_amount'], 2) }} Disallowed</p>
        </div>
    </div>

    <!-- 4 Claim Streams Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($typeBreakdowns as $typeKey => $t)
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">{{ $t['label'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-700">{{ $t['count'] }} Filed</span>
                </div>
                <p class="text-lg font-black font-outfit text-gray-900">PHP {{ number_format($t['disbursed_amount'], 2) }}</p>
                <div class="flex items-center justify-between text-[11px] text-gray-500 border-t border-gray-50 pt-2">
                    <span>Approved: {{ $t['approved_count'] }}</span>
                    <span class="text-amber-600 font-semibold">Pending: {{ $t['pending_count'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div x-data="{ activeTab: 'all' }" class="space-y-6">

        <!-- Tabs Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                <button type="button" @click="activeTab = 'all'" 
                        :class="activeTab === 'all' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all">
                    All Claims Audit Log
                </button>
                <button type="button" @click="activeTab = 'departments'" 
                        :class="activeTab === 'departments' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all">
                    Department Cost Breakdown
                </button>
                <button type="button" @click="activeTab = 'rejections'" 
                        :class="activeTab === 'rejections' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    Rejected Claims Log
                    <span class="px-1.5 py-0.5 text-[9px] font-black rounded-full bg-rose-100 text-rose-800">{{ $stats['rejected_count'] }}</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: ALL CLAIMS AUDIT LOG -->
        <div x-show="activeTab === 'all'" class="space-y-4">
            <!-- Filter Bar -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <form action="{{ route('claims.reports') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Claim Type</label>
                        <select name="type" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800">
                            <option value="">All Types</option>
                            <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Expenses</option>
                            <option value="incentive" {{ $type === 'incentive' ? 'selected' : '' }}>Ride Incentives</option>
                            <option value="performance" {{ $type === 'performance' ? 'selected' : '' }}>Performance</option>
                            <option value="maternity" {{ $type === 'maternity' ? 'selected' : '' }}>Maternity</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Department</label>
                        <select name="department_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800">
                            <option value="">All Departments</option>
                            @foreach($allDepartments as $dept)
                                <option value="{{ $dept->id }}" {{ (string)$departmentId === (string)$dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-800">
                            <option value="">All Statuses</option>
                            <option value="pending_hr" {{ $status === 'pending_hr' ? 'selected' : '' }}>Pending HR</option>
                            <option value="pending_admin" {{ $status === 'pending_admin' ? 'selected' : '' }}>Pending Admin</option>
                            <option value="pending_finance" {{ $status === 'pending_finance' ? 'selected' : '' }}>Pending Finance</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="payroll_queued" {{ $status === 'payroll_queued' ? 'selected' : '' }}>Queued for Payroll</option>
                            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Date Range (From - To)</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full text-[11px] bg-gray-50 border border-gray-200 rounded-xl px-2 py-1.5 text-gray-800">
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full text-[11px] bg-gray-50 border border-gray-200 rounded-xl px-2 py-1.5 text-gray-800">
                        </div>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white text-xs font-black py-2.5 rounded-xl shadow-sm">
                            Filter
                        </button>
                        <a href="{{ route('claims.reports') }}" class="px-3 py-2.5 text-xs text-gray-500 hover:text-gray-700 bg-gray-100 rounded-xl font-bold">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Container -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Ref & Date</th>
                                <th class="py-3 px-4">Claimant Employee</th>
                                <th class="py-3 px-4">Type & Category</th>
                                <th class="py-3 px-4">Claimed Amount</th>
                                <th class="py-3 px-4">Workflow Stage</th>
                                <th class="py-3 px-4">SLA Turnaround</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @forelse($claims as $claim)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                        {{ $claim->receipt_number }}
                                        <div class="text-[10px] text-gray-400 font-normal">{{ $claim->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900">
                                        <div>{{ $claim->employee?->first_name }} {{ $claim->employee?->last_name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono font-normal">{{ $claim->employee?->department?->name ?? 'General' }} • {{ $claim->employee?->position }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-gray-100 text-gray-700">
                                            {{ ucfirst((string)$claim->type) }}
                                        </span>
                                        <div class="text-[10px] text-gray-500 font-medium mt-0.5">{{ $claim->categoryModel?->name ?? ($claim->category ?? 'General Claim') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-extrabold text-gray-900 font-mono text-sm">
                                        PHP {{ number_format((float)$claim->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider inline-block {{ $claim->status_badge_class }}">
                                            {{ $claim->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($claim->isOverdue())
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-200">
                                                Overdue ({{ $claim->waitingDays() }} Days)
                                            </span>
                                        @else
                                            <span class="text-gray-500 text-[11px] font-medium font-mono">
                                                {{ $claim->waiting_label }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400 text-xs">No claims match the specified filter criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $claims->links() }}
                </div>
            </div>
        </div>

        <!-- TAB 2: DEPARTMENT COST BREAKDOWN -->
        <div x-show="activeTab === 'departments'" class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 font-outfit mb-4">Department Cost Summary</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Department Name</th>
                            <th class="py-3 px-4 text-center">Total Claims</th>
                            <th class="py-3 px-4 text-center">Approved Count</th>
                            <th class="py-3 px-4 text-right">Non-Taxable Portion</th>
                            <th class="py-3 px-4 text-right">Taxable Portion</th>
                            <th class="py-3 px-4 text-right">Total Disbursed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($departments as $dept)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3.5 px-4 font-bold text-gray-900">{{ $dept['name'] }}</td>
                                <td class="py-3.5 px-4 text-center font-semibold text-gray-700">{{ $dept['total_claims'] }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-full text-[10px]">
                                        {{ $dept['approved_count'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-gray-600">PHP {{ number_format($dept['non_taxable_amount'], 2) }}</td>
                                <td class="py-3.5 px-4 text-right font-mono text-gray-600">PHP {{ number_format($dept['taxable_amount'], 2) }}</td>
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-gray-900">PHP {{ number_format($dept['disbursed_amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">No departmental expense records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: REJECTED CLAIMS LOG -->
        <div x-show="activeTab === 'rejections'" class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-900 font-outfit mb-4">Rejected Claims Log with Documented Reasons</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Ref Code</th>
                            <th class="py-3 px-4">Claimant</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Disallowed Amount</th>
                            <th class="py-3 px-4">Documented Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentRejected as $rej)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    {{ $rej->receipt_number }}
                                    <div class="text-[10px] text-gray-400 font-normal">{{ $rej->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-900">
                                    <div>{{ $rej->employee?->first_name }} {{ $rej->employee?->last_name }}</div>
                                    <span class="text-[10px] text-gray-400 font-mono font-normal">{{ $rej->employee?->department?->name ?? 'General' }}</span>
                                </td>
                                <td class="py-3.5 px-4 uppercase font-black text-[10px] text-gray-600">
                                    {{ $rej->type }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-extrabold text-rose-600">
                                    PHP {{ number_format((float)$rej->amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-700">
                                    <p class="font-medium text-rose-900 bg-rose-50 border border-rose-200 rounded-xl p-2.5 text-xs">
                                        {{ $rej->rejection_reason ?: ($rej->hr_remarks ?: 'Did not satisfy required compliance documentation.') }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">No rejected claims recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection
