@extends('layouts.app')

@section('title', 'Drivers')

@push('scripts')
    @vite(['resources/js/drivers/index.js'])
@endpush

@section('content')
<div class="container mx-auto px-4 py-6" id="driverApp">
    

    <div class="space-y-4 px-2 sm:px-0">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-100 rounded-xl text-green-700 font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-100 rounded-xl text-red-700 font-bold">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filter Bar --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 sm:p-4">
            <div class="flex flex-col gap-3">
                {{-- Search --}}
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-10 pr-4 py-2.5 text-sm font-medium text-gray-900 border border-gray-100 rounded-xl bg-gray-50/50 focus:ring-4 focus:ring-[#1C69D4]/5 focus:border-[#1C69D4] focus:outline-none transition-all placeholder-gray-400" placeholder="Search by name, phone, or vehicle...">
                </div>
                {{-- Filters + Export --}}
                <div class="flex flex-wrap items-center gap-2">
                    <select id="statusFilter" class="flex-1 min-w-[110px] border border-gray-100 text-xs font-black uppercase tracking-widest text-gray-500 rounded-xl px-3 py-2.5 bg-gray-50/50 outline-none hover:border-blue-200 transition-all focus:ring-4 focus:ring-blue-100 cursor-pointer">
                        <option>All Status</option><option>Online</option><option>Offline</option><option>Suspended</option>
                    </select>
                    <select id="docsFilter" class="flex-1 min-w-[110px] border border-gray-100 text-xs font-black uppercase tracking-widest text-gray-500 rounded-xl px-3 py-2.5 bg-gray-50/50 outline-none hover:border-blue-200 transition-all focus:ring-4 focus:ring-blue-100 cursor-pointer">
                        <option>All Docs</option><option>Verified</option><option>Pending</option><option>Expired</option>
                    </select>
                    <button onclick="exportData()" class="flex items-center gap-2 px-4 py-2.5 bg-[#1C69D4] text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        <p class="text-xs font-black text-gray-400 uppercase tracking-widest px-1">Total Drivers: <span class="text-gray-700">{{ $drivers->count() }}</span></p>

        {{-- Table Block --}}
        <div class="bg-white rounded-2xl sm:rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <div class="w-full overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
                <table class="w-full text-left border-collapse" style="min-width: 750px;">
                    <thead class="border-b border-gray-100">
                        <tr class="text-[9px] text-gray-400 font-black uppercase tracking-[0.12em] bg-gray-50/60">
                            <th class="px-4 py-3.5 w-10">
                                <input type="checkbox" id="selectAll" class="w-3.5 h-3.5 text-[#1C69D4] bg-white border-gray-300 rounded focus:ring-[#1C69D4] cursor-pointer">
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
                            @include('admin.users.drivers.partials.table-row', ['driver' => $driver])
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-4 sm:px-6 py-4 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Showing 1 to {{ $drivers->count() }} of {{ $drivers->count() }} entries</p>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white border border-gray-100 rounded-xl shadow-sm" disabled>Previous</button>
                    <button class="w-9 h-9 text-[11px] font-black text-white bg-[#1C69D4] rounded-xl shadow-lg shadow-blue-200">1</button>
                    <button class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white border border-gray-100 rounded-xl shadow-sm" disabled>Next</button>
                </div>
            </div>
        </div>

        {{-- Driver Profile Side Drawer --}}
        @include('admin.users.drivers.partials.drawer')

        {{-- Modals --}}
        @include('admin.users.drivers.partials.modals')

    </div>
</div>
@endsection
