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
        $search = request()->search ?? '';
        $stores = Store::where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('storename', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('url', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        })
            ->when(request()->has('selected_date'), function ($query) {
                $query->whereBetween('created_at', [
                    request()->selected_date . ' 00:00:00',
                    request()->selected_date . ' 23:59:59'
                ]);
            })
            ->get();
        return response()->json($stores);
    }

    /**
     * Display a listing of the store user manage.
     *
     * @return \Illuminate\Http\Response
     */
    public function managed()
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
            'storename' => 'required|string|alpha_dash|unique:stores,storename',
            'description' => 'required|string',
            'location' => 'required|string',
            'address' => 'required|string',
            'coordinate' => 'required|string',
            'email' => 'string|email',
            'url' => 'string|url',
            'telephone' => 'string'
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
            'coordinate' => $fields['coordinate'],
            'email' => $fields['email'] ?? '',
            'url' => $fields['url'] ?? '',
            'telephone' => $fields['telephone'] ?? '',
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
    public function update(Request $request, $store_id)
    {
        $requirement = [
            'name' => 'required|string',
            'storename' => 'required|string|alpha_dash|unique:stores,storename',
            'description' => 'required|string',
            'location' => 'required|string',
            'address' => 'required|string',
            'coordinate' => 'required|string',
            'email' => 'required|string|email',
            'url' => 'required|string|url',
            'telp' => 'required|string'
        ];

        $fields = KeyValueRequest::requirements($request, $requirement);
        return KeyValueRequest::updateModelWithResponse(Store::class, $store_id, $fields, function (Store $store) {
            return response()->json($store, 200);
        });
    }
}
