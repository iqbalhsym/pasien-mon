<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HistoryIntegrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('verify.api.token')->group(function () {
    Route::post('/pasien-histori/obat', [HistoryIntegrationController::class, 'updateObatHistory']);
});
