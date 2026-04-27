{{-- Driver Profile Side Drawer --}}
<div id="drawerOverlay" class="fixed inset-0 z-[60] overflow-hidden hidden" onclick="closeDrawer()">
    <div id="drawerBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] opacity-0 transition-opacity duration-300"></div>

    <div id="drawerPanel" class="absolute inset-y-0 right-0 w-full sm:max-w-xl bg-white shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-out" onclick="event.stopPropagation()">

        {{-- Drawer Header --}}
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
            <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight">Driver Profile</h3>
            <button id="closeDrawerBtn" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Scrollable Content --}}
        <div id="drawerContent" class="flex-1 overflow-y-auto overscroll-contain hidden">
            <div>
                {{-- Hero Section --}}
                <div class="flex flex-col items-center py-6 px-4 sm:px-8 bg-gray-50/50 border-b border-gray-100">
                    <div id="driverAvatar" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-blue-50 text-[#1C69D4] flex items-center justify-center text-xl sm:text-2xl font-black border-2 border-blue-100 shadow-lg"></div>
                    <h4 id="driverName" class="mt-3 sm:mt-4 text-lg sm:text-xl font-black text-gray-900 tracking-tight text-center"></h4>
                    <div class="flex items-center gap-1.5 mt-1">
                        <div class="flex items-center gap-0.5">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <span id="driverRatingText" class="text-[10px] sm:text-xs font-black text-gray-400 uppercase tracking-widest"></span>
                    </div>
                    <p id="driverVehicle" class="mt-1 text-xs sm:text-sm font-black text-[#1C69D4] uppercase tracking-tight text-center"></p>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-100 bg-white sticky top-0 z-10 overflow-x-auto" onclick="event.stopPropagation()">
                    <button id="tab-overview" data-tab="overview" class="tab-btn flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1 text-[#1C69D4] border-b-2 border-[#1C69D4]">Overview</button>
                    <button id="tab-documents" data-tab="documents" class="tab-btn flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1 text-gray-400 border-b-2 border-transparent hover:text-gray-600">Documents</button>
                    <button id="tab-trips" data-tab="trips" class="tab-btn flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1 text-gray-400 border-b-2 border-transparent hover:text-gray-600">Trips</button>
                    <button id="tab-earnings" data-tab="earnings" class="tab-btn flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1 text-gray-400 border-b-2 border-transparent hover:text-gray-600">Earnings</button>
                    <button id="tab-ratings" data-tab="ratings" class="tab-btn flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1 text-gray-400 border-b-2 border-transparent hover:text-gray-600">Ratings</button>
                </div>

                {{-- Tab Panels --}}
                <div class="p-4 sm:p-6" onclick="event.stopPropagation()">

                    {{-- Overview Panel --}}
                    <div id="panel-overview" class="tab-panel space-y-5">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Phone</p>
                                    <p id="driverPhone" class="text-sm font-black text-gray-900 mt-0.5"></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Vehicle</p>
                                    <p id="driverVehicleDetail" class="text-sm font-black text-gray-900 mt-0.5"></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                                    <span id="driverStatus" class="inline-flex px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 mt-0.5">ACTIVE</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-4 bg-white border border-gray-100 rounded-2xl text-center">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Trips</p>
                                <p id="statTrips" class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5"></p>
                            </div>
                            <div class="p-4 bg-white border border-gray-100 rounded-2xl text-center">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Rating</p>
                                <p id="statRating" class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5"></p>
                            </div>
                            <div class="p-4 bg-green-50/50 border border-green-100/60 rounded-2xl text-center">
                                <p class="text-[9px] font-black text-green-500 uppercase tracking-widest">Earned</p>
                                <p id="statEarnings" class="text-sm font-black text-green-600 mt-1.5"></p>
                            </div>
                        </div>

                        <div class="space-y-2.5 pt-1">
                            <form id="suspendForm" method="POST">
                                @csrf
                                <button type="submit" id="suspendBtn" onclick="return confirm('Are you sure?')" class="w-full py-3 bg-[#1C69D4] text-white font-black text-xs rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all uppercase tracking-widest">Suspend Driver</button>
                            </form>
                        </div>
                    </div>

                    {{-- Documents Panel --}}
                    <div id="panel-documents" class="tab-panel space-y-3 hidden">
                        <div id="noDocumentsMsg" class="py-12 text-center hidden">
                            <div class="w-14 h-14 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center text-gray-300 mx-auto mb-4">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">No documents uploaded yet</p>
                        </div>
                        <div id="documentsList" class="space-y-3"></div>
                    </div>

                    {{-- Other Panels (Placeholder) --}}
                    <div id="panel-trips" class="tab-panel space-y-3 hidden">
                        <p class="text-center text-gray-400 py-8">Trips data coming soon</p>
                    </div>
                    <div id="panel-earnings" class="tab-panel space-y-3 hidden">
                        <p class="text-center text-gray-400 py-8">Earnings data coming soon</p>
                    </div>
                    <div id="panel-ratings" class="tab-panel space-y-3 hidden">
                        <p class="text-center text-gray-400 py-8">Ratings data coming soon</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
