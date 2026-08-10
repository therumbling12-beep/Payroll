@extends('layouts.app')

@php
    $pageTitle = 'Payment Modes Configuration';
    $currentPage = 'payroll.payment-modes';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Payment Modes & Bank Details Configuration</h1>
            <p class="text-xs text-gray-500 mt-0.5">Assign employee pay disbursement methods (Direct Bank Deposit vs. Physical Cash).</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                BDO & Major Bank Gateway Live
            </span>
            <span class="text-xs text-gray-400">{{ now()->format('D, M j Y') }}</span>
        </div>
    </div>

    <!-- Payment Config Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8" x-data="{ mode: 'bank', bankName: 'BDO Unibank', accountNo: '0012-3456-7890' }">
        
        <!-- Individual Employee Bank Config Form (Left 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
            <div class="border-b border-gray-100 pb-4 mb-6">
                <h2 class="text-sm font-bold font-outfit text-gray-900">Configure Employee Disbursement Channel</h2>
                <p class="text-[10px] text-gray-400">Map salary deposits to employee reference numbers</p>
            </div>

            <form @submit.prevent class="space-y-5">
                
                <!-- Employee Picker -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Select Employee</label>
                    <select class="w-full text-xs font-semibold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        <option>EMP-1001 — Juan Dela Cruz (Senior Driver)</option>
                        <option>EMP-1042 — Elena Rostova (Dispatcher)</option>
                        <option>EMP-1020 — Marco Rossi (Operations Lead)</option>
                    </select>
                </div>

                <!-- Disbursement Mode Selection -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Disbursement Method</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label @click="mode = 'bank'" :class="mode === 'bank' ? 'border-[#F44336] bg-red-50/40 text-[#F44336]' : 'border-gray-200 bg-gray-50/50 text-gray-700'" class="p-3.5 rounded-xl border cursor-pointer transition-all flex items-center gap-3">
                            <input type="radio" name="payment_mode" value="bank" x-model="mode" class="text-[#F44336]">
                            <div>
                                <p class="text-xs font-bold">Bank Transfer</p>
                                <p class="text-[10px] text-gray-400">Direct Deposit</p>
                            </div>
                        </label>

                        <label @click="mode = 'cash'" :class="mode === 'cash' ? 'border-[#F44336] bg-red-50/40 text-[#F44336]' : 'border-gray-200 bg-gray-50/50 text-gray-700'" class="p-3.5 rounded-xl border cursor-pointer transition-all flex items-center gap-3">
                            <input type="radio" name="payment_mode" value="cash" x-model="mode" class="text-[#F44336]">
                            <div>
                                <p class="text-xs font-bold">Physical Cash</p>
                                <p class="text-[10px] text-gray-400">Over-the-Counter</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Conditional Bank Details Form -->
                <div x-show="mode === 'bank'" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Bank Provider</label>
                            <select x-model="bankName" class="w-full text-xs font-semibold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                                <option value="BDO Unibank">BDO Unibank (Banco de Oro)</option>
                                <option value="BPI">BPI (Bank of the Philippine Islands)</option>
                                <option value="Metrobank">Metrobank</option>
                                <option value="GCash">GCash Enterprise Payroll</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Bank Account / Reference Number</label>
                            <input type="text" x-model="accountNo" class="w-full text-xs font-mono font-bold bg-gray-50/50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-gray-800 focus:outline-none focus:border-[#F44336]">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-3">
                    <button type="button" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold text-xs py-3 px-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Bank & Payment Configuration
                    </button>
                </div>

            </form>
        </div>

        <!-- Bank Routing Overview Panel (Right 1 col) -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h3 class="text-xs font-bold font-outfit uppercase tracking-wider text-gray-400 mb-4">Payment Distribution Stats</h3>
                
                <div class="space-y-4">
                    <div class="p-3 bg-emerald-50 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white font-bold flex items-center justify-center text-xs">
                                🏦
                            </div>
                            <div>
                                <p class="text-xs font-bold text-emerald-900">Direct Bank Deposit</p>
                                <p class="text-[10px] text-emerald-700">92% Workforce</p>
                            </div>
                        </div>
                        <span class="text-sm font-extrabold font-outfit text-emerald-900">293 Emps</span>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-gray-300 text-gray-700 font-bold flex items-center justify-center text-xs">
                                💵
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-800">Physical Cash</p>
                                <p class="text-[10px] text-gray-500">8% Workforce</p>
                            </div>
                        </div>
                        <span class="text-sm font-extrabold font-outfit text-gray-800">25 Emps</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
