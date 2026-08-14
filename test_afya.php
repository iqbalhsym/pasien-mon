<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\AfyaRegistrationService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('getToken');
$method->setAccessible(true);
$token = $method->invoke($service);

$baseUrl  = env('AFYA_API_URL', 'http://152.118.52.27:8081');
$response = Illuminate\Support\Facades\Http::withHeaders([
    'token'        => $token,
    'Content-Type' => 'application/json',
    'User-Agent'   => 'insomnia/12.4.0',
])
->withoutVerifying()
->post($baseUrl . '/api/v8/transaction/registration/list', [
    'PageNumber'          => 1,
    'PageSize'            => 1,
    'OrderBy'             => 'NoBilling DESC',
    'MRN'                 => '00215379',
]);

echo json_encode($response->json(), JSON_PRETTY_PRINT);
