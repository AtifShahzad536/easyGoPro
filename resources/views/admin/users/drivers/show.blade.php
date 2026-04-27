@extends('layouts.app')

@section('title', 'Driver Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header with Back Button --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('drivers.index') }}" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-[0.12em] mb-1">Driver Details</p>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">{{ $driver->full_name }}</h2>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-100 rounded-xl text-green-700 font-bold">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Profile Info --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Profile Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full bg-blue-50 text-[#1C69D4] flex items-center justify-center text-2xl font-black border-2 border-blue-100 shadow-lg">
                        {{ strtoupper(substr($driver->full_name, 0, 2)) }}
                    </div>
                    <h3 class="mt-4 text-xl font-black text-gray-900">{{ $driver->full_name }}</h3>
                    <p class="text-sm font-bold text-[#1C69D4] uppercase tracking-widest">{{ $driver->vehicle?->make }} {{ $driver->vehicle?->model }}</p>
                    
                    <div class="flex items-center gap-1 mt-2">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="text-xs font-bold text-gray-400 ml-1">{{ $driver->statistics?->average_rating ?? 0 }} rating</span>
                    </div>
                </div>

                {{-- Info Items --}}
                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-9 h-9 rounded-lg bg-white border border-gray-100 flex items-center justify-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Phone</p>
                            <p class="text-sm font-black text-gray-900">{{ $driver->mobile_number }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-9 h-9 rounded-lg bg-white border border-gray-100 flex items-center justify-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                            @if($driver->status === 'suspended')
                                <span class="inline-flex px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-full bg-red-50 text-red-600 border border-red-100">SUSPENDED</span>
                            @else
                                <span class="inline-flex px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100">ACTIVE</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 mt-6">
                    <div class="p-3 bg-gray-50 rounded-xl text-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Trips</p>
                        <p class="text-xl font-black text-gray-900">{{ $driver->statistics?->total_trips ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl text-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Rating</p>
                        <p class="text-xl font-black text-gray-900">{{ $driver->statistics?->average_rating ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-xl text-center">
                        <p class="text-[9px] font-black text-green-500 uppercase tracking-widest">Earned</p>
                        <p class="text-sm font-black text-green-600">PKR {{ $driver->statistics?->total_earnings ?? 0 }}</p>
                    </div>
                </div>

                {{-- Suspend/Activate Button --}}
                <div class="mt-6">
                    @if($driver->status === 'suspended')
                        <form action="{{ route('drivers.activate', $driver) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-green-600 text-white font-black text-xs rounded-xl shadow-lg hover:bg-green-700 transition-all uppercase tracking-widest">Activate Driver</button>
                        </form>
                    @else
                        <form action="{{ route('drivers.suspend', $driver) }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('Are you sure you want to suspend this driver?')" class="w-full py-3 bg-[#1C69D4] text-white font-black text-xs rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all uppercase tracking-widest">Suspend Driver</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column - Documents --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-lg font-black text-gray-900 mb-4">Documents</h3>

                @php
                    $pendingCount = $driver->documents?->where('status', 'pending')->count() ?? 0;
                    $verifiedCount = $driver->documents?->where('status', 'verified')->count() ?? 0;
                @endphp

                @if($driver->documents && $driver->documents->count() > 0)
                    <div class="space-y-3">
                        @foreach($driver->documents as $doc)
                            @php
                                $isPdf = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION)) === 'pdf';
                                $statusClass = match($doc->status) {
                                    'verified' => 'bg-green-50 text-green-600 border-green-100',
                                    'pending' => 'bg-orange-50 text-orange-500 border-orange-100',
                                    'rejected' => 'bg-red-50 text-red-500 border-red-100',
                                    default => 'bg-gray-100 text-gray-500 border-gray-200'
                                };
                            @endphp
                            
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 shrink-0 overflow-hidden">
                                            @if($isPdf)
                                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/></svg>
                                            @else
                                                <img src="/storage/{{ $doc->file_path }}" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 uppercase">{{ str_replace('_', ' ', $doc->type) }}</p>
                                            <p class="text-xs text-gray-400">Updated: {{ $doc->updated_at->format('d/m/Y') }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border {{ $statusClass }}">
                                        {{ ucfirst($doc->status) }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="/storage/{{ $doc->file_path }}" target="_blank" class="px-3 py-1.5 bg-white border border-gray-200 text-xs font-black uppercase tracking-widest rounded-lg hover:bg-gray-50 transition-all text-gray-600">
                                        View Full
                                    </a>

                                    @if($doc->status === 'pending')
                                        <form action="{{ route('admin.documents.approve', $doc) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-green-500 text-white text-xs font-black uppercase tracking-widest rounded-lg hover:bg-green-600 transition-all">
                                                Approve
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.documents.reject', $doc) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="reason" value="Document does not meet requirements">
                                            <button type="submit" onclick="return confirm('Reject this document?')" class="px-3 py-1.5 bg-red-50 text-red-600 border border-red-100 text-xs font-black uppercase tracking-widest rounded-lg hover:bg-red-100 transition-all">
                                                Reject
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if($doc->status === 'rejected' && $doc->rejection_reason)
                                    <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-100">
                                        <p class="text-xs font-black text-red-600 uppercase">Rejection Reason:</p>
                                        <p class="text-sm text-red-500 mt-1">{{ $doc->rejection_reason }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <div class="w-14 h-14 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center text-gray-300 mx-auto mb-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-black text-gray-400 uppercase tracking-widest">No documents uploaded yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
