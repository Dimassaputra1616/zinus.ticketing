<div class="space-y-6">
    @foreach($logs as $log)
        <div class="flex items-start space-x-4">
            <div class="w-3 h-3 rounded-full 
                @if($log->status === 'open') bg-green-500
                @elseif($log->status === 'assigned') bg-yellow-500
                @elseif($log->status === 'in_progress') bg-blue-500
                @elseif($log->status === 'waiting_user') bg-purple-500
                @elseif($log->status === 'resolved') bg-green-600
                @elseif($log->status === 'closed') bg-gray-500
                @endif
            "></div>

            <div>
                <p class="font-semibold capitalize">{{ str_replace('_', ' ', $log->status) }}</p>
                @if($log->message)
                    <p class="text-gray-600 text-sm">{{ $log->message }}</p>
                @endif
                <p class="text-gray-400 text-xs mt-1">{{ $log->created_at->format('d M Y — H:i') }}</p>
            </div>
        </div>
    @endforeach
</div>
