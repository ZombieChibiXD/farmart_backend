<?php

namespace App\Http\Controllers;

use App\Http\Requests\KeyValueRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all products from the database
        // where labeled is not unlisted or whereNull labeled
        // likes fullname or shortname by the request search if it exists else get all
        // filter by price from request if it exist and is a number
        // filter by price to request if exist and is a number
        // order by order request if it exist
        // paginate per page 20, and paging by page request if it exist


        $products = Product::where('labeled', '!=', 'unlisted')
            ->orWhereNull('labeled')
            ->where(function ($query) {
                $query->where('fullname', 'like', '%' . request()->search . '%')
                    ->orWhere('shortname', 'like', '%' . request()->search . '%');
            })
            ->when(request()->has('price_from') && is_numeric(request()->price_from), function ($query) {
                $query->where('price', '>=', request()->price_from);
            })
            ->when(request()->has('price_to') && is_numeric(request()->price_to), function ($query) {
                $query->where('price', '<=', request()->price_to);
            })
            ->when(request()->has('order'), function ($query) {
                $query->orderBy(request()->order);
            })
            ->get();


        // Filter product by store location if store location is simmilar to request location array
        // if request location is not empty
        if (request()->has('location') && !empty(request()->location)) {
            $products = $products->filter(function ($product) {
                return in_array($product->store->location, request()->location);
            });
        }

        // Filter products by category if request category array exist
        if (request('types')) {
            $products = $products->filter(function ($product) {
                foreach ($product->type as $value) {
                    if (in_array($value, request('types'))) {
                        return true;
                    }
                }
            });
        }

        // return products as json
        return response()->json($products);

    }


    /**
     * List all products belongs to this store
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ownned_products(Request $request)
    {
        $store_id = $request->route('store_id');
        // Get all products where store_id is equal to the store_id in the route only
        $products = Product::where('store_id', $store_id)->get();
        // return products as json
        return response()->json($products);
    }

    /**
     * Return a JSON list of products locations
     * @return \Illuminate\Http\JsonResponse
     */
    public function locations()
    {
        // Get all products names with distinct store ids
        $products = Product::select('store_id')->distinct()->get();

        // Get all location from products
        $locations = $products->map(function ($product) {
            return $product->location;
        });


        return response()->json($locations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $store_id = $request->route('store_id');
        $fields = $request->validate(Product::FIELDS);
        // $data = ['store_id' => $store_id, ...$fields];
        $product = Product::create(array_merge(['store_id' => $store_id], $fields));
        return response($product, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $id = request()->route('product_id');
        $product = Product::find($id);
        if (!$product) {
            return response([
                'message' => 'Product does not exist!'
            ], 401);
        }
        return response($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        $product_id = $request->route('product_id');

        $fields = KeyValueRequest::requirements($request, Product::FIELDS);

        if ($fields['value'] == '' || $fields['value'] == ' ') {
            $fields['value'] = null;
        }
        return KeyValueRequest::updateModel(Product::class, $product_id, $fields);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $product_id = $request->route('product_id');
        $product = Product::find($product_id);
        if (Product::destroy($product_id) > 0) {
            return response([
                'product' => $product,
                'message' => 'Product have been removed!'
            ], 200);
        }
        return response([
            'message' => 'An unknown error has occured!!'
        ], 501);
    }
}
