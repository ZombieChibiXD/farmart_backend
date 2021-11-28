<?php

namespace App\Http\Controllers;

use App\Http\Requests\KeyValueRequest;
use App\Models\Product;
use App\Models\Store;
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
    public function store(Request $request, int $store_id)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:products,slug',
            'price' => 'required|numeric'
        ]);
        $product = Product::create([
            'name' => $fields['name'],
            'slug' => $fields['slug'],
            'store_id' => $store_id,
            'price' => $fields['price']
        ]);
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
        if(!$product){
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
    public function update(Request $request, int $product_id)
    {
        $requirement = [
            'name' => 'required|string',
            'slug' => 'required|string|unique:products,slug,' . $product_id,
            'price' => 'required|numeric'
        ];

        $fields = KeyValueRequest::requirements($request, $requirement);
        return KeyValueRequest::updateModel(Product::class, $product_id, $fields);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($product_id)
    {
        $product = Product::find($product_id);
        if(Product::destroy($product_id)>0){
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
