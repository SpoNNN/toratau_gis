<?php
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\PointController;
Route::get('/', function () {
    return view('vue');
});

Route::prefix('api')->group(function () {

    Route::get('/routes', [RouteController::class, 'getRoutes']);

    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);

    Route::post('/routes/filter', [FilterController::class, 'index'])->name('routes.filter');



Route::post('/routes/{id}/points', [PointController::class, 'addPoint']);
});

Route::post('/routes/filter', [FilterController::class, 'index'])->name('routes.filter');

