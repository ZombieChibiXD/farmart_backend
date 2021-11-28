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
        if(!request()->user()->stores->contains($store_id)){
            return response([
                'message' => 'Store does not exist, or you have insufficient permission to modify the store!'
            ], 403);
        }

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
    public function update(Request $request, int $store_id, int $id)
    {
        if(!request()->user()->stores->contains($store_id)){
            return response([
                'message' => 'Store does not exist, or you have insufficient permission to modify the store!'
            ], 403);
        }
        if(!in_array($id, Store::find($store_id)->products->pluck('id')->all())){
            return response([
                'message' => 'Product does not exist, or you have insufficient permission to modify the product!'
            ], 403);
        }

        $requirement = [
            'name' => 'required|string',
            'slug' => 'required|string|unique:products,slug,' . $id,
            'price' => 'required|numeric'
        ];

        $fields = KeyValueRequest::requirements($request, $requirement);
        return KeyValueRequest::updateModel(Product::class, $id, $fields);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($store_id, $id)
    {
        if(!request()->user()->stores->contains($store_id)){
            return response([
                'message' => 'Store does not exist, or you have insufficient permission to modify the store!'
            ], 403);
        }
        if(!in_array($id, Store::find($store_id)->products->pluck('id')->all())){
            return response([
                'message' => 'Product does not exist, or you have insufficient permission to modify the product!'
            ], 403);
        }
        $product = Product::find($id);
        if(Product::destroy($id)>0){
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
