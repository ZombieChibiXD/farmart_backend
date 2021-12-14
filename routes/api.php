<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CsrfController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImageController;
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
Route::get('/csrf',       [CsrfController::class, 'index']);

//Check if username or email is already taken
Route::post('/check-username-or-email-exists',       [AuthController::class, 'check']);



#region Generic user routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/check_is_logged_in', function () {
        return 'You are logged in!';
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/user/photo', [ImageController::class, 'user_profile']);
    Route::middleware('role:' . Role::RESTRICTED)->get('/restricted', function () {
        return ['message' => 'You are restricted!'];
    });
    Route::middleware('role:' . (Role::MEMBER | Role::SELLER))->get('/member_seller', function () {
        return ['message' => 'You are seller and member!'];
    });
});
#endregion

#region Store
// Get store by ID and gets the general data about the store
Route::get('/store/{id}', [StoreController::class, 'show']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    // List stores that user can handle
    Route::get('/stores', [StoreController::class, 'index']);
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
    Route::get('/products', [ProductController::class, 'ownned_products']);
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
#endregion

Route::get('/hello-world', function () {
    return 'Hello world!';
});
Route::get('/tester', [TesterController::class, 'tester']);


// Route for chatroom with middleware role restricted
Route::group([
    'prefix' => 'chatroom',
    'middleware' => ['auth:sanctum']
], function () {

    // Prefix '{chatroom_id}'
    Route::group(['prefix' => '{chatroom_id}'], function () {
        // Route 'member' to controller messages_member
        Route::get('/member', [ChatController::class, 'messages_member']);
        // Route 'member' post to controller store_member
        Route::post('/member', [ChatController::class, 'store_member']);

        // Route group 'seller' with role seller and prefix seller
        Route::group([
            'prefix' => 'seller',
            'middleware' => ['role:' . Role::SELLER]
        ], function () {
            // Route 'seller' to controller messages_seller
            Route::get('/', [ChatController::class, 'messages_seller']);
            // Route 'seller' post to controller store_seller
            Route::post('/', [ChatController::class, 'store_seller']);
        });

        // Route group 'admin' with role admin and prefix admin
        Route::group([
            'prefix' => 'admin',
            'middleware' => ['role:' . Role::ADMINISTRATOR]
        ], function () {
            // Route 'admin' to controller messages_admin
            Route::get('/', [ChatController::class, 'messages_admin']);
            // Route 'admin' post to controller store_admin
            Route::post('/', [ChatController::class, 'store_admin']);
        });
    });
});

// Route prefix 'chatrooms'
Route::group([
    'prefix' => 'chatrooms',
    'middleware' => ['auth:sanctum']
], function () {
    // Get all chatrooms for member
    Route::get('/member', [ChatController::class, 'chatrooms_member']);
    // Create member to store chatroom
    Route::post('/member/{store_id}', [ChatController::class, 'create_member_to_seller']);

    // Get all chatrooms for member
    Route::get('/seller', [ChatController::class, 'chatrooms_seller'])->middleware('role:' . Role::SELLER);

    // Route Group by ADMINISTRATOR role and prefix 'admin'
    Route::group([
        'prefix' => 'admin',
        'middleware' => ['role:' . Role::ADMINISTRATOR]
    ], function () {
        // Get all chatrooms for admin
        Route::get('/', [ChatController::class, 'chatrooms_admin']);
        // Create admin to member chatroom
        Route::post('/member/{member_id}', [ChatController::class, 'chat_admin_to_member']);
    });
});


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


// Route prefix 'admin'
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth:sanctum', 'role:' . Role::ADMINISTRATOR]
], function () {
    // Get all users
    Route::get('/users', [UserController::class, 'index']);
});