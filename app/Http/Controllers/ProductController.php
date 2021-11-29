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
        return response(Product::all());
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
    public function show($id)
    {
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
