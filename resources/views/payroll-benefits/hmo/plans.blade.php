@extends('layouts.app')

@php
    $pageTitle = 'HMO Plans & Benefit Policy Matrix';
    $currentPage = 'hmo.plans';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">HMO Plans & Benefit Policy Matrix</h1>
            <p class="text-xs text-gray-500 mt-1">Configure company healthcare policy limits, Grade-based Maximum Benefit Limits (MBL), and the corporate benefit catalog.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Policy Matrix Active
            </span>
            <span class="text-xs text-gray-400 font-semibold font-mono">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Container with Alpine.js State -->
    <div x-data="{ 
        activeTab: '{{ $tab ?? 'matrix' }}', {{-- 'matrix', 'catalog' --}}
        showConfigModal: false,
        showBenefitModal: false,
        showResetConfigModal: false
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar & Action Modals -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Grade MBL Matrix -->
                <button type="button" @click="activeTab = 'matrix'" 
                        :class="activeTab === 'matrix' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/>
                    </svg>
                    Salary Grade MBL Matrix
                </button>

                <!-- Tab 2: Benefit Types Catalog -->
                <button type="button" @click="activeTab = 'catalog'" 
                        :class="activeTab === 'catalog' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Corporate Benefit Types Catalog
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $benefitTypes->count() }}</span>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Configure Policy Settings -->
                <button @click="showConfigModal = true" type="button" 
                        class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs px-3.5 py-2 rounded-xl transition-all border border-gray-200 flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Provider Policy Settings
                </button>

                <!-- Add Benefit Package Button -->
                <button @click="showBenefitModal = true" type="button" 
                        class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Benefit Package
                </button>
            </div>
        </div>

        <!-- 3 Summary Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Default Healthcare Provider</p>
                <p class="text-xl font-black font-outfit text-gray-900 mt-1 truncate">{{ $hmoConfig['hmo_provider_name'] ?? 'Maxicare' }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Co-Share: {{ $hmoConfig['hmo_company_share_pct'] ?? 80 }}% Company / {{ $hmoConfig['hmo_employee_share_pct'] ?? 20 }}% Employee</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                @php
                    $minMbl = $gradeLimits->min('mbl_amount') ?? 100000;
                    $maxMbl = $gradeLimits->max('mbl_amount') ?? 500000;
                @endphp
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Salary Grade MBL Limits</p>
                <p class="text-xl font-black font-outfit text-purple-600 mt-1">PHP {{ number_format((float)$minMbl) }} - PHP {{ number_format((float)$maxMbl) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $gradeLimits->count() }} Tiered Pay Grade Maximum Entitlements</p>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-5 shadow-sm">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Corporate Benefit Catalog</p>
                <p class="text-xl font-black font-outfit text-blue-600 mt-1">{{ $benefitTypes->count() }} Active Packages</p>
                <p class="text-[11px] text-gray-500 mt-1">Medical, Life, Accident & Statutory Schemes</p>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: SALARY GRADE MBL MATRIX -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'matrix'" x-transition class="space-y-6">

            <!-- Grade Matrix Table -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Salary Grade Maximum Benefit Limits (MBL) Policy Matrix</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Tiered healthcare coverage entitlement and room accommodations by employee pay grade.</p>
                    </div>
                    <a href="{{ route('hmo.export-plans') }}" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition-all border border-gray-200">
                        Export Matrix CSV
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Pay Grade Tier</th>
                                <th class="py-3.5 px-4">Standard Coverage Tier</th>
                                <th class="py-3.5 px-4 text-right">Annual MBL Limit</th>
                                <th class="py-3.5 px-4">Room & Board Entitlement</th>
                                <th class="py-3.5 px-4">Included Core Benefits</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($gradeLimits as $limit)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-gray-900 font-mono">{{ $limit->grade_label }}</span>
                                        <div class="text-[10px] text-gray-400">{{ $limit->roles_description }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider
                                            @if(str_contains(strtolower($limit->tier_name), 'basic')) bg-gray-100 text-gray-700
                                            @elseif(str_contains(strtolower($limit->tier_name), 'plus')) bg-blue-50 text-blue-700 border border-blue-200
                                            @elseif(str_contains(strtolower($limit->tier_name), 'premium')) bg-purple-50 text-purple-700 border border-purple-200
                                            @elseif(str_contains(strtolower($limit->tier_name), 'executive')) bg-amber-50 text-amber-800 border border-amber-200
                                            @else bg-emerald-50 text-emerald-800 border border-emerald-200 @endif">
                                            {{ $limit->tier_name }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black font-outfit text-sm text-gray-900">
                                        PHP {{ number_format((float) $limit->mbl_amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700 font-medium">
                                        {{ $limit->room_board_type }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-600 text-[11px]">
                                        {{ $limit->benefits_description }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-400 text-xs">No grade limit rules configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: CORPORATE BENEFIT TYPES MASTER CATALOG -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'catalog'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black font-outfit text-gray-900">Corporate Benefit Packages Master Catalog</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Master register of all health, insurance, wellness, and statutory benefit packages available to employees.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Benefit Name & Code</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Eligibility Criteria</th>
                                <th class="py-3.5 px-4">Tenure Req.</th>
                                <th class="py-3.5 px-4">Dependent Options</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($benefitTypes as $type)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 font-outfit">{{ $type->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $type->code }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                            {{ $type->category }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700">
                                        {{ $type->eligibility }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-600 font-mono text-[11px]">
                                        {{ $type->min_tenure_months }} mos
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-600 text-[11px]">
                                        {{ $type->dependent_options }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase {{ $type->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <form action="{{ route('hmo.toggle-benefit-type', $type) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-bold px-3 py-1 rounded-lg border transition-all {{ $type->is_active ? 'border-gray-200 hover:bg-gray-100 text-gray-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                                {{ $type->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-400 text-xs">No benefit type packages registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ADD BENEFIT PACKAGE -->
        <!-- ========================================================================= -->
        <div x-show="showBenefitModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showBenefitModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Add Corporate Benefit Package</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Register a new company benefit type or allowance scheme</p>
                    </div>
                    <button @click="showBenefitModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('hmo.store-benefit-type') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Package Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Executive Optical Rider" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Package Code *</label>
                            <input type="text" name="code" required placeholder="e.g. OPTICAL-01" class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Category *</label>
                            <select name="category" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="Health Insurance">Health Insurance (HMO)</option>
                                <option value="Insurance">Insurance (Life/Accident)</option>
                                <option value="Allowance">Allowance & Subsidy</option>
                                <option value="Government Mandated">Government Mandated</option>
                                <option value="Statutory">Statutory Benefit</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Min. Service Tenure (Months) *</label>
                            <input type="number" name="min_tenure_months" value="0" min="0" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Eligibility Criteria *</label>
                        <input type="text" name="eligibility" value="All Regular Employees" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dependent Coverage Options *</label>
                        <input type="text" name="dependent_options" value="Up to 2 Direct Dependents" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Description & Scope</label>
                        <textarea name="description" rows="2" placeholder="Summary of coverage limits and reimbursement rules..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="benefit_active" value="1" checked class="rounded border-gray-300 text-gray-900">
                        <label for="benefit_active" class="text-xs font-bold text-gray-700">Activate Benefit Immediately</label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button @click="showBenefitModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2 rounded-xl transition-all shadow-sm">Save Package</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: CONFIGURE HMO PROVIDER SETTINGS -->
        <!-- ========================================================================= -->
        <!-- ========================================================================= -->
        <!-- MODAL: CONFIGURE HMO PROVIDER SETTINGS -->
        <!-- ========================================================================= -->
        <div x-show="showConfigModal" x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.away="showConfigModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Provider & Co-Sharing Settings</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Corporate healthcare policy ratios, premium baselines, and dependent allowances</p>
                    </div>
                    <button @click="showConfigModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form action="{{ route('hmo.plans.config') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Corporate HMO Provider *</label>
                        <input type="text" name="hmo_provider_name" value="{{ $hmoConfig['hmo_provider_name'] ?? 'Maxicare Healthcare Corporation' }}" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Plan Structure Type *</label>
                            <select name="hmo_plan_type" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="Comprehensive" {{ ($hmoConfig['hmo_plan_type'] ?? '') === 'Comprehensive' ? 'selected' : '' }}>Comprehensive</option>
                                <option value="Outpatient" {{ ($hmoConfig['hmo_plan_type'] ?? '') === 'Outpatient' ? 'selected' : '' }}>Outpatient Only</option>
                                <option value="Room & Board" {{ ($hmoConfig['hmo_plan_type'] ?? '') === 'Room & Board' ? 'selected' : '' }}>Room & Board Inpatient</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Premium Shoulder Model *</label>
                            <select name="hmo_premium_shoulder_type" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="shared" {{ ($hmoConfig['hmo_premium_shoulder_type'] ?? '') === 'shared' ? 'selected' : '' }}>Shared Co-Payment</option>
                                <option value="company" {{ ($hmoConfig['hmo_premium_shoulder_type'] ?? '') === 'company' ? 'selected' : '' }}>100% Company Subsidized</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Company Share (%) *</label>
                            <input type="number" name="hmo_company_share_pct" value="{{ $hmoConfig['hmo_company_share_pct'] ?? 80 }}" min="0" max="100" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Employee Share (%) *</label>
                            <input type="number" name="hmo_employee_share_pct" value="{{ $hmoConfig['hmo_employee_share_pct'] ?? 20 }}" min="0" max="100" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Base Employee Premium (PHP/mo) *</label>
                            <input type="number" step="0.01" name="hmo_base_employee_premium" value="{{ $hmoConfig['hmo_base_employee_premium'] ?? 1800.00 }}" min="0" required class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Base Dependent Premium (PHP/mo) *</label>
                            <input type="number" step="0.01" name="hmo_base_dependent_premium" value="{{ $hmoConfig['hmo_base_dependent_premium'] ?? 1200.00 }}" min="0" required class="w-full text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-900 focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Max Qualified Dependents *</label>
                            <input type="number" name="hmo_max_dependents" value="{{ $hmoConfig['hmo_max_dependents'] ?? 4 }}" min="0" max="10" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Coverage Start Tenure (Months) *</label>
                            <select name="hmo_coverage_start_months" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="0" {{ ($hmoConfig['hmo_coverage_start_months'] ?? 6) == 0 ? 'selected' : '' }}>Day 1 (Immediate Onboarding)</option>
                                <option value="3" {{ ($hmoConfig['hmo_coverage_start_months'] ?? 6) == 3 ? 'selected' : '' }}>3 Months</option>
                                <option value="6" {{ ($hmoConfig['hmo_coverage_start_months'] ?? 6) == 6 ? 'selected' : '' }}>6 Months (Regularization)</option>
                                <option value="12" {{ ($hmoConfig['hmo_coverage_start_months'] ?? 6) == 12 ? 'selected' : '' }}>1 Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showResetConfigModal = true" class="w-full sm:w-auto text-xs font-bold text-gray-600 hover:text-rose-700 bg-gray-100 hover:bg-rose-50 px-3.5 py-2 rounded-xl border border-gray-200 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset HMO Policies to Defaults
                        </button>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button @click="showConfigModal = false" type="button" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                            <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">Save Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Reset HMO Policies Modal -->
        <div x-show="showResetConfigModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showResetConfigModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold font-outfit text-gray-900">Reset HMO Policies to Defaults?</h3>
                    <button @click="showResetConfigModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>
                <p class="text-xs text-gray-600">
                    This will restore standard company baseline HMO settings:
                </p>
                <ul class="text-[11px] text-gray-600 space-y-1 bg-gray-50 p-3 rounded-xl border border-gray-100 font-mono">
                    <li>• Provider: Maxicare Healthcare Corporation</li>
                    <li>• Co-Sharing: 80% Company / 20% Employee</li>
                    <li>• Base Employee Premium: PHP 1,800.00/mo</li>
                    <li>• Base Dependent Premium: PHP 1,200.00/mo</li>
                    <li>• Max Qualified Dependents: 4</li>
                    <li>• Coverage Start: 6 Months (Regularization)</li>
                </ul>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showResetConfigModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</button>
                    <form action="{{ route('hmo.plans.config.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-sm cursor-pointer">
                            Confirm Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection
