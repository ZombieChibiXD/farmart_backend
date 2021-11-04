<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $store = new Store();
        $store->user_id = 1;
        $store->name = 'John\'s Store';
        $store->storename = 'johnstore';
        $store->description = 'This is john store';
        $store->location = 'Jawa Timur';
        $store->address = 'Jalan merdeka';
        $store->coordinate = '1.231312GA, 123.3123WS';
        $store->save();
        $store->handlers()->attach(1);
    }
}
