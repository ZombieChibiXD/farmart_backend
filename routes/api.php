<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CsrfController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TesterController;
use App\Http\Controllers\UserController;
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

//Check if username or email is already taken
Route::post('/check-username-or-email-exists',       [AuthController::class, 'check']);



#region User General
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/user/photo', [ImageController::class, 'user_profile']);
    Route::post('/user/verification', [ImageController::class, 'verify_profile']);
    Route::middleware('role:' . Role::RESTRICTED)->get('/restricted', function () {
        return ['message' => 'You are restricted!'];
    });
});
#endregion

#region Store
// Get store by ID and gets the general data about the store
Route::get('/store/{id}', [StoreController::class, 'show']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    // List stores that user can handle
    Route::get('/stores', [StoreController::class, 'managed']);
    // Creates a new store for user
    Route::post('/stores', [StoreController::class, 'store']);

    Route::group([
        'prefix' => 'store/{store_id}',
        'middleware' => ['store_manage']
    ], function () {
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
// Get product reviews overview
Route::get('/product/{product_id}/reviews', [ProductController::class, 'reviews']);
// Get product limited by 5 reviews
Route::get('/product/{product_id}/reviews/compact', [ProductController::class, 'reviews_limited']);


// Route Group auth for products
Route::group(['middleware' => ['auth:sanctum']], function () {
    // Toggle Like product route
    Route::post('/product/{product_id}/like', [ProductController::class, 'like']);
    // User liked products
    Route::get('/products/likes', [ProductController::class, 'likes']);
    // Review product
    Route::post('/products/review', [ProductController::class, 'review']);
});


Route::group([
    'prefix' => 'store/{store_id}',
    'middleware' => ['auth:sanctum', 'store_manage'],
], function () {
    Route::get('/products', [ProductController::class, 'owned_products']);
    // Create a new product
    Route::post('/products', [ProductController::class, 'store']);

    Route::group([
        'prefix' => 'product/{product_id}',
        'middleware' => ['store_product_manage']
    ], function () {
        Route::get('/', [ProductController::class, 'show']);
        Route::put('/', [ProductController::class, 'update']);
        Route::delete('/', [ProductController::class, 'destroy']);

        Route::post('/images', [ImageController::class, 'add_product_image']);
        Route::delete('/image/{image_id}', [ImageController::class, 'remove_product_image']);
    });

    // Expense route for store
    Route::group([
        'prefix' => 'expenses',
    ], function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::post('/', [ExpenseController::class, 'store']);
        // Route::get  ('/{expense_id}', [ExpenseController::class, 'show']);
        // Route::put   ('/{expense_id}', [ExpenseController::class, 'update']);
        // Route::delete('/{expense_id}', [ExpenseController::class, 'destroy']);
    });
});
#endregion Product

#region Expenses
Route::group([
    'prefix' => 'store/{store_id}',
    'middleware' => ['auth:sanctum', 'store_manage'],
], function () {
    // Expense route for store
    Route::group([
        'prefix' => 'expenses',
    ], function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::post('/', [ExpenseController::class, 'store']);
        // Route::get  ('/{expense_id}', [ExpenseController::class, 'show']);
        // Route::put   ('/{expense_id}', [ExpenseController::class, 'update']);
        // Route::delete('/{expense_id}', [ExpenseController::class, 'destroy']);
    });
});
#endregion Expenses

#region Chat
// Route prefix 'chatrooms'
Route::group([
    'prefix' => 'chatrooms',
    'middleware' => ['auth:sanctum']
], function () {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/', [ChatController::class, 'store']);

});


// Route prefix 'chatrooms'
Route::group([
    'prefix' => 'chatroom',
    'middleware' => ['auth:sanctum']
], function () {
    Route::get('/', [ChatController::class, 'initial_messages']);
    Route::get('/refresh', [ChatController::class, 'messages']);
    Route::post('/', [ChatController::class, 'send_message']);
});
#endregion Chat


#region Cart
// Route prefix 'cart'
Route::group([
    'prefix' => 'cart',
    'middleware' => ['auth:sanctum']
], function () {
    // Get all cart items
    Route::get('/', [CartController::class, 'index']);
    // Add item to cart
    Route::post('/', [CartController::class, 'store']);
    // Update cart item
    Route::post('/checkout', [CartController::class, 'checkout']);
    // Delete cart item
    Route::delete('/{item_id}', [CartController::class, 'destroy']);
});
#endregion Cart

#region Order

// Route prefix 'orders'
Route::group([
    'prefix' => 'orders',
    'middleware' => ['auth:sanctum']
], function () {
    // Get all cart items
    Route::get('/', [OrderController::class, 'index']);
    // Add item to cart
    Route::post('/', [OrderController::class, 'store']);

    // Get all cart items
    Route::get('/store/{store_id}', [OrderController::class, 'store_list'])->middleware('store_manage');

    // Route middleware for admin
    Route::group([
        'middleware' => ['role:' . Role::ADMINISTRATOR],
    ], function () {
        Route::get('/admin', [OrderController::class, 'admin_list']);
    });
});


Route::group([
    'prefix' => 'order',
    'middleware' => ['auth:sanctum']
], function () {
    // Get all cart items
    Route::get('/{order_id}', [OrderController::class, 'show']);
    Route::post('/cancel', [OrderController::class, 'cancel']);

    Route::group([
        'middleware' => ['role:' . Role::ADMINISTRATOR]
    ], function (){
        Route::post('/pay', [OrderController::class, 'pay']);
        Route::post('/deliver', [OrderController::class, 'deliver']);
    });

    Route::group([
        'middleware' => ['role:' . Role::SELLER]
    ], function (){
        Route::post('/ship/seller', [OrderController::class, 'ship']);
        Route::post('/cancel/seller', [OrderController::class, 'cancel_seller']);
    });
});


#endregion Order


#region Admin
// Route prefix 'admin'
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth:sanctum', 'role:' . Role::ADMINISTRATOR]
], function () {
    // Get all users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/verification', [UserController::class, 'verify']);
    // Get all stores
    Route::get('/stores', [StoreController::class, 'index']);



});

#endregion Admin