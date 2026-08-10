@extends('layouts.app')

@php
    $pageTitle = 'Payslips & Transparency';
    $currentPage = 'payroll.payslips';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-extrabold font-outfit text-gray-900">Employee Payslip Generation</h1>
            <p class="text-xs text-gray-500 mt-0.5">Transparent deduction breakdown for SSS, PhilHealth, and Pag-IBIG. Downloadable per employee.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-full">
                Transparent Payroll Engine Active
            </span>
        </div>
    </div>

    <!-- Interactive Search & Filter Toolbar -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-1 items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1 max-w-xs">
                <input type="text" placeholder="Search employee or payslip ID..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select class="text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-[#F44336]">
                <option value="all">All Departments</option>
                <option value="fleet">Fleet Operations</option>
                <option value="dispatch">Dispatch & Routing</option>
            </select>
        </div>
        <span class="text-xs text-gray-400">Showing 2 of 142 Payslips</span>
    </div>

    <!-- Main Grid: Employee Selection Left, Generated Payslip Preview Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8" x-data="{ selectedEmployee: 1 }">
        
        <!-- Employee Selector List (Left 1 col) -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
            <h2 class="text-xs font-bold font-outfit uppercase tracking-wider text-gray-400 mb-3">Select Employee Payslip</h2>
            
            <button @click="selectedEmployee = 1" 
                    :class="selectedEmployee === 1 ? 'border-[#F44336] bg-red-50/30' : 'border-gray-100 hover:border-gray-200'"
                    class="w-full text-left p-3.5 rounded-xl border transition-all flex items-center justify-between">
                <div>
                    <div class="font-bold text-xs text-gray-900">Juan Dela Cruz</div>
                    <div class="text-[10px] text-gray-400">Senior Driver • EMP-1001</div>
                </div>
                <div class="text-right">
                    <div class="font-extrabold text-xs text-emerald-600">₱8,230.00</div>
                    <div class="text-[9px] text-gray-400">July 1–15 Cutoff</div>
                </div>
            </button>

            <button @click="selectedEmployee = 2" 
                    :class="selectedEmployee === 2 ? 'border-[#F44336] bg-red-50/30' : 'border-gray-100 hover:border-gray-200'"
                    class="w-full text-left p-3.5 rounded-xl border transition-all flex items-center justify-between">
                <div>
                    <div class="font-bold text-xs text-gray-900">Elena Rostova</div>
                    <div class="text-[10px] text-gray-400">Dispatcher • EMP-1042</div>
                </div>
                <div class="text-right">
                    <div class="font-extrabold text-xs text-emerald-600">₱15,355.00</div>
                    <div class="text-[9px] text-gray-400">July 1–15 Cutoff</div>
                </div>
            </button>

            <!-- Pagination Footer for Left Sidebar List -->
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
                <button class="hover:text-gray-700">← Prev</button>
                <span>Page 1 of 14</span>
                <button class="hover:text-gray-700">Next →</button>
            </div>
        </div>

        <!-- Generated Payslip View (Right 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-8 shadow-xs">
            <div class="flex items-center justify-between border-b border-gray-100 pb-6 mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold font-outfit text-gray-900">Official Payslip Statement</h2>
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px]">Verified & Transparent</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Pay Period: July 1, 2026 – July 15, 2026</p>
                </div>
                <button class="bg-[#F44336] hover:bg-[#D32F2F] text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download PDF Payslip
                </button>
            </div>

            <!-- Employee Info Box -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100 mb-6 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Employee Name</span>
                    <span class="font-bold text-gray-900" x-text="selectedEmployee === 1 ? 'Juan Dela Cruz' : 'Elena Rostova'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Employee ID</span>
                    <span class="font-mono text-gray-700" x-text="selectedEmployee === 1 ? 'EMP-1001' : 'EMP-1042'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Department</span>
                    <span class="font-semibold text-gray-800" x-text="selectedEmployee === 1 ? 'Fleet Operations' : 'Dispatch & Routing'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Payment Mode</span>
                    <span class="font-bold text-blue-600">BDO Bank Transfer</span>
                </div>
            </div>

            <!-- Itemized Earnings & Deductions Table -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Earnings Column -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold font-outfit uppercase tracking-wider text-emerald-600 border-b border-gray-100 pb-2">1. Earnings Breakdown</h3>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Basic Salary (Attendance)</span>
                        <span class="font-bold text-gray-900" x-text="selectedEmployee === 1 ? '₱9,350.00' : '₱17,500.00'"></span>
                    </div>
                    <div class="flex justify-between text-xs pt-2 border-t border-gray-100 font-bold">
                        <span class="text-gray-900">Total Gross Earnings</span>
                        <span class="text-gray-900" x-text="selectedEmployee === 1 ? '₱9,350.00' : '₱17,500.00'"></span>
                    </div>
                </div>

                <!-- Deductions Column -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold font-outfit uppercase tracking-wider text-red-600 border-b border-gray-100 pb-2">2. Itemized Statutory Deductions</h3>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">SSS Contribution</span>
                        <span class="font-semibold text-red-600" x-text="selectedEmployee === 1 ? '-₱420.00' : '-₱800.00'"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">PhilHealth Contribution</span>
                        <span class="font-semibold text-red-600" x-text="selectedEmployee === 1 ? '-₱500.00' : '-₱1,145.00'"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Pag-IBIG Contribution</span>
                        <span class="font-semibold text-red-600">-₱200.00</span>
                    </div>
                    <div class="flex justify-between text-xs pt-2 border-t border-gray-100 font-bold">
                        <span class="text-gray-900">Total Deductions</span>
                        <span class="text-red-600" x-text="selectedEmployee === 1 ? '-₱1,120.00' : '-₱2,145.00'"></span>
                    </div>
                </div>
            </div>

            <!-- Net Pay Box -->
            <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-200 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-widest block">Net Salary Payout</span>
                    <span class="text-xs text-emerald-600">Deposited directly to employee BDO account</span>
                </div>
                <span class="text-2xl font-extrabold font-outfit text-emerald-700" x-text="selectedEmployee === 1 ? '₱8,230.00' : '₱15,355.00'"></span>
            </div>

        </div>

    </div>

@endsection
