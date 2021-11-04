<?php

namespace Database\Seeders;

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
        $user->role = 4;
        $user->username = 'johndoe_';
        $user->password = Hash::make('johndoe');
        $user->save();
    }
}
