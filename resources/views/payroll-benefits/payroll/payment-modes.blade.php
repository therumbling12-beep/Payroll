@extends('layouts.app')

@php
    $pageTitle = 'Payment Modes & Bank Setup';
    $currentPage = 'payroll.payment-modes';
@endphp

@section('content')

    <div x-data="{ 
        activeTab: 'registry',
        editModal: false,
        reviewModal: false,
        activeEmployee: null,
        activeSubmission: null,
        rejectionReason: '',
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
        },
        openReview(sub) {
            this.activeSubmission = sub;
            this.rejectionReason = '';
            this.reviewModal = true;
        }
    }" class="space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold font-outfit text-gray-900">Payment Modes & Security Bank Configuration</h1>
                <p class="text-xs text-gray-500 mt-0.5">Assign personnel disbursement channels, inspect ESS Security Bank account proofs, and configure OTC cash rosters.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    Security Bank Facility Active
                </span>
            </div>
        </div>

        <!-- Flash Status Message -->
        @if(session('status'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl flex items-center justify-between text-xs font-bold shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-2xl text-xs space-y-1 shadow-xs font-bold">
                <div class="flex items-center gap-2 text-rose-800 font-black uppercase text-[11px]">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Validation Errors</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
            <button @click="activeTab = 'registry'"
                    :class="activeTab === 'registry' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Payment Modes Registry ({{ $employees->total() }})
            </button>

            <button @click="activeTab = 'verifications'"
                    :class="activeTab === 'verifications' ? 'bg-[#F44336] text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2 relative">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Pending Bank Verifications
                @if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white text-[#F44336]">
                        {{ $pendingSubmissions->count() }}
                    </span>
                @endif
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: EMPLOYEE PAYMENT REGISTRY -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'registry'" class="space-y-6">

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
                                    $rawAcct = $emp->bank_account_number ?: ($emp->bank_account_no ?: '');
                                    $maskedAcct = strlen($rawAcct) >= 4 
                                        ? str_repeat('*', max(0, strlen($rawAcct) - 4)) . substr($rawAcct, -4) 
                                        : ($rawAcct ?: 'No Account Set');
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
                                            <span class="font-mono text-gray-500 text-[11px]">{{ $maskedAcct }}</span>
                                        @else
                                            <span class="text-amber-700 font-bold text-[11px] bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Cash Envelope & Voucher</span>
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
        <!-- TAB 2: PENDING BANK VERIFICATIONS (ESS PROOFS) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'verifications'" style="display: none;" class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-5">
                <div>
                    <h3 class="text-sm font-black text-gray-900 font-outfit">Pending Security Bank ATM Verifications</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Review employee ATM photo submissions and activate direct deposit with 1 click.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Employee</th>
                                <th class="py-3 px-4">Department / Position</th>
                                <th class="py-3 px-4">Submitted Bank & Account</th>
                                <th class="py-3 px-4">Date Submitted</th>
                                <th class="py-3 px-4 text-center">ATM Photo Proof</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            @forelse($pendingSubmissions ?? [] as $sub)
                                <tr class="hover:bg-gray-50/75 transition-colors">
                                    <td class="py-3.5 px-4 font-black text-gray-900">
                                        <div class="text-sm font-black">{{ $sub->employee?->first_name }} {{ $sub->employee?->last_name }}</div>
                                        <span class="text-xs text-gray-400 font-mono">{{ $sub->employee?->employee_code }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700">
                                        <div class="font-bold">{{ $sub->employee?->position }}</div>
                                        <span class="text-[11px] text-gray-400">{{ $sub->employee?->department?->name ?? 'Fleet Operations' }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900">{{ $sub->bank_name }}</div>
                                        <span class="font-mono text-emerald-700 font-black text-xs">{{ $sub->account_number }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 font-mono text-xs">
                                        {{ $sub->created_at->format('M j, Y • h:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        @if($sub->proof_attachment_path)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                Photo Attached
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-[11px]">No Photo</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click="openReview({{ Js::from($sub) }})"
                                                class="px-3 py-1.5 bg-[#F44336] hover:bg-[#D32F2F] text-white font-black rounded-xl text-xs shadow-2xs transition-all inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Inspect & Verify
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-400 text-xs font-semibold">
                                        No pending bank account submissions. All employee payment modes are up to date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: SIDE-BY-SIDE PHOTO INSPECTION & 1-CLICK VERIFICATION -->
        <!-- ========================================================================= -->
        <div x-show="reviewModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="reviewModal = false" class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-black font-outfit text-gray-900">Security Bank Account Verification</h3>
                        <p class="text-[11px] text-gray-400">Cross-verify the submitted ATM card photo against the account number.</p>
                    </div>
                    <button @click="reviewModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeSubmission">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Left: Proof Photo -->
                        <div class="bg-gray-50 rounded-2xl p-3 border border-gray-200 flex flex-col items-center justify-center min-h-[220px]">
                            <template x-if="activeSubmission.proof_attachment_path">
                                <img :src="'/storage/' + activeSubmission.proof_attachment_path" 
                                     class="max-h-56 w-auto rounded-xl object-contain shadow-sm border border-gray-200">
                            </template>
                            <template x-if="!activeSubmission.proof_attachment_path">
                                <div class="text-center text-gray-400 text-xs p-4">
                                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    No photo attachment provided.
                                </div>
                            </template>
                        </div>

                        <!-- Right: Details & Actions -->
                        <div class="space-y-4 text-xs">
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-1">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Employee Name</span>
                                <span class="font-black text-gray-900 text-sm block" x-text="activeSubmission.employee ? activeSubmission.employee.first_name + ' ' + activeSubmission.employee.last_name : 'Employee'"></span>
                                <span class="text-gray-500 font-mono block text-[11px]" x-text="activeSubmission.employee ? activeSubmission.employee.employee_code + ' • ' + activeSubmission.employee.position : ''"></span>
                            </div>

                            <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-200 space-y-1">
                                <span class="text-emerald-700 block text-[10px] uppercase font-bold">Security Bank Account Number</span>
                                <span class="font-mono font-black text-emerald-900 text-base block" x-text="activeSubmission.account_number"></span>
                                <span class="text-emerald-700/80 block text-[10px]" x-text="activeSubmission.bank_name"></span>
                            </div>

                            <!-- 1-Click Approve Form -->
                            <form :action="'{{ url('/payroll/bank-verifications') }}/' + activeSubmission.id + '/approve'" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-2.5 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Approve & Switch to Security Bank
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form :action="'{{ url('/payroll/bank-verifications') }}/' + activeSubmission.id + '/reject'" method="POST" class="space-y-2 pt-2 border-t border-gray-100">
                                @csrf
                                <label class="block font-bold text-gray-600 text-[11px]">Reject Submission</label>
                                <input type="text" name="rejection_reason" required placeholder="e.g. Photo blurry, please re-upload clear image."
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs focus:outline-none focus:border-rose-500">
                                <button type="submit" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-black py-2 px-3 rounded-xl border border-rose-200 transition-all text-xs flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject Submission
                                </button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: EDIT PAYMENT CHANNEL (MANUAL OVERRIDE) -->
        <!-- ========================================================================= -->
        <div x-show="editModal" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="editModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-black font-outfit text-gray-900">Configure Disbursement Channel</h3>
                    <button @click="editModal = false" class="text-gray-400 hover:text-gray-600 text-sm font-bold">✕</button>
                </div>

                <template x-if="activeEmployee">
                    <form :action="'{{ url('/payroll/payment-modes') }}/' + activeEmployee.id" method="POST" class="space-y-4 text-xs">
                        @csrf
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
                                <select name="bank_name" x-model="bankName" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                    <option value="Security Bank Corporation">Security Bank Corporation (Corporate Payroll)</option>
                                    <option value="Security Bank Easy Savings">Security Bank Easy Savings Account</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Security Bank Account Number</label>
                                <input type="text" name="bank_account_number" x-model="accountNo" placeholder="e.g. 0012-3456-7890" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 font-mono font-bold text-gray-900 focus:outline-none focus:border-[#F44336]">
                                <span class="text-[10px] text-gray-400 block mt-1">10-20 digits Security Bank ATM / Corporate payroll account.</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                            <button type="button" @click="editModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 bg-[#F44336] hover:bg-[#D32F2F] text-white font-black rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Channel
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>

@endsection
