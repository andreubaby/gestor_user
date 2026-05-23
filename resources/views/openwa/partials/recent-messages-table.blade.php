@forelse($recentMessages as $msg)
    @if ($loop->first)
        <div class="overflow-x-auto -mx-6 -mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-100 bg-gradient-to-r from-slate-50 to-slate-100">
                        <th class="px-6 py-4 text-left font-semibold text-slate-700">Dirección</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-700">Chat ID</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-700">Estado</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-700">Mensaje</th>
                        <th class="px-6 py-4 text-left font-semibold text-slate-700">Hora</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
    @endif
    <tr
        id="message-row-{{ $msg->id }}"
        data-message-id="{{ $msg->id }}"
        data-status="{{ $msg->status ?? 'unknown' }}"
        class="transition-all duration-500 hover:bg-blue-50 cursor-pointer group/row scroll-mt-24"
    >
        <td class="px-6 py-4">
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold @if($msg->direction === 'incoming') bg-indigo-100 text-indigo-700 @else bg-emerald-100 text-emerald-700 @endif">
                @if($msg->direction === 'incoming')
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                    Entrante
                @else
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5z"></path></svg>
                    Saliente
                @endif
            </span>
        </td>
        <td class="px-6 py-4">
            <span class="font-mono text-xs text-slate-500">{{ substr($msg->chat_id, 0, 12) }}...</span>
        </td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold
                @if($msg->status === 'sent') bg-green-100 text-green-700
                @elseif($msg->status === 'pending') bg-amber-100 text-amber-700
                @elseif($msg->status === 'failed') bg-red-100 text-red-700
                @elseif($msg->status === 'delivered') bg-sky-100 text-sky-700
                @elseif($msg->status === 'read') bg-violet-100 text-violet-700
                @else bg-slate-100 text-slate-700 @endif">
                <div class="h-2 w-2 rounded-full
                    @if($msg->status === 'sent') bg-green-500
                    @elseif($msg->status === 'pending') bg-amber-500
                    @elseif($msg->status === 'failed') bg-red-500
                    @elseif($msg->status === 'delivered') bg-sky-500
                    @elseif($msg->status === 'read') bg-violet-500
                    @else bg-slate-400 @endif"></div>
                {{ ucfirst($msg->status ?? 'unknown') }}
            </span>
            @if($msg->status === 'failed' && $msg->error_message)
                <div class="mt-1 max-w-xs truncate text-[11px] text-red-600" title="{{ $msg->error_message }}">
                    {{ $msg->error_message }}
                </div>
            @endif
        </td>
        <td class="px-6 py-4 max-w-xs truncate text-slate-700">{{ $msg->text }}</td>
        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">{{ ($msg->sent_at ?? $msg->created_at)?->format('H:i:s') }}</td>
    </tr>
    @if ($loop->last)
                </tbody>
            </table>
        </div>
    @endif
@empty
    <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm text-slate-600 font-medium">Sin mensajes registrados</p>
        <p class="text-xs text-slate-500 mt-1">Los mensajes enviados y recibidos aparecerán aquí</p>
    </div>
@endforelse


