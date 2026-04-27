@php
    $pendingCount = $driver->documents?->where('status', 'pending')->count() ?? 0;
    $verifiedCount = $driver->documents?->where('status', 'verified')->count() ?? 0;
@endphp

<tr class="hover:bg-blue-50/10 transition-colors group driver-row" 
    id="row-{{ $driver->id }}" 
    data-driver-id="{{ $driver->id }}"
    data-driver-info="{{ json_encode($driver->load(['documents', 'vehicle', 'statistics'])) }}">
    
    {{-- Checkbox --}}
    <td class="px-4 py-3">
        <input type="checkbox" 
               class="w-3.5 h-3.5 text-[#1C69D4] bg-white border-gray-300 rounded focus:ring-[#1C69D4] cursor-pointer row-checkbox" 
               data-id="{{ $driver->id }}">
    </td>

    {{-- Driver Info --}}
    <td class="px-3 py-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-blue-50 text-[#1C69D4] flex items-center justify-center text-xs font-black border border-blue-100 shrink-0">
                {{ strtoupper(substr($driver->full_name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="font-black text-gray-900 text-xs sm:text-sm truncate">{{ $driver->full_name }}</p>
                <p class="text-[10px] font-bold text-gray-400 truncate">{{ $driver->email }}</p>
            </div>
        </div>
    </td>

    {{-- Phone --}}
    <td class="px-3 py-3 text-xs font-semibold text-gray-600 whitespace-nowrap">{{ $driver->mobile_number }}</td>

    {{-- Vehicle --}}
    <td class="px-3 py-3 text-xs font-semibold text-gray-600 whitespace-nowrap">
        {{ $driver->vehicle?->model ?? 'Not Set' }}
    </td>

    {{-- Trips --}}
    <td class="px-3 py-3 text-xs font-black text-gray-900 whitespace-nowrap">{{ $driver->statistics?->total_trips ?? 0 }}</td>

    {{-- Rating --}}
    <td class="px-3 py-3">
        <div class="flex items-center gap-1">
            <svg class="w-3 h-3 text-yellow-400 fill-current shrink-0" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="font-black text-gray-900 text-xs">{{ $driver->average_rating }}</span>
        </div>
    </td>

    {{-- Earnings --}}
    <td class="px-3 py-3 text-xs font-black text-green-600 whitespace-nowrap">PKR {{ $driver->total_earnings }}</td>

    {{-- Status --}}
    <td class="px-3 py-3">
        @if($driver->status === 'suspended')
            <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-red-50 text-red-600 border border-red-100 whitespace-nowrap">Suspended</span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Active</span>
        @endif
    </td>

    {{-- Docs Badge --}}
    <td class="px-3 py-3 text-center" id="docs-badge-{{ $driver->id }}">
        @if($verifiedCount == 6)
            <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Verified</span>
        @elseif($pendingCount > 0)
            <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-orange-50 text-orange-500 border border-orange-100 whitespace-nowrap">{{ $pendingCount }} Pending</span>
        @else
            <span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-gray-100 text-gray-500 border border-gray-200 whitespace-nowrap">Incomplete</span>
        @endif
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3 text-right">
        <div class="flex items-center justify-end gap-1">
            {{-- View Profile (Opens Drawer) --}}
            <button data-action="open-drawer" data-driver-id="{{ $driver->id }}"
                    class="w-7 h-7 flex items-center justify-center text-[#1C69D4] hover:bg-blue-50 rounded-lg transition-all" 
                    title="View Profile">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
            
            {{-- Edit (Opens Edit Modal) --}}
            <button data-action="open-edit" data-driver-id="{{ $driver->id }}"
                    class="w-7 h-7 flex items-center justify-center text-gray-400 hover:bg-gray-100 rounded-lg transition-all" 
                    title="Edit Driver">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
        </div>
    </td>
</tr>
