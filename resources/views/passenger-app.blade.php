@extends('layouts.app')

@php
    $pageTitle = 'Passenger App Simulator';
    $currentPage = 'passenger-app';
@endphp

@section('content')
<div class="flex flex-col items-center justify-center py-6 min-h-[80vh]">
    
    <!-- Smartphone Simulator Container -->
    <div class="w-full max-w-[400px] flex flex-col items-center">
        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold font-outfit text-gray-900">Passenger App Simulator (T10)</h2>
            <p class="text-xs text-gray-500">Interactive live prototype of the passenger booking journey</p>
        </div>

        <!-- Phone Shell Container -->
        <div class="relative w-[370px] h-[760px] bg-[#1c1c1e] rounded-[50px] p-3 shadow-2xl border-4 border-gray-800 ring-1 ring-gray-700/50 overflow-hidden flex flex-col">
            
            <!-- Speaker Notch / Dynamic Island -->
            <div class="absolute top-4 left-1/2 -translate-x-1/2 w-32 h-6 bg-black rounded-full z-50 flex items-center justify-center">
                <div class="w-2 h-2 bg-gray-900 rounded-full ml-auto mr-4"></div>
            </div>

            <!-- Status Bar -->
            <div class="h-6 px-6 pt-1 flex justify-between items-center text-[11px] font-semibold text-white/90 z-40 select-none">
                <span id="simulator-time">10:30</span>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.9-1.9C9.07 19.57 10.48 20 12 20c4.97 0 9-4.03 9-9s-4.03-9-9-9zm0 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    <span class="text-[9px]">98%</span>
                </div>
            </div>

            <!-- Simulator Screen Frame (Viewport) -->
            <div class="flex-1 w-full bg-[#fcfbfa] rounded-[38px] overflow-hidden relative flex flex-col mt-1.5 shadow-inner select-none text-gray-800 font-sans">
                
                <!-- SCREEN 1: LOGIN with login_bg.png background -->
                <div id="screen-login" class="absolute inset-0 flex flex-col justify-between p-6 z-30 transition-all duration-300 overflow-hidden">
                    
                    <!-- Background Image & Overlay -->
                    <div class="absolute inset-0 z-0">
                        <img src="{{ asset('login_bg.png') }}" alt="Login Background" class="w-full h-full object-cover object-center">
                        <div class="absolute inset-0 bg-gradient-to-br from-[#1c1c1e]/95 via-[#1c1c1e]/85 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#F44336]/30 via-transparent to-transparent"></div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 flex flex-col justify-center items-center mt-8 z-10">
                        <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center p-2.5 mb-4 border border-white/20 shadow-lg backdrop-blur">
                            <img src="{{ asset('tripwise_icon.png') }}" alt="TripWise Logo" class="w-full h-full object-contain error-fallback-logo">
                        </div>
                        <h3 class="text-xl font-bold font-outfit text-white tracking-tight">TripWise Passenger</h3>
                        <p class="text-xs text-white/50 mt-1">Ride Smart, Arrive Wise.</p>

                        <div class="w-full mt-8 space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-white/40 mb-1">Mobile / Email</label>
                                <input type="text" value="passenger@tripwise.app" class="w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#F44336] transition-colors" readonly>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-white/40 mb-1">Password</label>
                                <input type="password" value="••••••••" class="w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#F44336] transition-colors" readonly>
                            </div>
                        </div>

                        <button onclick="goToScreen('screen-home')" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold rounded-xl py-3 text-xs mt-6 transition-all shadow-lg shadow-brand/20 active:scale-[0.98]">
                            Sign In / Continue
                        </button>
                    </div>
                    <div class="text-center text-[10px] text-white/30 z-10">
                        By continuing, you agree to our Terms and Privacy Policy.
                    </div>
                </div>

                <!-- SCREEN 2: HOME / MAP BOOKING -->
                <div id="screen-home" class="absolute inset-0 bg-[#f4f3f0] flex flex-col z-20 transition-all duration-300 translate-y-full opacity-0">
                    <!-- Map Mock -->
                    <div class="flex-1 relative overflow-hidden bg-emerald-50">
                        <!-- Custom Vector Map -->
                        <svg class="absolute inset-0 w-full h-full text-gray-300" xmlns="http://www.w3.org/2000/svg">
                            <path d="M 0,50 L 400,50 M 0,150 L 400,150 M 0,250 L 400,250 M 0,350 L 400,350 M 0,450 L 400,450" stroke="#e4e2dd" stroke-width="2" />
                            <path d="M 50,0 L 50,700 M 150,0 L 150,700 M 250,0 L 250,700 M 350,0 L 350,700" stroke="#e4e2dd" stroke-width="2" />
                            <path d="M -50,100 L 400,450" stroke="#fdfdfd" stroke-width="22" />
                            <path d="M -50,100 L 400,450" stroke="#bbb" stroke-width="24" class="z-0" style="opacity: 0.2" />
                            <path d="M -50,100 L 400,450" stroke="#ffeb3b" stroke-width="1.5" stroke-dasharray="8 8" />
                            <path d="M 120,-20 L 280,720" stroke="#fdfdfd" stroke-width="18" />
                            
                            <circle cx="100" cy="220" r="7" fill="#F44336" class="animate-pulse" />
                            <circle cx="280" cy="180" r="7" fill="#F44336" />
                            <circle cx="150" cy="390" r="7" fill="#F44336" />

                            <circle cx="210" cy="310" r="18" fill="#F44336" fill-opacity="0.15" />
                            <circle cx="210" cy="310" r="8" fill="#ffffff" stroke="#F44336" stroke-width="3" />
                        </svg>

                        <!-- Header Overlay -->
                        <div class="absolute top-4 left-4 right-4 flex items-center justify-between z-10">
                            <button onclick="goToScreen('screen-login')" class="w-9 h-9 bg-white rounded-xl shadow-md border border-gray-100 flex items-center justify-center text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="bg-white px-3 py-1.5 rounded-full shadow-md border border-gray-100 flex items-center gap-1.5 text-[10px] font-bold text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                GPS Connected
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Destination selector drawer -->
                    <div class="bg-white rounded-t-[32px] p-5 shadow-[0_-8px_30px_rgb(0,0,0,0.06)] border-t border-gray-100">
                        <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-4"></div>
                        <h4 class="text-sm font-bold font-outfit text-gray-900 mb-1">Where are you heading?</h4>
                        <p class="text-[10px] text-gray-400 mb-4">Enter destinations within Metro Manila</p>

                        <div class="space-y-2">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-gray-200 cursor-pointer" onclick="goToScreen('screen-confirm')">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-gray-800">Set Destination...</p>
                                    <p class="text-[9px] text-gray-400">SM Megamall, EDSA Corner Ortigas</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>

                        <!-- Fast Favorites -->
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div class="p-2.5 bg-gray-50 rounded-lg flex items-center gap-2 cursor-pointer hover:bg-gray-100 transition-colors" onclick="selectQuickDestination('BGC, Taguig')">
                                <span class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-800 truncate">Work</p>
                                    <p class="text-[8px] text-gray-400 truncate">BGC, Taguig</p>
                                </div>
                            </div>
                            <div class="p-2.5 bg-gray-50 rounded-lg flex items-center gap-2 cursor-pointer hover:bg-gray-100 transition-colors" onclick="selectQuickDestination('Alabang, Muntinlupa')">
                                <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-gray-800 truncate">Home</p>
                                    <p class="text-[8px] text-gray-400 truncate">Alabang, Metro Manila</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SCREEN 3: CONFIRM RIDE & FARE -->
                <div id="screen-confirm" class="absolute inset-0 bg-[#f4f3f0] flex flex-col z-20 transition-all duration-300 translate-y-full opacity-0">
                    <div class="flex-1 relative overflow-hidden bg-emerald-50">
                        <svg class="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
                            <path d="M 0,100 L 400,100 M 0,250 L 400,250 M 0,400 L 400,400" stroke="#e4e2dd" stroke-width="2" />
                            <path d="M 100,0 L 100,600 M 250,0 L 250,600" stroke="#e4e2dd" stroke-width="2" />
                            <path d="M 210,310 C 240,240 180,180 150,110" fill="none" stroke="#F44336" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" class="animate-pulse" />
                            <circle cx="210" cy="310" r="6" fill="#10B981" stroke="#ffffff" stroke-width="2" />
                            <circle cx="150" cy="110" r="6" fill="#F44336" stroke="#ffffff" stroke-width="2" />
                        </svg>

                        <!-- Header Overlay -->
                        <div class="absolute top-4 left-4 right-4 flex items-center justify-between z-10">
                            <button onclick="goToScreen('screen-home')" class="w-9 h-9 bg-white rounded-xl shadow-md border border-gray-100 flex items-center justify-center text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div class="bg-white px-3 py-1.5 rounded-full shadow-md border border-gray-100 text-[10px] font-bold text-gray-700">
                                Route Found
                            </div>
                        </div>
                    </div>

                    <!-- Fare drawer -->
                    <div class="bg-white rounded-t-[32px] p-5 shadow-[0_-8px_30px_rgb(0,0,0,0.06)] border-t border-gray-100 flex flex-col">
                        <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-4"></div>
                        <h4 class="text-sm font-bold font-outfit text-gray-900 mb-3">Select Vehicle Class</h4>

                        <div class="space-y-2 max-h-[160px] overflow-y-auto">
                            <!-- Option 1 -->
                            <div id="option-sedan" class="p-3 bg-red-50/50 border-2 border-[#F44336] rounded-xl flex items-center justify-between cursor-pointer" onclick="selectVehicle('sedan', 180)">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 text-[#F44336] flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">TripWise Sedan</p>
                                        <p class="text-[9px] text-gray-400">4 Seats • 3 mins away</p>
                                    </div>
                                </div>
                                <p class="text-xs font-extrabold text-gray-900">PHP 180.00</p>
                            </div>
                            <!-- Option 2 -->
                            <div id="option-premium" class="p-3 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-between cursor-pointer hover:bg-gray-100" onclick="selectVehicle('premium', 320)">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">TripWise Premium</p>
                                        <p class="text-[9px] text-gray-400">4 Seats • Executive Car</p>
                                    </div>
                                </div>
                                <p class="text-xs font-extrabold text-gray-900">PHP 320.00</p>
                            </div>
                            <!-- Option 3 -->
                            <div id="option-xl" class="p-3 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-between cursor-pointer hover:bg-gray-100" onclick="selectVehicle('xl', 270)">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">TripWise SUV / XL</p>
                                        <p class="text-[9px] text-gray-400">6 Seats • Spacious</p>
                                    </div>
                                </div>
                                <p class="text-xs font-extrabold text-gray-900">PHP 270.00</p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded bg-gray-100 text-gray-600 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </span>
                                <span class="text-[10px] font-bold text-gray-600">TripWise Pay (PHP 500.00)</span>
                            </div>
                            <span class="text-[10px] text-gray-400 underline cursor-pointer">Change</span>
                        </div>

                        <button onclick="goToScreen('screen-matching')" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold rounded-xl py-3 text-xs transition-all shadow-lg shadow-brand/20 active:scale-[0.98]">
                            Confirm Booking (<span id="btn-price-display">PHP 180.00</span>)
                        </button>
                    </div>
                </div>

                <!-- SCREEN 4: DRIVER MATCHING / ACTIVE RIDE -->
                <div id="screen-matching" class="absolute inset-0 bg-[#f4f3f0] flex flex-col z-20 transition-all duration-300 translate-y-full opacity-0">
                    <div class="flex-1 relative overflow-hidden bg-emerald-50">
                        <svg class="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
                            <path d="M 0,100 L 400,100 M 0,250 L 400,250 M 0,400 L 400,400" stroke="#e4e2dd" stroke-width="2" />
                            <path d="M 100,0 L 100,600 M 250,0 L 250,600" stroke="#e4e2dd" stroke-width="2" />
                            <path id="matching-route" d="M 210,310 C 240,240 180,180 150,110" fill="none" stroke="#F44336" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="210" cy="310" r="6" fill="#10B981" stroke="#ffffff" stroke-width="2" />
                            <circle cx="150" cy="110" r="6" fill="#F44336" stroke="#ffffff" stroke-width="2" />

                            <circle id="moving-car" cx="210" cy="310" r="10" fill="#F44336" stroke="#ffffff" stroke-width="2">
                                <animate attributeName="cx" values="210;225;210;180;150" dur="10s" repeatCount="indefinite" />
                                <animate attributeName="cy" values="310;275;240;180;110" dur="10s" repeatCount="indefinite" />
                            </circle>
                        </svg>

                        <!-- Pulse overlay -->
                        <div id="matching-overlay" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center z-10 transition-opacity duration-300">
                            <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center animate-ping mb-6">
                                <div class="w-10 h-10 bg-[#F44336] rounded-full"></div>
                            </div>
                            <h4 class="text-sm font-bold text-white">Finding a Driver...</h4>
                            <p class="text-[10px] text-white/60 mt-1">Connecting to intelligent dispatch system</p>
                        </div>
                    </div>

                    <!-- Driver Card -->
                    <div id="driver-drawer" class="bg-white rounded-t-[32px] p-5 shadow-[0_-8px_30px_rgb(0,0,0,0.06)] border-t border-gray-100 flex flex-col z-20">
                        <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-4"></div>
                        
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-extrabold text-gray-900">Danilo Santos</h4>
                                <p class="text-[9px] text-gray-400">Toyota Vios • Red (NQR 8847)</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1 py-0.2 rounded border border-amber-200">Rating 4.9</span>
                                    <span class="text-[9px] text-gray-400">LTFRB Compliant</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-[#F44336]">4 mins</p>
                                <p class="text-[9px] text-gray-400">ETA</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-gray-100">
                            <button onclick="goToScreen('screen-home')" class="py-2.5 bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-700 font-bold rounded-xl text-[10px] transition-all">
                                Cancel Trip
                            </button>
                            <button onclick="goToScreen('screen-feedback')" class="py-2.5 bg-[#F44336] text-white hover:bg-[#D32F2F] font-bold rounded-xl text-[10px] transition-all">
                                Mock Arrive / Finish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SCREEN 5: FEEDBACK -->
                <div id="screen-feedback" class="absolute inset-0 bg-white flex flex-col justify-between p-6 z-30 transition-all duration-300 translate-y-full opacity-0">
                    <div class="flex-1 flex flex-col justify-center items-center">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-500 mb-4 border border-emerald-100">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-lg font-bold font-outfit text-gray-900">Arrived at Destination!</h3>
                        <p class="text-[11px] text-gray-400 mt-1 text-center">Your trip with Danilo Santos is completed. Rate your experience below:</p>

                        <div class="flex items-center gap-2 my-6">
                            @for ($i = 1; $i <= 5; $i++)
                            <span onclick="setRating({{ $i }})" class="star-rating cursor-pointer transition-transform hover:scale-125 text-gray-200 inline-block" id="star-{{ $i }}">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </span>
                            @endfor
                        </div>

                        <div class="w-full">
                            <label class="block text-[9px] font-bold uppercase text-gray-400 mb-1">Add a Comment (Optional)</label>
                            <textarea placeholder="Tell us what went well..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:outline-none focus:border-[#F44336] transition-colors h-16 resize-none"></textarea>
                        </div>
                    </div>

                    <button onclick="goToScreen('screen-home')" class="w-full bg-[#F44336] hover:bg-[#D32F2F] text-white font-bold rounded-xl py-3 text-xs transition-all shadow-lg active:scale-[0.98]">
                        Return to Home Screen
                    </button>
                </div>

            </div>

            <!-- Home Indicator Bar -->
            <div class="h-4 flex items-center justify-center select-none z-40">
                <div class="w-28 h-1 bg-white/40 rounded-full cursor-pointer hover:bg-white/60 transition-colors" onclick="goToScreen('screen-home')"></div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    // Update live simulator time
    function updateTime() {
        const timeEl = document.getElementById('simulator-time');
        const now = new Date();
        const hrs = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        if (timeEl) timeEl.textContent = `${hrs}:${mins}`;
    }
    setInterval(updateTime, 1000);
    updateTime();

    // Navigation function for phone simulator screens
    function goToScreen(screenId) {
        const screens = ['screen-login', 'screen-home', 'screen-confirm', 'screen-matching', 'screen-feedback'];
        screens.forEach(s => {
            const el = document.getElementById(s);
            if (el) {
                if (s === screenId) {
                    el.classList.remove('translate-y-full', 'opacity-0');
                    el.classList.add('translate-y-0', 'opacity-100');
                } else {
                    el.classList.remove('translate-y-0', 'opacity-100');
                    el.classList.add('translate-y-full', 'opacity-0');
                }
            }
        });

        if (screenId === 'screen-matching') {
            document.getElementById('matching-overlay').style.opacity = '1';
            document.getElementById('matching-overlay').style.pointerEvents = 'auto';
            setTimeout(() => {
                document.getElementById('matching-overlay').style.opacity = '0';
                document.getElementById('matching-overlay').style.pointerEvents = 'none';
            }, 2500);
        }
    }

    function selectQuickDestination(destination) {
        goToScreen('screen-confirm');
    }

    let currentSelectedPrice = 180;
    function selectVehicle(type, price) {
        currentSelectedPrice = price;
        document.getElementById('btn-price-display').textContent = `₱${price.toFixed(2)}`;

        ['option-sedan', 'option-premium', 'option-xl'].forEach(optId => {
            const el = document.getElementById(optId);
            if (el) {
                el.classList.remove('bg-red-50/50', 'border-2', 'border-[#F44336]');
                el.classList.add('bg-gray-50', 'border', 'border-gray-100');
            }
        });

        const selectedEl = document.getElementById(`option-${type}`);
        if (selectedEl) {
            selectedEl.classList.remove('bg-gray-50', 'border', 'border-gray-100');
            selectedEl.classList.add('bg-red-50/50', 'border-2', 'border-[#F44336]');
        }
    }

    function setRating(rating) {
        for (let i = 1; i <= 5; i++) {
            const star = document.getElementById(`star-${i}`);
            if (star) {
                if (i <= rating) {
                    star.classList.remove('text-gray-200');
                    star.classList.add('text-amber-500');
                } else {
                    star.classList.remove('text-amber-500');
                    star.classList.add('text-gray-200');
                }
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const logo = document.querySelector(".error-fallback-logo");
        if (logo) {
            logo.onerror = function() {
                this.style.display = 'none';
                const parent = this.parentElement;
                parent.innerHTML = '<span class="text-white text-xl font-bold font-outfit">TW</span>';
            };
        }
    });
</script>
@endpush
@endsection
