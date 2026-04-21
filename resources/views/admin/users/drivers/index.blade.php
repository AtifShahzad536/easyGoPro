@extends('layouts.app')

@section('title', 'Drivers')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between w-full mb-6">
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Drivers</h2>
    </div>

    <div class="space-y-4 px-2 sm:px-0" x-data="{
        selectedDriver: null,
        drawerOpen: false,
        activeTab: 'overview',
        editOpen: false,
        editData: null,
        selectAll: false,
        selected: [],
        rejectModalOpen: false,
        rejectDocId: null,
        rejectionReason: '',
        drivers: @json($drivers->pluck('id')->toArray()),
        showRejectModal(docId) {
            this.rejectDocId = docId;
            this.rejectModalOpen = true;
            this.rejectionReason = '';
        },
        async approveDocument(docId) {
            try {
                const response = await fetch('/driver-documents/' + docId + '/approve', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const doc = this.selectedDriver.documents.find(d => d.id === docId);
                    if (doc) doc.status = 'verified';
                    alert('Document approved successfully!');
                    window.location.reload();
                } else {
                    const error = await response.json();
                    alert('Failed to approve: ' + (error.message || 'Unknown error'));
                }
            } catch (e) { 
                console.error('Approve failed:', e);
                alert('Error: ' + e.message);
            }
        },
        async rejectDocument() {
            try {
                const response = await fetch('/driver-documents/' + this.rejectDocId + '/reject', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: this.rejectionReason })
                });
                if (response.ok) {
                    const doc = this.selectedDriver.documents.find(d => d.id === this.rejectDocId);
                    if (doc) { doc.status = 'rejected'; doc.rejection_reason = this.rejectionReason; }
                    this.rejectModalOpen = false;
                    alert('Document rejected successfully!');
                    window.location.reload();
                } else {
                    const error = await response.json();
                    alert('Failed to reject: ' + (error.message || 'Unknown error'));
                }
            } catch (e) { 
                console.error('Reject failed:', e);
                alert('Error: ' + e.message);
            }
        },
        toggleAll() { this.selectAll ? this.selected = [...this.drivers] : this.selected = []; },
        toggleRow(id) {
            this.selected.includes(id) ? this.selected = this.selected.filter(n => n !== id) : this.selected.push(id);
            this.selectAll = this.selected.length === this.drivers.length;
        }
    }">

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 sm:p-4">
            <div class="flex flex-col gap-3">
                <!-- Search -->
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-10 pr-4 py-2.5 text-sm font-medium text-gray-900 border border-gray-100 rounded-xl bg-gray-50/50 focus:ring-4 focus:ring-[#1C69D4]/5 focus:border-[#1C69D4] focus:outline-none transition-all placeholder-gray-400" placeholder="Search by name, phone, or vehicle...">
                </div>
                <!-- Filters + Export -->
                <div class="flex flex-wrap items-center gap-2">
                    <select class="flex-1 min-w-[110px] border border-gray-100 text-xs font-black uppercase tracking-widest text-gray-500 rounded-xl px-3 py-2.5 bg-gray-50/50 outline-none hover:border-blue-200 transition-all focus:ring-4 focus:ring-blue-100 cursor-pointer">
                        <option>All Status</option><option>Online</option><option>Offline</option><option>Suspended</option>
                    </select>
                    <select class="flex-1 min-w-[110px] border border-gray-100 text-xs font-black uppercase tracking-widest text-gray-500 rounded-xl px-3 py-2.5 bg-gray-50/50 outline-none hover:border-blue-200 transition-all focus:ring-4 focus:ring-blue-100 cursor-pointer">
                        <option>All Docs</option><option>Verified</option><option>Pending</option><option>Expired</option>
                    </select>
                    <button class="flex items-center gap-2 px-4 py-2.5 bg-[#1C69D4] text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        <p class="text-xs font-black text-gray-400 uppercase tracking-widest px-1">Total Drivers: <span class="text-gray-700">{{ $drivers->count() }}</span></p>

        <!-- Table Block -->
        <div class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <div class="w-full overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                <table class="w-full text-left border-collapse" style="min-width: 750px;">
                    <thead class="border-b border-gray-100">
                        <tr class="text-[9px] text-gray-400 font-black uppercase tracking-[0.12em] bg-gray-50/60">
                            <th class="px-4 py-3.5 w-10">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-3.5 h-3.5 text-[#1C69D4] bg-white border-gray-300 rounded focus:ring-[#1C69D4] cursor-pointer">
                            </th>
                            <th class="px-3 py-3.5">Driver</th>
                            <th class="px-3 py-3.5">Phone</th>
                            <th class="px-3 py-3.5">Vehicle</th>
                            <th class="px-3 py-3.5">Trips</th>
                            <th class="px-3 py-3.5">Rating</th>
                            <th class="px-3 py-3.5">Earnings</th>
                            <th class="px-3 py-3.5">Status</th>
                            <th class="px-3 py-3.5 text-center">Docs</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($drivers as $driver)
                        <tr class="hover:bg-blue-50/10 transition-colors group" :class="selected.includes({{ $driver->id }}) ? 'bg-blue-50/20' : ''">
                            <td class="px-4 py-3">
                                <input type="checkbox" :checked="selected.includes({{ $driver->id }})" @change="toggleRow({{ $driver->id }})" class="w-3.5 h-3.5 text-[#1C69D4] bg-white border-gray-300 rounded focus:ring-[#1C69D4] cursor-pointer">
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-[#1C69D4] flex items-center justify-center font-black text-[10px] border border-blue-100 uppercase shrink-0">
                                        {{ substr($driver->name, 0, 2) }}
                                    </div>
                                    <span class="font-black text-gray-900 tracking-tight text-xs whitespace-nowrap">{{ $driver->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs font-semibold text-gray-500 whitespace-nowrap">{{ $driver->mobile_number }}</td>
                            <td class="px-3 py-3 text-xs font-bold text-gray-800 whitespace-nowrap">{{ $driver->vehicle?->model ?? 'Not Set' }}</td>
                            <td class="px-3 py-3 text-xs font-black text-gray-900">{{ $driver->total_trips }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-yellow-400 fill-current shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span class="font-black text-gray-900 text-xs">{{ $driver->average_rating }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs font-black text-green-600 whitespace-nowrap">PKR {{ $driver->total_earnings }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Active</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @php
                                    $pendingCount = $driver->documents?->where('status', 'pending')->count() ?? 0;
                                    $verifiedCount = $driver->documents?->where('status', 'verified')->count() ?? 0;
                                @endphp
                                @if($verifiedCount == 6)
                                    <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Verified</span>
                                @elseif($pendingCount > 0)
                                    <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-orange-50 text-orange-500 border border-orange-100 whitespace-nowrap">{{ $pendingCount }} Pending</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-gray-100 text-gray-500 border border-gray-200 whitespace-nowrap">Incomplete</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="selectedDriver = {{ json_encode($driver->load(['documents', 'vehicle', 'statistics'])) }}; drawerOpen = true; activeTab = 'overview'"
                                            class="w-7 h-7 flex items-center justify-center text-[#1C69D4] hover:bg-blue-50 rounded-lg transition-all" title="View Profile">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button @click="editData = {{ json_encode($driver->load(['vehicle', 'statistics'])) }}; editOpen = true"
                                            class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-all" title="Edit Driver">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-4 sm:px-6 py-4 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Showing 1 to {{ $drivers->count() }} of {{ $drivers->count() }} entries</p>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white border border-gray-100 rounded-xl shadow-sm" disabled>Previous</button>
                    <button class="w-9 h-9 text-[11px] font-black text-white bg-[#1C69D4] rounded-xl shadow-lg shadow-blue-200">1</button>
                    <button class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white border border-gray-100 rounded-xl shadow-sm" disabled>Next</button>
                </div>
            </div>
        </div>

        <!-- ---- Driver Profile Side Drawer ---- -->
        <div class="fixed inset-0 z-[60] overflow-hidden" x-show="drawerOpen" x-cloak @keydown.escape.window="drawerOpen = false">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"
                 x-show="drawerOpen"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="drawerOpen = false"></div>

            <!-- Full width on mobile, max-xl on desktop -->
            <div class="absolute inset-y-0 right-0 w-full sm:max-w-xl bg-white shadow-2xl flex flex-col"
                 x-show="drawerOpen"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-full opacity-0">

                <!-- Drawer Header -->
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight">Driver Profile</h3>
                    <button @click="drawerOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto overscroll-contain">
                    <template x-if="selectedDriver">
                        <div>
                            <!-- Hero -->
                            <div class="flex flex-col items-center py-6 px-4 sm:px-8 bg-gray-50/50 border-b border-gray-100">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-blue-50 text-[#1C69D4] flex items-center justify-center text-xl sm:text-2xl font-black border-2 border-blue-100 shadow-lg" x-text="selectedDriver.full_name ? selectedDriver.full_name.substring(0, 2).toUpperCase() : ''"></div>
                                <h4 class="text-lg sm:text-xl font-black text-gray-900 mt-3 tracking-tight text-center" x-text="selectedDriver.full_name"></h4>
                                <div class="flex items-center gap-1 mt-2">
                                    <template x-for="i in 5" :key="i">
                                        <svg class="w-3.5 h-3.5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </template>
                                    <span class="text-xs font-bold text-gray-400 ml-1" x-text="(selectedDriver.statistics?.average_rating || 0) + ' rating'"></span>
                                </div>
                                <p class="text-xs font-black text-[#1C69D4] uppercase tracking-widest mt-1.5" x-text="(selectedDriver.vehicle?.make || '') + ' ' + (selectedDriver.vehicle?.model || '')"></p>
                            </div>

                            <!-- Tabs — horizontally scrollable -->
                            <div class="flex border-b border-gray-100 bg-white sticky top-0 z-10 overflow-x-auto">
                                <template x-for="tab in ['overview', 'documents', 'trips', 'earnings', 'ratings']">
                                    <button @click="activeTab = tab"
                                            :class="activeTab === tab ? 'text-[#1C69D4] border-b-2 border-[#1C69D4]' : 'text-gray-400 border-b-2 border-transparent hover:text-gray-600'"
                                            class="flex-1 min-w-[70px] py-3 text-[9px] sm:text-[10px] font-black uppercase tracking-widest capitalize transition-all whitespace-nowrap px-1">
                                        <span x-text="tab.charAt(0).toUpperCase() + tab.slice(1)"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Tab Panels -->
                            <div class="p-4 sm:p-6">

                                <!-- Overview -->
                                <div x-show="activeTab === 'overview'" class="space-y-5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                            <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Phone</p>
                                                <p class="text-sm font-black text-gray-900 mt-0.5" x-text="selectedDriver.mobile_number"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                            <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Vehicle</p>
                                                <p class="text-sm font-black text-gray-900 mt-0.5" x-text="(selectedDriver.vehicle?.make || '') + ' ' + (selectedDriver.vehicle?.model || 'Not Set')"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 rounded-2xl border border-gray-100">
                                            <div class="w-9 h-9 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                                                <span class="inline-flex mt-1 px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg bg-green-50 text-green-600 border border-green-100" x-text="selectedDriver.is_available ? 'Online' : 'Offline'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="p-4 bg-gray-50/70 border border-gray-100 rounded-2xl text-center">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Trips</p>
                                            <p class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5" x-text="selectedDriver.statistics?.total_trips || 0"></p>
                                        </div>
                                        <div class="p-4 bg-gray-50/70 border border-gray-100 rounded-2xl text-center">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Rating</p>
                                            <p class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5" x-text="selectedDriver.statistics?.average_rating || 0"></p>
                                        </div>
                                        <div class="p-4 bg-green-50/50 border border-green-100/60 rounded-2xl text-center">
                                            <p class="text-[9px] font-black text-green-500 uppercase tracking-widest">Earned</p>
                                            <p class="text-sm font-black text-green-600 mt-1.5" x-text="'PKR ' + (selectedDriver.statistics?.total_earnings || 0)"></p>
                                        </div>
                                    </div>

                                    <div class="space-y-2.5 pt-1">
                                        <button class="w-full py-3 bg-[#1C69D4] text-white font-black text-xs rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all uppercase tracking-widest">Suspend Driver</button>
                                        <button class="w-full py-3 bg-white border border-red-100 text-red-500 font-black text-xs rounded-xl hover:bg-red-50 transition-all uppercase tracking-widest">Deactivate Account</button>
                                    </div>
                                </div>

                                <!-- Documents -->
                                <div x-show="activeTab === 'documents'" class="space-y-3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <template x-if="!selectedDriver.documents || selectedDriver.documents.length === 0">
                                        <div class="py-12 text-center">
                                            <div class="w-14 h-14 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center text-gray-300 mx-auto mb-4">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">No documents uploaded yet</p>
                                        </div>
                                    </template>

                                    <template x-for="doc in selectedDriver.documents" :key="doc.id">
                                        <div class="p-4 bg-white border border-gray-100 rounded-2xl hover:border-blue-100 transition-all">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shrink-0 overflow-hidden">
                                                        <template x-if="doc.file_path.toLowerCase().endsWith('.pdf')">
                                                            <div class="w-full h-full flex flex-col items-center justify-center bg-red-50 text-red-500">
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/></svg>
                                                                <span class="text-[6px] font-black uppercase mt-0.5">PDF</span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!doc.file_path.toLowerCase().endsWith('.pdf')">
                                                            <img :src="'/storage/' + doc.file_path" class="w-full h-full object-cover">
                                                        </template>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-black text-gray-900 uppercase tracking-tight truncate" x-text="doc.type.replace('_', ' ')"></p>
                                                        <p class="text-[10px] font-semibold text-gray-400 mt-0.5" x-text="'Updated: ' + new Date(doc.updated_at).toLocaleDateString()"></p>
                                                    </div>
                                                </div>
                                                <div class="shrink-0">
                                                    <span x-show="doc.status === 'verified'" class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest bg-green-50 text-green-600 rounded-lg border border-green-100 whitespace-nowrap">Verified</span>
                                                    <span x-show="doc.status === 'pending'" class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest bg-orange-50 text-orange-500 rounded-lg border border-orange-100 whitespace-nowrap">Pending</span>
                                                    <span x-show="doc.status === 'rejected'" class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest bg-red-50 text-red-500 rounded-lg border border-red-100 whitespace-nowrap">Rejected</span>
                                                </div>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <a :href="'/storage/' + doc.file_path" target="_blank" class="px-3 py-1.5 bg-white border border-gray-100 text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-gray-50 transition-all text-gray-500 inline-block">View Full</a>
                                                <template x-if="doc.status === 'pending'">
                                                    <div class="flex gap-2">
                                                        <button @click="approveDocument(doc.id)" class="flex items-center gap-1 px-3 py-1.5 bg-green-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-green-600 transition-all">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg> Approve
                                                        </button>
                                                        <button @click="showRejectModal(doc.id)" class="flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-red-100 transition-all border border-red-100">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg> Reject
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="doc.status === 'rejected' && doc.rejection_reason" class="mt-3 p-3 bg-red-50/50 rounded-xl border border-red-100/50">
                                                <p class="text-[10px] font-black text-red-600 uppercase tracking-widest">Reason</p>
                                                <p class="text-xs font-medium text-red-500 mt-1" x-text="doc.rejection_reason"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Trips -->
                                <div x-show="activeTab === 'trips'" class="space-y-3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    @foreach([
                                        ['from' => 'Main Boulevard, Gulberg', 'to' => 'DHA Phase 6',        'date' => 'Apr 9, 2026',  'id' => 'TR-9821', 'fare' => 'PKR 650',  'type' => 'Standard', 'status' => 'Completed', 'duration' => '28 min'],
                                        ['from' => 'Johar Town, Block D',     'to' => 'Allama Iqbal Airport','date' => 'Apr 8, 2026',  'id' => 'TR-9820', 'fare' => 'PKR 950',  'type' => 'Premium',  'status' => 'Completed', 'duration' => '42 min'],
                                        ['from' => 'Model Town',              'to' => 'Liberty Market',      'date' => 'Apr 8, 2026',  'id' => 'TR-9819', 'fare' => 'PKR 280',  'type' => 'Mini',     'status' => 'Completed', 'duration' => '14 min'],
                                        ['from' => 'Bahria Town Gate 1',      'to' => 'Emporium Mall',       'date' => 'Apr 7, 2026',  'id' => 'TR-9818', 'fare' => 'PKR 730',  'type' => 'Standard', 'status' => 'Cancelled', 'duration' => '—'],
                                    ] as $trip)
                                    <div class="p-4 bg-white border border-gray-100 rounded-2xl hover:border-blue-100 transition-all">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1.5 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-[#1C69D4] shrink-0"></span>
                                                    <p class="text-xs font-bold text-gray-500 truncate">{{ $trip['from'] }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                                                    <p class="text-sm font-black text-gray-900 truncate">{{ $trip['to'] }}</p>
                                                </div>
                                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">#{{ $trip['id'] }} &bull; {{ $trip['type'] }} &bull; {{ $trip['duration'] }}</p>
                                            </div>
                                            <div class="text-right shrink-0 space-y-1.5">
                                                <p class="text-sm font-black text-gray-900">{{ $trip['fare'] }}</p>
                                                <span class="inline-flex px-2 py-0.5 text-[8px] font-black uppercase tracking-widest rounded-md {{ $trip['status'] === 'Completed' ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-red-50 text-red-500 border border-red-100' }}">{{ $trip['status'] }}</span>
                                                <p class="text-[9px] text-gray-400">{{ $trip['date'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    <button class="w-full py-3.5 text-[10px] font-black text-[#1C69D4] uppercase tracking-[0.15em] hover:bg-blue-50 transition-all rounded-2xl border border-dashed border-blue-100">Load More History</button>
                                </div>

                                <!-- Earnings -->
                                <div x-show="activeTab === 'earnings'" class="space-y-5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-4 sm:p-5 bg-green-50/40 border border-green-100 rounded-2xl">
                                            <p class="text-[9px] font-black text-green-600 uppercase tracking-widest">Total Earnings</p>
                                            <p class="text-xl sm:text-2xl font-black text-green-600 mt-1.5" x-text="selectedDriver.earnings"></p>
                                        </div>
                                        <div class="p-4 sm:p-5 bg-blue-50/40 border border-blue-100 rounded-2xl">
                                            <p class="text-[9px] font-black text-[#1C69D4] uppercase tracking-widest">This Month</p>
                                            <p class="text-xl sm:text-2xl font-black text-[#1C69D4] mt-1.5">PKR 45.2K</p>
                                        </div>
                                    </div>

                                    <div>
                                        <h5 class="text-sm font-black text-gray-900 tracking-tight mb-3">Daily Earnings (Last 8 Days)</h5>
                                        <div class="flex items-end gap-2 px-1" style="height: 160px;">
                                            @foreach([
                                                ['day' => 'Apr 1', 'val' => 65],
                                                ['day' => 'Apr 2', 'val' => 80],
                                                ['day' => 'Apr 3', 'val' => 50],
                                                ['day' => 'Apr 4', 'val' => 95],
                                                ['day' => 'Apr 5', 'val' => 72],
                                                ['day' => 'Apr 6', 'val' => 100],
                                                ['day' => 'Apr 7', 'val' => 68],
                                                ['day' => 'Apr 8', 'val' => 82],
                                            ] as $bar)
                                            <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end group/bar">
                                                <div class="relative w-full bg-[#1C69D4] rounded-t-lg hover:bg-blue-700 transition-colors cursor-default" style="height: {{ $bar['val'] }}%;">
                                                    <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] font-black px-2 py-1 rounded-md opacity-0 group-hover/bar:opacity-100 transition-opacity whitespace-nowrap z-10">PKR {{ 800 + ($bar['val'] * 32) }}</div>
                                                </div>
                                                <span class="text-[8px] font-black text-gray-400 uppercase leading-none">{{ substr($bar['day'], -2) }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Ratings -->
                                <div x-show="activeTab === 'ratings'" class="space-y-4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="flex items-center gap-5 p-4 bg-gray-50/70 rounded-2xl border border-gray-100">
                                        <div class="text-center shrink-0">
                                            <p class="text-4xl sm:text-5xl font-black text-gray-900" x-text="selectedDriver.rating"></p>
                                            <div class="flex items-center gap-0.5 mt-1.5 justify-center">
                                                @foreach(range(1,5) as $s)
                                                <svg class="w-3 h-3 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                @endforeach
                                            </div>
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-1">847 rides</p>
                                        </div>
                                        <div class="flex-1 space-y-1.5">
                                            @foreach([[5, 84], [4, 11], [3, 3], [2, 1], [1, 1]] as [$star, $pct])
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] font-black text-gray-400 w-3 shrink-0">{{ $star }}</span>
                                                <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-yellow-400 rounded-full" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span class="text-[9px] font-bold text-gray-400 w-7 shrink-0">{{ $pct }}%</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach([
                                            ['name' => 'Junaid Khan',  'initials' => 'JK', 'time' => '2 hours ago', 'review' => 'Extremely professional driver. Very clean car, knew all the fastest routes.'],
                                            ['name' => 'Ayesha Raza',  'initials' => 'AR', 'time' => 'Yesterday',   'review' => 'Smooth ride, driver was polite and the AC was perfect. Will book again.'],
                                            ['name' => 'Sohail Mirza', 'initials' => 'SM', 'time' => '2 days ago',  'review' => 'Got me to the airport on time despite traffic. Great experience overall.'],
                                        ] as $review)
                                        <div class="p-4 bg-white border border-gray-100 rounded-2xl space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#1C69D4] flex items-center justify-center text-[10px] font-black border border-blue-100 shrink-0">{{ $review['initials'] }}</div>
                                                    <div>
                                                        <p class="text-xs font-black text-gray-900">{{ $review['name'] }}</p>
                                                        <p class="text-[9px] font-bold text-gray-400">{{ $review['time'] }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-0.5">
                                                    @foreach(range(1,5) as $s)
                                                    <svg class="w-3 h-3 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <p class="text-xs font-medium text-gray-600 leading-relaxed italic">"{{ $review['review'] }}"</p>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ===== Edit Driver Modal ===== -->
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4" x-show="editOpen" x-cloak @keydown.escape.window="editOpen = false">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                 x-show="editOpen"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="editOpen = false"></div>

            <div class="relative w-full sm:max-w-lg bg-white sm:rounded-[2.5rem] rounded-t-[2rem] shadow-2xl overflow-hidden max-h-[95vh] flex flex-col"
                 x-show="editOpen"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full sm:scale-95">

                <template x-if="editData">
                    <div class="flex flex-col max-h-[95vh]">
                        <!-- Drag handle -->
                        <div class="flex justify-center pt-3 pb-1 sm:hidden shrink-0">
                            <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
                        </div>

                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 sm:px-7 py-4 border-b border-gray-100 shrink-0">
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight">Edit Driver</h3>
                                <p class="text-[10px] font-semibold text-gray-400 mt-0.5">Editing: <span class="text-[#1C69D4]" x-text="editData.full_name || ''"></span></p>
                            </div>
                            <button @click="editOpen = false" class="p-1.5 hover:bg-gray-100 rounded-xl text-gray-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Form — scrollable -->
                        <div class="overflow-y-auto overscroll-contain flex-1 px-5 sm:px-7 py-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Full Name</label>
                                    <input type="text" x-model="editData.full_name" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Phone</label>
                                    <input type="text" x-model="editData.mobile_number" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Vehicle Model</label>
                                    <input type="text" x-model="editData.vehicle?.model || ''" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">License Plate</label>
                                    <input type="text" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all" value="LHR-4521">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Account Status</label>
                                    <select class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all cursor-pointer">
                                        <option>Online</option><option>Offline</option><option>Suspended</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Document Status</label>
                                    <select class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all cursor-pointer">
                                        <option>Verified</option><option>Pending</option><option>Incomplete</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Commission Rate</label>
                                <div class="relative">
                                    <input type="number" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all" value="15">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-gray-400">%</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Admin Notes</label>
                                <textarea rows="2" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1C69D4] transition-all resize-none" placeholder="Internal notes about this driver..."></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 sm:px-7 py-4 flex gap-3 border-t border-gray-50 bg-white shrink-0 pb-safe">
                            <button @click="editOpen = false" class="flex-1 py-3 bg-white border border-gray-200 text-gray-600 font-black text-[10px] rounded-xl hover:bg-gray-50 transition-all uppercase tracking-widest">Cancel</button>
                            <button class="flex-1 py-3 bg-[#1C69D4] text-white font-black text-[10px] rounded-xl shadow-lg shadow-blue-200 hover:bg-[#1656b0] transition-all uppercase tracking-widest">Save Changes</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>

    <!-- ===== Document Rejection Modal ===== -->
    <div class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center sm:p-4" x-show="rejectModalOpen" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
             x-show="rejectModalOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="rejectModalOpen = false"></div>

        <div class="relative w-full sm:max-w-md bg-white sm:rounded-[2rem] rounded-t-[2rem] shadow-2xl overflow-hidden"
             x-show="rejectModalOpen"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full sm:scale-95 sm:translate-y-4" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full sm:scale-95">

            <!-- Drag handle -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
            </div>

            <div class="p-5 sm:p-7">
                <div class="w-11 h-11 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 tracking-tight">Reject Document</h3>
                <p class="text-sm font-semibold text-gray-400 mt-1.5">Provide a reason — the driver will see this in their app.</p>
                <div class="mt-5">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Rejection Reason</label>
                    <textarea rows="4" x-model="rejectionReason" required
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all resize-none"
                              placeholder="e.g. The image is too blurry, please upload a clear photo."></textarea>
                </div>
            </div>
            <div class="px-5 sm:px-7 pb-5 sm:pb-7 pb-safe flex gap-3">
                <button @click="rejectModalOpen = false" class="flex-1 py-3 bg-white border border-gray-200 text-gray-600 font-black text-xs rounded-xl hover:bg-gray-50 transition-all uppercase tracking-widest">Cancel</button>
                <button @click="rejectDocument()" :disabled="!rejectionReason" class="flex-1 py-3 bg-red-500 text-white font-black text-xs rounded-xl shadow-lg shadow-red-200 hover:bg-red-600 transition-all uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed">Reject Doc</button>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .pb-safe { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
        .overscroll-contain { overscroll-behavior: contain; }
    </style>
</div>
@endsection