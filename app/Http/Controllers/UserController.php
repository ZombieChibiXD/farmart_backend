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
        $search = request()->search ?? '';
        $users = User::where('id', '!=', auth()->user()->id)
            ->where(function ($query) use ($search) {
                $query->where('firstname', 'like', '%' . $search . '%')
                ->orWhere('lastname', 'like', '%' . $search . '%')
                ->orWhere('username', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
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
     * Toggle the verified status of the user
     */
    public function verify(Request $request)
    {
        // Verify fields id user_id
        $fields = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Get user by id
        $user = User::find($fields['user_id']);

        // Toggle verified status
        if($user->verification){
            $user->verification->verified = !$user->verification->verified;
            $user->verification->save();
        }


        // Return user
        return response()->json($user);

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
        // Get user by id
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Return user, store that user handle and reviews
        return response()->json([
            'user' => $user,
            'store' => $user->store,
            'reviews' => $user->reviews->with('product')->get()
        ]);
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
