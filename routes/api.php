<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Get store by ID and gets the general data about the store
Route::get('/store/{id}', [StoreController::class, 'show']);

// Routes that you can access after logging in.
Route::group(['middleware' => ['auth:sanctum']], function(){
    Route::post('/logout', [AuthController::class, 'logout']);

    // List stores that user can handle
    Route::get('/stores', [StoreController::class, 'index']);
    // Creates a new store for user
    Route::post('/stores', [StoreController::class, 'store']);
    // Create a new product
    Route::post('/products', [ProductController::class, 'store']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);


    Route::get('/check_is_logged_in', function(){
        return 'You are logged in!';
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/hello-world', function (){
    return 'Hello world!';
});