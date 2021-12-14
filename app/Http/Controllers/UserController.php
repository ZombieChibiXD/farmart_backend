<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all users from the database except the current user
        // Where likes firstname or lastname or email by the request search if it exists else get all
        // where created_at within range of 5 days from request selected_date if it exist
        // return as json response
        $users = User::where('id', '!=', auth()->user()->id)
            ->where(function ($query) {
                $query->where('firstname', 'like', '%' . request()->search . '%')
                    ->orWhere('lastname', 'like', '%' . request()->search . '%')
                    ->orWhere('email', 'like', '%' . request()->search . '%');
            })
            ->when(request()->has('selected_date'), function ($query) {
                $query->whereBetween('created_at', [
                    request()->selected_date . ' 00:00:00',
                    request()->selected_date . ' 23:59:59'
                ]);
            })
            ->get();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
