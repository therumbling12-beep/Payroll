@extends('layouts.app')

@php
    $pageTitle = 'Merit & Promotions';
    $currentPage = 'compensation.merit-promotions';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Merit & Promotion Adjustments</h1>
            <p class="text-xs text-gray-500 mt-0.5">Propose and approve salary rate increases, job title promotions, and one-time merit bonuses synced directly to Payroll.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Team 3 Performance Hook Active
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js state for proposal modal -->
    <div x-data="{ showModal: false }" class="space-y-6">

        <!-- Toolbar & New Proposal Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div>
                <h2 class="text-sm font-bold font-outfit text-gray-900">Merit Adjustments Queue</h2>
                <p class="text-[10px] text-gray-400">Proposals waiting for HR Director approval to trigger payroll rate updates</p>
            </div>
            
            <button @click="showModal = true" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Propose Merit Promotion
            </button>
        </div>

        <!-- Adjustments Table -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Employee</th>
                            <th class="py-3 px-4">Current vs New Title</th>
                            <th class="py-3 px-4">Base Rate Increase</th>
                            <th class="py-3 px-4">One-Time Bonus</th>
                            <th class="py-3 px-4">Reason / Notes</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($adjustments as $adj)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-4 font-bold text-gray-900">
                                    <div>{{ $adj->employee?->first_name }} {{ $adj->employee?->last_name }}</div>
                                    <span class="text-[10px] text-gray-400 font-normal">{{ $adj->employee?->employee_code }}</span>
                                </td>
                                <td class="py-4 px-4 font-medium text-gray-700">
                                    <div class="text-gray-400 line-through text-[10px]">{{ $adj->old_position }}</div>
                                    <div class="font-bold text-gray-900">{{ $adj->new_position }}</div>
                                </td>
                                <td class="py-4 px-4 font-semibold text-gray-800">
                                    <div class="text-gray-400 line-through text-[10px]">₱{{ number_format((float)$adj->old_rate, 2) }}</div>
                                    <div class="font-extrabold text-emerald-600">₱{{ number_format((float)$adj->new_rate, 2) }}</div>
                                </td>
                                <td class="py-4 px-4 font-bold text-purple-700">
                                    @if($adj->bonus_amount > 0)
                                        +₱{{ number_format((float)$adj->bonus_amount, 2) }}
                                    @else
                                        <span class="text-gray-400 font-normal">None</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-gray-600 max-w-xs truncate" title="{{ $adj->reason }}">
                                    {{ $adj->reason }}
                                </td>
                                <td class="py-4 px-4">
                                    @if($adj->status === 'approved')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Approved</span>
                                    @elseif($adj->status === 'rejected_financial_budget')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-800">Rejected (Financial Budget)</span>
                                    @elseif($adj->status === 'rejected')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-800">Rejected</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800">Pending Approval</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-right">
                                    @if($adj->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('compensation.adjustments.approve', $adj->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-sm">
                                                    Approve & Sync to Payroll
                                                </button>
                                            </form>
                                            <form action="{{ route('compensation.adjustments.reject', $adj->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-semibold">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400 text-xs">No merit promotion proposals found. Click "Propose Merit Promotion" above to add one!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $adjustments->links() }}
            </div>
        </div>

        <!-- Propose Merit Promotion Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div @click.away="showModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold font-outfit text-gray-900">Propose Merit Increase / Promotion</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form action="{{ route('compensation.adjustments.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="merit_promotion">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Select Employee</label>
                        <select name="employee_id" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->position }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">New Position Title (Optional)</label>
                        <input type="text" name="new_position" placeholder="e.g. Senior Operations Officer" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">New Base Salary / Daily Rate (₱)</label>
                            <input type="number" step="0.01" name="new_rate" placeholder="e.g. 45000.00" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-purple-700 mb-1">One-Time Performance Bonus (₱)</label>
                            <input type="number" step="0.01" name="bonus_amount" placeholder="e.g. 5000.00" class="w-full text-xs bg-purple-50/50 border border-purple-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-purple-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Justification / Reason</label>
                        <textarea name="reason" rows="3" required placeholder="Describe performance evaluation rating or achievement justification..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-[#F44336]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs px-5 py-2 rounded-xl">Submit Proposal</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
