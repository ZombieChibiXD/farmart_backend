<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'flag' => 0,
                'name' => 'RESTRICTED',
            ],
            [
                'flag' => 2**0,
                'name' => 'MEMBER',
            ],
            [
                'flag' => 2**1,
                'name' => 'SELLER',
            ],
            [
                'flag' => 2**2,
                'name' => 'SUPERVISOR',
            ],
            [
                'flag' => 2**3,
                'name' => 'ADMINISTRATOR',
            ],
        ];
        Role::insert($roles);
    }
}
