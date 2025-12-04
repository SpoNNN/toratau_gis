<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ScoreController;

Route::get('/', function () {
    return view('vue');
});

Route::prefix('api')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/routes/{id}/reviews', [ScoreController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/routes/{id}/reviews', [ScoreController::class, 'store']);
    });
    Route::get('/routes', [RouteController::class, 'getRoutes']);
    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);
    Route::post('/routes/filter', [FilterController::class, 'index']);


    Route::get('/score/{route_id}', [ScoreController::class, 'index']);


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/routes/{routeId}/reviews', [ScoreController::class, 'store']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/routes/{id}/points', [PointController::class, 'addPoint']);


        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);
            Route::post('/', [FavoriteController::class, 'store']);
            Route::delete('/{routeId}', [FavoriteController::class, 'destroy']);
        });


        Route::post('/score', [ScoreController::class, 'store']);
        Route::delete('/score/{id}', [ScoreController::class, 'destroy']);
    });
});
Route::get('/routes/{routeId}/reviews', [ScoreController::class, 'index']);

Route::get('/profile', function () {
    return view('vue');
});
