@extends('layouts.app')

@php
    $pageTitle = 'Bonus Allocation Desk';
    $currentPage = 'compensation.bonus-allocation';
@endphp

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black font-outfit text-gray-900 tracking-tight">Bonus Allocation Desk</h1>
            <p class="text-xs text-gray-500 mt-1">Distribute performance, attendance, and loyalty bonuses with Team 3 performance multipliers and tenure weighting.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-800 bg-white border border-gray-200 px-3.5 py-1.5 rounded-full shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Bonus Engine Active
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

    <div x-data="{
        activeTab: 'allocations', {{-- 'allocations', 'config' --}}
        bonusType: 'Performance Bonus',
        poolAmount: 300000,
        selectedDept: '{{ $deptId ?? 'all' }}',
        employees: {{ Js::from($employees->map(function($e) {
            $rating = $e->performance_rating ?? 'Satisfactory';
            $tenureYears = (float)$e->years_of_service;
            
            $perfMult = match($rating) {
                'Outstanding' => 2.0,
                'Very Satisfactory' => 1.5,
                'Satisfactory' => 1.0,
                'Needs Improvement' => 0.5,
                default => 1.0,
            };

            $tenureMult = match(true) {
                $tenureYears >= 5.0 => 1.30,
                $tenureYears >= 3.0 => 1.20,
                $tenureYears >= 1.0 => 1.10,
                default => 1.00,
            };

            return [
                'id' => $e->id,
                'name' => $e->first_name . ' ' . $e->last_name,
                'code' => $e->employee_code,
                'position' => $e->position,
                'department' => $e->department?->name ?? 'Operations',
                'rating' => $rating,
                'perf_multiplier' => $perfMult,
                'tenure_years' => $tenureYears,
                'tenure_multiplier' => $tenureMult,
                'allocated_bonus' => 0,
                'manual_override' => false,
            ];
        })) }},

        updateRating(emp, newRating) {
            emp.rating = newRating;
            emp.perf_multiplier = newRating === 'Outstanding' ? 2.0 : (newRating === 'Very Satisfactory' ? 1.5 : (newRating === 'Satisfactory' ? 1.0 : 0.5));
            this.recalculateAll();
        },

        recalculateAll() {
            const totalWeights = this.employees.reduce((acc, e) => {
                return acc + (e.perf_multiplier * e.tenure_multiplier);
            }, 0);

            if (totalWeights > 0) {
                this.employees.forEach(e => {
                    if (!e.manual_override) {
                        const share = (e.perf_multiplier * e.tenure_multiplier) / totalWeights;
                        e.allocated_bonus = Math.round((this.poolAmount * share) / 100) * 100;
                    }
                });
            }
        },

        get totalAllocated() {
            return this.employees.reduce((acc, e) => acc + (Number(e.allocated_bonus) || 0), 0);
        },

        get remainingPool() {
            return this.poolAmount - this.totalAllocated;
        },

        get poolPercentage() {
            return Math.min(100, Math.round((this.totalAllocated / (this.poolAmount || 1)) * 100));
        },

        init() {
            this.recalculateAll();
        }
    }" class="space-y-6 pb-12">

        <!-- Top Navigation Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-2xl">
                
                <!-- Tab 1: Allocations Table -->
                <button type="button" @click="activeTab = 'allocations'" 
                        :class="activeTab === 'allocations' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Employee Bonus Allocations
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-gray-200 text-gray-800">{{ $employees->count() }} Staff</span>
                </button>

                <!-- Tab 2: Pool Config -->
                <button type="button" @click="activeTab = 'config'" 
                        :class="activeTab === 'config' ? 'bg-white text-gray-900 font-black shadow-sm' : 'text-gray-500 hover:text-gray-900 font-bold'" 
                        class="px-4 py-2 text-xs rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#F44336]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Pool Setup & Multiplier Rules
                </button>
            </div>

            <!-- Quick Pool Summary Pill -->
            <div class="hidden sm:flex items-center gap-3 text-xs font-bold bg-white/80 border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-2xs">
                <span class="text-gray-400">Total Pool:</span>
                <span class="font-black text-gray-900 font-outfit">₱<span x-text="Number(poolAmount).toLocaleString()"></span></span>
                <span class="text-[10px] font-black px-2 py-0.5 rounded-lg" 
                      :class="totalAllocated > poolAmount ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'" 
                      x-text="poolPercentage + '% Allocated'"></span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: EMPLOYEE BONUS ALLOCATIONS TABLE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'allocations'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                
                <form action="{{ route('compensation.bonus-allocation.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bonus_type" :value="bonusType">
                    <input type="hidden" name="pool_amount" :value="poolAmount">
                    <input type="hidden" name="department_id" :value="selectedDept">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4 mb-4">
                        <div>
                            <h2 class="text-base font-extrabold font-outfit text-gray-900">Individual Bonus Allocation Engine</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Calculated shares based on Performance Multiplier × Tenure Weighting. Adjust bonus values inline if required.</p>
                        </div>

                        <button type="submit" :disabled="totalAllocated > poolAmount || totalAllocated === 0" 
                                class="bg-[#F44336] hover:bg-[#D32F2F] disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-black px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirm & Queue Bonuses for Payroll
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                                    <th class="py-3.5 px-4">Employee</th>
                                    <th class="py-3.5 px-4">Position</th>
                                    <th class="py-3.5 px-4 text-center">Team 3 Rating</th>
                                    <th class="py-3.5 px-4 text-center">Tenure Weight</th>
                                    <th class="py-3.5 px-4 text-right">Computed Bonus Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                <template x-for="(emp, idx) in employees" :key="emp.id">
                                    <tr class="hover:bg-gray-50/75 transition-colors">
                                        <td class="py-4 px-4 font-bold text-gray-900">
                                            <input type="hidden" :name="'allocations[' + idx + '][employee_id]'" :value="emp.id">
                                            <div class="font-black text-sm text-gray-900" x-text="emp.name"></div>
                                            <span class="text-xs text-gray-400 font-mono" x-text="emp.code"></span>
                                        </td>
                                        <td class="py-4 px-4 font-medium text-gray-700" x-text="emp.position"></td>
                                        <td class="py-4 px-4 text-center">
                                            <select @change="updateRating(emp, $event.target.value)" 
                                                    class="text-xs font-bold rounded-xl px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-900 focus:outline-none focus:border-[#F44336]">
                                                <option value="Outstanding" :selected="emp.rating === 'Outstanding'">Outstanding (2.0x)</option>
                                                <option value="Very Satisfactory" :selected="emp.rating === 'Very Satisfactory'">Very Satisfactory (1.5x)</option>
                                                <option value="Satisfactory" :selected="emp.rating === 'Satisfactory'">Satisfactory (1.0x)</option>
                                                <option value="Needs Improvement" :selected="emp.rating === 'Needs Improvement'">Needs Improvement (0.5x)</option>
                                            </select>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-gray-100 text-gray-800" 
                                                  x-text="emp.tenure_years.toFixed(1) + ' yrs (' + emp.tenure_multiplier + 'x)'"></span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <div class="inline-flex items-center gap-1.5">
                                                <span class="text-xs font-black text-gray-500 font-outfit">₱</span>
                                                <input type="number" step="100" min="0" :name="'allocations[' + idx + '][bonus_amount]'" 
                                                       x-model.number="emp.allocated_bonus" @input="emp.manual_override = true" 
                                                       class="w-28 text-sm font-black font-outfit text-right bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-gray-900 focus:outline-none focus:border-[#F44336] shadow-2xs">
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: POOL SETUP & MULTIPLIER RULES -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'config'" x-transition class="space-y-6">

            <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">
                
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-base font-extrabold font-outfit text-gray-900">Bonus Pool Parameter Settings</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Configure pool capital and review performance and tenure multiplier equations.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-extrabold text-gray-800 mb-1.5">Bonus Cycle Type</label>
                        <select x-model="bonusType" 
                                class="w-full text-sm font-bold bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:border-[#F44336]">
                            <option value="Performance Bonus">Performance Bonus (Team 3 Rating)</option>
                            <option value="Attendance Bonus">Perfect Attendance Incentive</option>
                            <option value="Loyalty Bonus">Tenure & Loyalty Recognition</option>
                            <option value="Year-End Special Bonus">Year-End Special Company Bonus</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-gray-800 mb-1.5">Total Bonus Pool Amount (₱)</label>
                        <input type="number" step="5000" min="10000" x-model.number="poolAmount" @input="recalculateAll()" 
                               class="w-full text-base font-black font-outfit bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-gray-900 focus:outline-none focus:border-[#F44336]">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-gray-800 mb-1.5">Target Department Scope</label>
                        <select x-model="selectedDept" 
                                class="w-full text-sm font-bold bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:border-[#F44336]">
                            <option value="all">Company-Wide (All Staff)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Multiplier Reference Cards -->
                <div class="pt-4 border-t border-gray-100 space-y-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-400">Statutory Multiplier Reference Weights</h3>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-1">
                            <span class="text-xs font-bold text-emerald-800 block">Outstanding</span>
                            <div class="text-xl font-black font-outfit text-emerald-900">2.0× Weight</div>
                            <span class="text-[11px] text-emerald-700 block">Top Tier Performance</span>
                        </div>

                        <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-200 space-y-1">
                            <span class="text-xs font-bold text-blue-800 block">Very Satisfactory</span>
                            <div class="text-xl font-black font-outfit text-blue-900">1.5× Weight</div>
                            <span class="text-[11px] text-blue-700 block">Above Standard</span>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-1">
                            <span class="text-xs font-bold text-gray-700 block">Satisfactory</span>
                            <div class="text-xl font-black font-outfit text-gray-900">1.0× Weight</div>
                            <span class="text-[11px] text-gray-600 block">Meets Standard</span>
                        </div>

                        <div class="p-4 bg-rose-50/50 rounded-2xl border border-rose-200 space-y-1">
                            <span class="text-xs font-bold text-rose-800 block">Needs Improvement</span>
                            <div class="text-xl font-black font-outfit text-rose-900">0.5× Weight</div>
                            <span class="text-[11px] text-rose-700 block">Minimum Allocation</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection
