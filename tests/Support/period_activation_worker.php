<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

$app->make(
    \Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

if (
    $app->environment() !== 'testing' ||
    DB::connection()->getDatabaseName() !== 'ppdb_v2_test'
) {
    fwrite(STDERR, "Unsafe database environment.\n");
    exit(2);
}

$userId = (int) ($argv[1] ?? 0);
$periodId = (int) ($argv[2] ?? 0);
$payloadFile = $argv[3] ?? '';
$resultFile = $argv[4] ?? '';

if (
    $userId <= 0 ||
    $periodId <= 0 ||
    !is_file($payloadFile) ||
    $resultFile === ''
) {
    fwrite(STDERR, "Invalid worker arguments.\n");
    exit(3);
}

$payload = json_decode(
    file_get_contents($payloadFile),
    true,
    512,
    JSON_THROW_ON_ERROR
);

$user = \App\Models\User::findOrFail($userId);

Auth::login($user);

$request = Request::create(
    '/admin/pengaturan/periode/' . $periodId,
    'PUT',
    $payload
);

$connectionId = DB::selectOne(
    'SELECT CONNECTION_ID() AS id'
)->id;

$response = $app->handle($request);

$result = [
    'database' => DB::connection()->getDatabaseName(),
    'connection_id' => $connectionId,
    'period_id' => $periodId,
    'http_status' => $response->getStatusCode(),
    'location' => $response->headers->get('Location'),
];

file_put_contents(
    $resultFile,
    json_encode(
        $result,
        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
    )
);

echo json_encode($result, JSON_THROW_ON_ERROR), PHP_EOL;
