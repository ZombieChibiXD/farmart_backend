<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request){
        $fields = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
        ]);
        $user = User::create([
            'firstname' => $fields['firstname'],
            'lastname' => $fields['lastname'],
            'email' => $fields['email'],
            'username' => $fields['username'],
            'password' => bcrypt($fields['password']),
            'role' => Role::MEMBER,
        ]);
        $token = $user->createToken('myapptoken')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    public function login(Request $request){
        $fields = $request->validate([
            'user' => 'required|string',
            'password' => 'required|string',
        ]);
        $field = filter_var($fields['user'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $fields['user'])->first();
        if(!$user){
            return response([
                'message' => 'Username/Email is not found!'
            ], 401);
        }
        else if (!Hash::check($fields['password'], $user->password)){
            return response([
                'message' => 'Bad password'
            ], 401);
        }
        $token = $user->createToken('myapptoken')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    public function logout(Request $request){
        $user = request()->user();
        $user->currentAccessToken()->delete();
        // $user->tokens()->delete();
        $response = [
            'user' => $user,
            'message' => 'Account Logged out'
        ];
        return response($response, 201);
    }
    // Check if username or email is already taken
    public function check(Request $request){
        $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
        ]);

        return response([
            'message' => 'Username/Email is available!'
        ], 201);
    }
}
