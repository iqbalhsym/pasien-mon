<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Equipment;

$rm = '00388031';
$correctDob = '1970-06-14';

$patient = Equipment::where('serial_number', $rm)->first();
if ($patient) {
    echo "Current DB date of birth for RM {$rm}: " . ($patient->tanggal_lahir ?? 'NULL') . "\n";
    if ($patient->tanggal_lahir !== $correctDob) {
        $patient->tanggal_lahir = $correctDob;
        $patient->save();
        echo "Successfully updated DB date of birth to: {$correctDob}\n";
    } else {
        echo "DB date of birth is already correct ({$correctDob}).\n";
    }
} else {
    echo "Patient RM {$rm} not found in database to update.\n";
}
