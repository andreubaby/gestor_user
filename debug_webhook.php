<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $controller = app(App\Http\Controllers\OpenWAWebhookController::class);
    $request = Illuminate\Http\Request::create('/api/webhooks/openwa', 'POST', [
        'event' => 'message.received',
        'session' => 'default',
        'message' => [
            'id' => '123456789',
            'from' => '34612345678@c.us',
            'body' => 'Hello from WhatsApp',
            'timestamp' => time(),
        ],
    ]);

    $response = $controller->handle($request);
    var_export($response->getData(true));
} catch (Throwable $e) {
    echo get_class($e), ': ', $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
}
