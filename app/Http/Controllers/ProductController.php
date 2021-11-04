<?php

namespace App\Http\Controllers;

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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = request()->user();

        $fields = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:products,slug',
            'store_id' => 'required|numeric',
            'price' => 'required|numeric'
        ]);
        $exist = $user->stores->contains($fields['store_id']);
        if(!$exist){
            return response([
                'message' => 'Store does not exist, or you have insufficient permission to modify the store!'
            ], 401);
        }
        $product = Product::create([
            'name' => $fields['name'],
            'slug' => $fields['slug'],
            'store_id' => $fields['store_id'],
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = request()->user();
        $product = Product::find($id);
        if(!$product){
            return response([
                'message' => 'Product does not exist!'
            ], 401);
        }
        $exist = $user->stores->contains($product->store->id);
        if(!$exist){
            return response([
                'message' => 'You do not have the permission to modify this product!'
            ], 401);
        }
        if(Product::destroy($id)>0){
            return response([
                'message' => 'Product have been removed!'
            ], 201);
        }
        return response([
            'message' => 'An unknown error has occured!!'
        ], 501);

    }
}
