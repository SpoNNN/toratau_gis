<?php
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilterController;
Route::get('/', function () {
    return view('vue');
});

Route::prefix('api')->group(function () {

    Route::get('/routes', [RouteController::class, 'getRoutes']);

    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);

    Route::post('/routes/filter', [FilterController::class, 'index'])->name('routes.filter');
   


    Route::post('/routes/{slug}/points', [RouteController::class, 'addPoint']);
});

    Route::post('/routes/filter', [FilterController::class, 'index'])->name('routes.filter');
