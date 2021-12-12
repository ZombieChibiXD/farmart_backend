<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsrfController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TesterController;
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
Route::get('/csrf',       [CsrfController::class, 'index']);

//Check if username or email is already taken
Route::post('/check-username-or-email-exists',       [AuthController::class, 'check']);



#region Generic user routes
Route::group(['middleware' => ['auth:sanctum']], function(){
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/check_is_logged_in', function(){
        return 'You are logged in!';
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/user/photo', [ImageController::class, 'user_profile']);
    Route::middleware('role:' . Role::RESTRICTED)->get('/restricted', function(){
        return ['message'=>'You are restricted!'];
    });
    Route::middleware('role:' . (Role::MEMBER|Role::SELLER))->get('/member_seller', function(){
        return ['message'=>'You are seller and member!'];
    });
});
#endregion

#region Store
// Get store by ID and gets the general data about the store
Route::get('/store/{id}', [StoreController::class, 'show']);
Route::group(['middleware' => ['auth:sanctum']], function(){
    // List stores that user can handle
    Route::get('/stores', [StoreController::class, 'index']);
    // Creates a new store for user
    Route::post('/stores', [StoreController::class, 'store']);
    Route::group([
        'prefix' => 'store/{store_id}',
        'middleware' => ['store_manage']
    ], function(){
        Route::put('/', [StoreController::class, 'update']);
        Route::post('/photo', [ImageController::class, 'store_profile']);

    });

});
#endregion

#region Products
// List all products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/locations', [ProductController::class, 'locations']);
Route::get('/product/{product_id}', [ProductController::class, 'show']);

Route::group([
    'prefix' => 'store/{store_id}',
    'middleware' => ['auth:sanctum', 'store_manage'],
], function(){
    Route::get  ('/products', [ProductController::class, 'ownned_products']);
    // Create a new product
    Route::post  ('/products', [ProductController::class, 'store']);
    Route::group([
        'prefix' => 'product/{product_id}',
        'middleware' => ['store_product_manage']
    ], function(){
        Route::get  ('/', [ProductController::class, 'show']);
        Route::put   ('/', [ProductController::class, 'update']);
        Route::delete('/', [ProductController::class, 'destroy']);

        Route::post ('/images', [ImageController::class, 'add_product_image']);
        Route::delete('/image/{image_id}', [ImageController::class, 'remove_product_image']);
    });
});
#endregion

Route::get('/hello-world', function (){
    return 'Hello world!';
});
Route::get('/tester', [TesterController::class, 'tester']);
