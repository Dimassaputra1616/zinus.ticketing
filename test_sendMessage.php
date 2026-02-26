<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Simulate user sending message
    $userId = 1; // User ID from user's env
    \Illuminate\Support\Facades\Auth::loginUsingId($userId);
    
    $conversation = \App\Models\Conversation::where('user_id', $userId)->where('is_open', true)->first();
    if (!$conversation) {
        $conversation = \App\Models\Conversation::create([
            'user_id' => $userId,
            'is_open' => true,
        ]);
        echo "Created conversation ID: " . $conversation->id . "\n";
    } else {
        echo "Found conversation ID: " . $conversation->id . "\n";
    }

    echo "is_bot_active before update: " . ($conversation->is_bot_active ? 'true' : 'false') . "\n";
    
    if (!$conversation->is_bot_active) {
        $conversation->update(['is_bot_active' => true]);
        echo "Updated is_bot_active to true\n";
    }

    $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $userId,
        'body' => 'Test message',
        'is_read' => false,
    ]);
    echo "Message created\n";

    echo "Dispatching Event...\n";
    \App\Events\MessageCreated::dispatch($message, 'user');
    echo "Event Dispatched\n";

} catch (\Throwable $e) {
    echo "FATAL ERROR:\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
}
