{{-- Edit Driver Modal --}}
<div id="editModal" class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4 hidden">
    <div id="editModalBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <div id="editModalPanel" class="relative w-full sm:max-w-lg bg-white sm:rounded-[2.5rem] rounded-t-[2rem] shadow-2xl overflow-hidden max-h-[95vh] flex flex-col transform translate-y-full sm:translate-y-4 sm:scale-95 transition-transform duration-300">
        
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-lg font-black text-gray-900">Edit Driver</h3>
                <p id="editDriverName" class="text-sm font-bold text-gray-400 mt-0.5"></p>
            </div>
            <button id="closeEditModalBtn" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Form --}}
        <div class="p-6 overflow-y-auto">
            <div class="space-y-5">
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Full Name</label>
                    <input type="text" id="editFullName" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Phone Number</label>
                    <input type="text" id="editPhone" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Vehicle Model</label>
                    <input type="text" id="editVehicleModel" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Account Status</label>
                        <select id="editStatus" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all cursor-pointer">
                            <option>Online</option><option>Offline</option><option>Suspended</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Document Status</label>
                        <select id="editDocStatus" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all cursor-pointer">
                            <option>Verified</option><option>Pending</option><option>Incomplete</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-6 border-t border-gray-100 shrink-0">
            <div class="flex gap-3">
                <button id="cancelEditBtn" class="flex-1 py-3.5 bg-gray-100 text-gray-700 font-black text-xs rounded-xl hover:bg-gray-200 transition-all uppercase tracking-widest">Cancel</button>
                <button id="saveDriverEdit" class="flex-1 py-3.5 bg-[#1C69D4] text-white font-black text-xs rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all uppercase tracking-widest">Save Changes</button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Document Modal --}}
<div id="rejectModal" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center sm:p-4 hidden">
    <div id="rejectModalBackdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <div id="rejectModalPanel" class="relative w-full sm:max-w-md bg-white sm:rounded-[2rem] rounded-t-[2rem] shadow-2xl overflow-hidden transform translate-y-full sm:scale-95 sm:translate-y-4 transition-transform duration-300">
        
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Reject Document</h3>
            <p class="text-sm font-medium text-gray-400 mt-0.5">Please provide a reason for rejection</p>
        </div>

        {{-- Content --}}
        <div class="p-6">
            <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea id="rejectionReason" rows="4" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-red-500/10 focus:border-red-400 transition-all resize-none" placeholder="Enter reason..."></textarea>
        </div>

        {{-- Actions --}}
        <div class="p-6 border-t border-gray-100">
            <div class="flex gap-3">
                <button onclick="closeRejectModal()" class="flex-1 py-3.5 bg-gray-100 text-gray-700 font-black text-xs rounded-xl hover:bg-gray-200 transition-all uppercase tracking-widest">Cancel</button>
                <button id="rejectDocBtn" onclick="submitRejectForm()" disabled class="flex-1 py-3.5 bg-red-500 text-white font-black text-xs rounded-xl shadow-lg shadow-red-200 hover:bg-red-600 transition-all uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed">Reject Document</button>
            </div>
        </div>
    </div>
</div>
