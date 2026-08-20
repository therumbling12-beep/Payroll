@extends('layouts.app')

@php
    $pageTitle = 'Payment Modes & Bank Setup';
    $currentPage = 'payroll.payment-modes';
@endphp

@section('content')

    <div x-data="{ 
        activeTab: 'registry',
        editModal: false,
        activeEmployee: null,
        mode: 'bank',
        bankName: 'Security Bank Corporation',
        accountNo: '',
        accountName: '',
        openEdit(emp) {
            this.activeEmployee = emp;
            this.mode = emp.payment_mode || 'bank';
            this.bankName = emp.bank_name || 'Security Bank Corporation';
            this.accountNo = emp.bank_account_number || emp.bank_account_no || '0012-3456-7890';
            this.accountName = emp.first_name + ' ' + emp.last_name;
            this.editModal = true;
        }
    }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Payment Modes & Security Bank Configuration</h1>
                <p class="text-xs text-gray-500 mt-0.5">Assign personnel disbursement methods, manage Security Bank Corporation (SBC) payroll accounts, and configure OTC cash rosters.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    Security Bank Facility Active
                </span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- EMPLOYEE PAYMENT REGISTRY -->
        <!-- ========================================================================= -->
        <div class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                
                <!-- Search & Mode Filter Form -->
                <form action="{{ route('payroll.payment-modes') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-1 items-center gap-3">
                        <div class="relative flex-1 max-w-xs">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search employee or code..." 
                                   class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <select name="mode" onchange="this.form.submit()" 
                                class="text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                            <option value="all" {{ ($mode ?? 'all') === 'all' ? 'selected' : '' }}>All Payment Channels</option>
                            <option value="bank" {{ ($mode ?? '') === 'bank' ? 'selected' : '' }}>Security Bank Transfer</option>
                            <option value="cash" {{ ($mode ?? '') === 'cash' ? 'selected' : '' }}>Physical Cash (Envelope)</option>
                        </select>

                        <button type="submit" class="bg-gray-900 hover:bg-black text-white text-xs font-black px-4 py-2.5 rounded-xl transition-all">
                            Filter
                        </button>
                    </div>

                    <div class="text-xs text-gray-500 font-bold">
                        Showing {{ $employees->count() }} of {{ $employees->total() }} records
                    </div>
                </form>

                <!-- Employee Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Position</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4 text-center">Disbursement Method</th>
                                <th class="py-3 px-4">Bank / Reference Details</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($employees as $emp)
                                @php
                                    $isCash = $emp->payment_mode === 'cash';
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div class="text-sm font-black">{{ $emp->first_name }} {{ $emp->last_name }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $emp->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700 font-bold">
                                        {{ $emp->position }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-800 font-bold rounded-lg text-xs">
                                            {{ $emp->department?->name ?? 'Operations' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black {{ $isCash ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isCash ? 'bg-amber-500' : 'bg-emerald-600' }}"></span>
                                            {{ $isCash ? 'Physical Cash' : 'Security Bank' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if(!$isCash)
                                            <div class="font-bold text-gray-900">{{ $emp->bank_name ?? 'Security Bank Corporation' }}</div>
                                            <span class="font-mono text-gray-500 text-[11px]">{{ $emp->bank_account_number ?: ($emp->bank_account_no ?: 'SBC-0012345678') }}</span>
                                        @else
                                            <span class="text-gray-400 font-bold italic">Over-The-Counter Envelope</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click="openEdit({{ Js::from($emp) }})" 
                                                title="Edit Disbursement Channel"
                                                class="p-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900 transition-all shadow-2xs inline-flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No employee records found.
                                    </td>
                                </tr>
                            @endforelse
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
        <!-- MODAL: EDIT PAYMENT CHANNEL -->
        <!-- ========================================================================= -->
        <div x-show="editModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="editModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Configure Disbursement Channel</h3>
                    <button @click="editModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeEmployee">
                    <form @submit.prevent="editModal = false" class="space-y-4 text-xs">
                        <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 space-y-1">
                            <span class="font-black text-sm text-gray-900 block" x-text="activeEmployee.first_name + ' ' + activeEmployee.last_name"></span>
                            <span class="text-xs text-gray-500 font-mono block" x-text="activeEmployee.employee_code + ' • ' + activeEmployee.position"></span>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1.5">Payment Method *</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label @click="mode = 'bank'" 
                                       :class="mode === 'bank' ? 'border-[#F44336] bg-red-50/40 text-[#F44336]' : 'border-gray-200 bg-gray-50 text-gray-700'" 
                                       class="p-3 rounded-xl border cursor-pointer transition-all flex items-center gap-2 font-bold">
                                    <input type="radio" name="payment_mode" value="bank" x-model="mode" class="text-[#F44336]">
                                    <span>Security Bank</span>
                                </label>

                                <label @click="mode = 'cash'" 
                                       :class="mode === 'cash' ? 'border-[#F44336] bg-red-50/40 text-[#F44336]' : 'border-gray-200 bg-gray-50 text-gray-700'" 
                                       class="p-3 rounded-xl border cursor-pointer transition-all flex items-center gap-2 font-bold">
                                    <input type="radio" name="payment_mode" value="cash" x-model="mode" class="text-[#F44336]">
                                    <span>Physical Cash</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="mode === 'bank'" class="space-y-3 pt-1">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Bank Name</label>
                                <select x-model="bankName" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                    <option value="Security Bank Corporation">Security Bank Corporation (Corporate Payroll)</option>
                                    <option value="Security Bank Easy Savings">Security Bank Easy Savings Account</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Account Number</label>
                                <input type="text" x-model="accountNo" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-mono font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                            <button type="button" @click="editModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 bg-[#F44336] hover:bg-[#D32F2F] text-white font-black rounded-xl shadow-sm transition-all">Save Channel</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>

@endsection
