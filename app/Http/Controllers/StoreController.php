<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = request()->user();
        $response = [
            'stores' => $user->stores
        ];
        return response($response, 201);
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
            'storename' => 'required|string|unique:stores,storename',
            'description' => 'required|string',
            'location' => 'required|string',
            'address' => 'required|string',
            'coordinate' => 'required|string'
        ]);
        $store = Store::create([
            'user_id' => $user->id,
            'name' => $fields['name'],
            'storename' => $fields['storename'],
            'description' => $fields['description'],
            'location' => $fields['location'],
            'address' => $fields['address'],
            'coordinate' => $fields['coordinate']
        ]);
        $store->handlers()->attach($user->id);
        return response($store, 201);
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
        //
    }
}
