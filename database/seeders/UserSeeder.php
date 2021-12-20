<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->firstname = 'John';
        $user->lastname = 'Doe';
        $user->email = 'johndoe@gmail.com';
        $user->role = Role::ADMINISTRATOR | Role::MEMBER;
        $user->username = 'johndoe_';
        $user->password = Hash::make('johndoe');
        $user->save();


        $user = new User();
        $user->firstname = 'Administrator';
        $user->lastname = 'Joe';
        $user->email = 'administrator@gmail.com';
        $user->role = Role::ADMINISTRATOR;
        $user->username = 'administrator';
        $user->password = Hash::make('admin');
        $user->save();


        // $user = new User();
        // $user->firstname = 'John';
        // $user->lastname = 'Doe';
        // $user->email = 'johndoe@gmail.com';
        // $user->role = 4;
        // $user->username = 'johndoe_';
        // $user->password = Hash::make('johndoe');
        // $user->save();
    }
}
