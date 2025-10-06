<?php
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('vue');
});
// API маршруты
Route::prefix('api')->group(function() {
    // Получить все маршруты
    Route::get('/routes', [RouteController::class, 'getRoutes']);
    
    // Получить конкретный маршрут
    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);
    
    // Добавить точку к маршруту
    Route::post('/routes/{slug}/points', [RouteController::class, 'addPoint']);
});

// Все остальные запросы - на Vue приложение
