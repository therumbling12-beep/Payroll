@extends('layouts.app')

@php
    $pageTitle = 'Claim Categories & Taxability Setup';
    $currentPage = 'claims.categories';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Claim & Incentive Categories</h1>
            <p class="text-xs text-gray-500 mt-1">Configure allowable expense categories, BIR TRAIN Law de minimis tax exemptions, and department role scoping.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                BIR Tax Rules Active
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

    <div x-data="{ 
        activeTab: 'categories',
        showAddModal: false, 
        showEditModal: false, 
        showResetModal: false, 
        
        // Edit Form State
        editCat: { 
            id: '', 
            name: '', 
            code: '', 
            type: 'reimbursement', 
            tax_classification: 'non_taxable',
            max_amount: '', 
            de_minimis_annual_cap: '',
            spending_limit_period: 'per_claim',
            requires_receipt: true,
            applicable_to: 'all', 
            description: '', 
            updateUrl: '' 
        },

        openEdit(cat, updateUrl) {
            this.editCat = {
                id: cat.id,
                name: cat.name,
                code: cat.code,
                type: cat.type || 'reimbursement',
                tax_classification: cat.tax_classification || 'non_taxable',
                max_amount: cat.max_amount || '',
                de_minimis_annual_cap: cat.de_minimis_annual_cap || '',
                spending_limit_period: cat.spending_limit_period || 'per_claim',
                requires_receipt: cat.requires_receipt !== false,
                applicable_to: cat.applicable_to || 'all',
                description: cat.description || '',
                updateUrl: updateUrl
            };
            this.showEditModal = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Categories -->
                <button type="button" @click="activeTab = 'categories'" 
                        :class="activeTab === 'categories' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'categories' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Master Category Catalog
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $categories->count() }}</span>
                </button>

                <!-- Tab 2: Limits Guide -->
                <button type="button" @click="activeTab = 'limits'" 
                        :class="activeTab === 'limits' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'limits' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Statutory Governance Guide
                </button>

                <!-- Tab 4: Policy & Rates Setup -->
                <button type="button" @click="activeTab = 'settings'" 
                        :class="activeTab === 'settings' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="activeTab === 'settings' ? 'text-[#F44336]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Policy & Rates Setup
                </button>
            </div>

            <!-- Action Buttons -->
            <button @click="showAddModal = true" type="button" 
                    class="bg-gray-900 hover:bg-black text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Configure New Category
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: CATEGORIES CATALOG -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'categories'" x-transition class="space-y-6">

            <!-- 5 Summary Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Total Categories</span>
                        <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-gray-900">{{ $stats['total_categories'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">{{ $stats['active_categories'] }} Active in System</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Non-Taxable</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-emerald-600">{{ $stats['non_taxable_count'] }}</p>
                    <p class="text-xs text-emerald-700 font-bold">100% Tax Exempt</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">De Minimis Capped</span>
                        <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-cyan-600">{{ $stats['de_minimis_count'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">Capped (PHP 10k/yr Med)</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Taxable Portions</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black font-outfit text-rose-600">{{ $stats['taxable_count'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">Subject to Withholding Tax</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Role Scoping</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-base font-black font-outfit text-gray-900">{{ $stats['driver_only_count'] }} Drivers</span>
                        <span class="text-xs text-gray-400">/</span>
                        <span class="text-base font-black font-outfit text-gray-900">{{ $stats['staff_only_count'] }} Staff</span>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Restricted Submissions</p>
                </div>
            </div>

            <!-- Categories Table Container -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Category Name & Description</th>
                                <th class="py-3.5 px-4">Tax Classification</th>
                                <th class="py-3.5 px-4">Spending Limit</th>
                                <th class="py-3.5 px-4">Applicable To</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs">
                            @foreach($categories as $category)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                        {{ $category->code }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $category->badge_class }}">
                                                {{ $category->name }}
                                            </span>
                                            @if($category->requires_receipt)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-600 border border-gray-200">Receipt Required</span>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-400 font-normal mt-0.5">{{ $category->description ?: 'Standard company claim category.' }}</p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black border {{ $category->tax_badge_classes }}">
                                            {{ $category->tax_label }}
                                        </span>
                                        @if($category->isDeMinimis() && $category->de_minimis_annual_cap)
                                            <p class="text-[10px] font-mono text-cyan-700 mt-0.5 font-bold">Cap: PHP {{ number_format((float)$category->de_minimis_annual_cap, 2) }}/yr</p>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($category->max_amount)
                                            <span class="font-mono font-black text-gray-900">PHP {{ number_format((float)$category->max_amount, 2) }}</span>
                                            <span class="text-[10px] text-gray-400 block font-normal capitalize">/ {{ str_replace('_', ' ', $category->spending_limit_period ?? 'claim') }}</span>
                                        @else
                                            <span class="text-gray-400 italic text-[11px]">No Cap Set</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($category->applicable_to === 'driver')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200">TNVS Drivers</span>
                                        @elseif(in_array($category->applicable_to, ['regular', 'staff']))
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">Office Staff</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200">All Personnel</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <form action="{{ route('claims.categories.toggle', $category->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border transition-all cursor-pointer {{ $category->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-600' : 'bg-gray-400' }}"></span>
                                                {{ $category->is_active ? 'Active' : 'Disabled' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" @click="openEdit({{ json_encode($category) }}, '{{ route('claims.categories.update', $category->id) }}')" 
                                                class="bg-white hover:bg-gray-100 text-gray-800 font-bold text-[11px] px-3.5 py-1.5 rounded-xl transition-all border border-gray-200 shadow-2xs cursor-pointer">
                                            Edit Category
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: STATUTORY GOVERNANCE GUIDE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'limits'" x-transition class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-900"></span>
                    <h3 class="text-base font-extrabold font-outfit text-gray-900">Category Ceilings & Tax Governance Guide</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-900">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Non-Taxable Business Expenses
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Official fuel, toll fees, vehicle repair, and work travel expenses supported by valid receipts are 100% non-taxable reimbursements per BIR rules.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-900">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            De Minimis Medical Exemption
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Actual medical assistance and medicine receipts are tax-exempt up to PHP 10,000.00 / year per employee. Any excess is automatically taxed.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-900">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            Role Scoping & Fraud Guard
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Categories restricted to TNVS Drivers (Fuel/Toll) cannot be filed by Office Staff, ensuring proper cost allocation and audit compliance.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: POLICY & RATES SETUP -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'settings'" x-transition class="space-y-6">
            <form action="{{ route('claims.settings.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Section 1: Fuel Expense Policy -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h3 class="text-base font-extrabold font-outfit text-gray-900">Fuel Reimbursement Policy</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">Formula: (Distance / Efficiency) × Pump Price</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Default Pump Price (PHP/L)</label>
                            <input type="number" step="0.01" min="1" name="fuel_default_pump_price" value="{{ old('fuel_default_pump_price', $policySettings['fuel_default_pump_price']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Current baseline fuel price per liter</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Default Vehicle Efficiency (km/L)</label>
                            <input type="number" step="0.1" min="1" name="fuel_default_efficiency_kpl" value="{{ old('fuel_default_efficiency_kpl', $policySettings['fuel_default_efficiency_kpl']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Baseline kilometers per liter consumption</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Variance Tolerance Limit (%)</label>
                            <input type="number" step="0.1" min="1" max="100" name="fuel_tolerance_percentage" value="{{ old('fuel_tolerance_percentage', $policySettings['fuel_tolerance_percentage']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Receipts exceeding this variance flag for HR review</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Driver Ride Milestone Incentives -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <h3 class="text-base font-extrabold font-outfit text-gray-900">Driver Ride Milestone Tier Structure</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">TNVS Fleet Quota Targets</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                    <th class="py-2.5 px-3">Tier</th>
                                    <th class="py-2.5 px-3">Tier Label</th>
                                    <th class="py-2.5 px-3">Min Completed Rides</th>
                                    <th class="py-2.5 px-3">Payout Amount (PHP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs font-mono">
                                @foreach($policySettings['milestone_tiers'] as $tierNum => $tier)
                                    <tr>
                                        <td class="py-3 px-3 font-bold text-gray-900">
                                            Tier {{ $tier['tier'] }}
                                            <input type="hidden" name="milestone_tiers[{{ $tier['tier'] }}][tier]" value="{{ $tier['tier'] }}">
                                        </td>
                                        <td class="py-3 px-3">
                                            <input type="text" name="milestone_tiers[{{ $tier['tier'] }}][label]" value="{{ old("milestone_tiers.{$tier['tier']}.label", $tier['label']) }}" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 font-sans font-bold text-gray-800 focus:outline-none focus:border-gray-900">
                                        </td>
                                        <td class="py-3 px-3">
                                            <input type="number" min="1" name="milestone_tiers[{{ $tier['tier'] }}][min_rides]" value="{{ old("milestone_tiers.{$tier['tier']}.min_rides", $tier['min_rides']) }}" required class="w-28 text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                                        </td>
                                        <td class="py-3 px-3">
                                            <input type="number" step="10" min="1" name="milestone_tiers[{{ $tier['tier'] }}][amount]" value="{{ old("milestone_tiers.{$tier['tier']}.amount", $tier['amount']) }}" required class="w-36 text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 font-bold text-emerald-600 focus:outline-none focus:border-gray-900">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Monthly Consistency Bonus (PHP)</label>
                            <input type="number" step="50" min="0" name="driver_consistency_bonus" value="{{ old('driver_consistency_bonus', $policySettings['driver_consistency_bonus']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Paid to drivers meeting quota in consecutive cutoffs</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Perfect Attendance Bonus (PHP)</label>
                            <input type="number" step="50" min="0" name="driver_attendance_bonus" value="{{ old('driver_attendance_bonus', $policySettings['driver_attendance_bonus']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Paid to drivers with 100% scheduled attendance</p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Performance Appraisal & Statutory Ceilings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            <h3 class="text-base font-extrabold font-outfit text-gray-900">Merit & Performance Policy</h3>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Performance Bonus Multiplier (PHP / Point)</label>
                            <input type="number" step="50" min="1" name="performance_bonus_multiplier" value="{{ old('performance_bonus_multiplier', $policySettings['performance_bonus_multiplier']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                            <p class="text-[11px] text-gray-500 mt-1">Multiplied against Team 3 Scorecard (1.00 - 5.00)</p>
                        </div>
                    </div>

                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h3 class="text-base font-extrabold font-outfit text-gray-900">Government & Statutory Ceilings</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">SSS Max MSC Ceiling (PHP)</label>
                                <input type="number" step="500" min="1000" name="sss_max_msc" value="{{ old('sss_max_msc', $policySettings['sss_max_msc']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                                <p class="text-[11px] text-gray-500 mt-1">SSS Salary Credit limit for RA 11210</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Medical De Minimis Cap (PHP/yr)</label>
                                <input type="number" step="500" min="0" name="medical_de_minimis_annual_cap" value="{{ old('medical_de_minimis_annual_cap', $policySettings['medical_de_minimis_annual_cap']) }}" required class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 font-mono font-bold text-gray-900 focus:outline-none focus:border-gray-900">
                                <p class="text-[11px] text-gray-500 mt-1">Tax-free threshold per employee</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: Save Settings and Reset to Defaults -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="showResetModal = true" class="w-full sm:w-auto bg-gray-100 hover:bg-rose-50 text-gray-700 hover:text-rose-700 font-bold text-xs px-4 py-2.5 rounded-xl border border-gray-200 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reset All Policies to Defaults
                    </button>

                    <button type="submit" class="w-full sm:w-auto bg-gray-900 hover:bg-black text-white font-black text-xs px-6 py-3 rounded-xl transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save & Deploy Policy Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Confirm Reset to Defaults Modal -->
        <div x-show="showResetModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showResetModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-black font-outfit text-gray-900">Reset Policies to Defaults?</h2>
                    <button @click="showResetModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>
                <p class="text-xs text-gray-600">
                    This will restore standard company baseline numbers:
                </p>
                <ul class="text-[11px] text-gray-600 space-y-1 bg-gray-50 p-3 rounded-xl border border-gray-100 font-mono">
                    <li>• Fuel Pump Price: PHP 65.00/L</li>
                    <li>• Fuel Efficiency: 10.0 km/L</li>
                    <li>• Fuel Tolerance: 15.0%</li>
                    <li>• Performance Multiplier: PHP 1,500/point</li>
                    <li>• Driver Milestone Tiers: 10/20/30/50 rides</li>
                    <li>• Medical De Minimis Cap: PHP 10,000/yr</li>
                </ul>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showResetModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                    <form action="{{ route('claims.settings.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-sm cursor-pointer">
                            Confirm Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showAddModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Define New Category</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Configure allowable claim category, tax classification, and spending cap</p>
                    </div>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form action="{{ route('claims.categories.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g. Relocation Support" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Category Code</label>
                            <input type="text" name="code" required placeholder="e.g. CAT-RELO" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono font-bold uppercase focus:outline-none focus:border-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Claim Type</label>
                            <select name="type" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="reimbursement">Reimbursement (Expense)</option>
                                <option value="incentive">Incentive (Bonus)</option>
                                <option value="maternity">Maternity Benefit</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tax Classification</label>
                            <select name="tax_classification" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="non_taxable">Non-Taxable Reimbursement</option>
                                <option value="de_minimis">De Minimis Benefit (Capped)</option>
                                <option value="taxable">Taxable Compensation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">De Minimis Annual Cap (PHP)</label>
                            <input type="number" step="0.01" name="de_minimis_annual_cap" placeholder="10000.00" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Spending Limit (PHP)</label>
                            <input type="number" step="0.01" name="max_amount" placeholder="e.g. 5000.00" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono focus:outline-none focus:border-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Applicable To</label>
                            <select name="applicable_to" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="all">All Personnel</option>
                                <option value="driver">TNVS Drivers Only</option>
                                <option value="regular">Office Staff Only</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Description & Policy Guidelines</label>
                        <textarea name="description" rows="2" placeholder="Policy guidelines for this category..." class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showAddModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
            <div @click.away="showEditModal = false" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-black font-outfit text-gray-900">Edit Category Limit & Tax Rule</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Update policy ceiling, tax classification, and applicability</p>
                    </div>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form :action="editCat.updateUrl" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Category Name</label>
                        <input type="text" name="name" x-model="editCat.name" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tax Classification</label>
                            <select name="tax_classification" x-model="editCat.tax_classification" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="non_taxable">Non-Taxable Reimbursement</option>
                                <option value="de_minimis">De Minimis Benefit (Capped)</option>
                                <option value="taxable">Taxable Compensation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">De Minimis Annual Cap (PHP)</label>
                            <input type="number" step="0.01" name="de_minimis_annual_cap" x-model="editCat.de_minimis_annual_cap" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Spending Limit (PHP)</label>
                            <input type="number" step="0.01" name="max_amount" x-model="editCat.max_amount" class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 font-mono focus:outline-none focus:border-gray-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Applicable To</label>
                            <select name="applicable_to" x-model="editCat.applicable_to" required class="w-full text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-gray-800 focus:outline-none focus:border-gray-900">
                                <option value="all">All Personnel</option>
                                <option value="driver">TNVS Drivers Only</option>
                                <option value="regular">Office Staff Only</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Description & Policy Guidelines</label>
                        <textarea name="description" x-model="editCat.description" rows="2" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 focus:outline-none focus:border-gray-900"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showEditModal = false" class="text-xs font-bold text-gray-500 px-4 py-2 hover:text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-gray-900 hover:bg-black text-white font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-sm cursor-pointer">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
