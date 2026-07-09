<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to the Swagger UI on the local for convenience
    if (app()->environment('local')) {
        return redirect('/api/documentation');
    }

    return response()->json([
        'name' => 'FinanceFlow API',
        'version' => '1.0.0',
        'status' => 'healthy',
    ]);
});
