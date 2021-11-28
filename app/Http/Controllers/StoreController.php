<?php

namespace App\Http\Controllers;

use App\Http\Requests\KeyValueRequest;
use App\Models\Role;
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
        $fields = $request->validate([
            'name' => 'required|string',
            'storename' => 'required|string|unique:stores,storename',
            'description' => 'required|string',
            'location' => 'required|string',
            'address' => 'required|string',
            'coordinate' => 'required|string'
        ]);
        $user = request()->user();
        $user->role |= Role::SELLER;
        $user->save();
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
        $store = Store::find($id);
        if (!$store) {
            return response([
                'message' => 'Store does not exist!'
            ], 401);
        }
        $response = [
            'store' => $store,
            'products' => $store->products
        ];
        return response($response, 201);
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
        $user = request()->user();
        if (!$user->stores->contains($id))
            return response([
                'message' =>
                'Store does not exist, or you have insufficient permission to modify the store!'
            ], 403);
        $requirement = [
            'name' => 'required|string',
            'storename' => 'required|string|unique:stores,storename',
            'description' => 'required|string',
            'location' => 'required|string',
            'address' => 'required|string',
            'coordinate' => 'required|string'
        ];

        $fields = KeyValueRequest::requirements($request, $requirement);
        return KeyValueRequest::updateModel(Store::class, $id, $fields);
    }
}
