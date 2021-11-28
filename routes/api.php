<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Models\Role;
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

Route::post('/register',    [AuthController::class, 'register']);
Route::post('/login',       [AuthController::class, 'login']);

#region Generic user routes
Route::group(['middleware' => ['auth:sanctum']], function(){
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/check_is_logged_in', function(){
        return 'You are logged in!';
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::middleware('role:' . Role::RESTRICTED)->get('/check_restricted', function(){
        return ['message'=>'You are restricted!'];
    });
});
#endregion

#region Store
// Get store by ID and gets the general data about the store
Route::get('/store/{id}', [StoreController::class, 'show']);
Route::group(['middleware' => ['auth:sanctum']], function(){
    // List stores that user can handle
    Route::get('/managed-stores', [StoreController::class, 'index']);
    // Creates a new store for user
    Route::post('/stores', [StoreController::class, 'store']);

});
#endregion

#region Products
// List all products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'show']);
Route::group(['middleware' => ['auth:sanctum']], function(){
    // Create a new product
    Route::post  ('/store/{store_id}/products',     [ProductController::class, 'store']);
    Route::delete('/store/{store_id}/product/{id}', [ProductController::class, 'destroy']);
    Route::put   ('/store/{store_id}/product/{id}', [ProductController::class, 'update']);
});
#endregion

Route::get('/hello-world', function (){
    return 'Hello world!';
});
