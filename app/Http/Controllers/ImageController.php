<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Store;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function user_profile(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|string|in:image,url',
            'content' => 'required',
        ]);
        $fields = $request->validate([
            'type' => 'required|string|in:image,url',
            'content' => 'required|' . $fields['type'],
        ]);
        $data = ['location' => null, 'local' => null, 'active' => true];
        if ($fields['type'] == 'image') {
            $data['location'] = $fields['content']->store('images/user/profiles', 'public');
            $data['local'] = true;
        } else if ($fields['type'] == 'url') {
            $data['location'] = $fields['content'];
            $data['local'] = false;
        } else return response(['message' => 'Fatal error'], 500);

        $user = $request->user();
        if ($user->image) {
            $user->image->active = false;
            $user->image->save();
        }
        $imageStorage = Image::create($data);
        $user->image_id = $imageStorage->id;
        $user->save();
        return ['user' => $user, 'image' => $imageStorage];
    }

    public function store_profile(Request $request, int $store_id)
    {
        $fields = $request->validate([
            'type' => 'required|string|in:image,url',
            'content' => 'required',
        ]);
        $fields = $request->validate([
            'type' => 'required|string|in:image,url',
            'content' => 'required|' . $fields['type'],
        ]);
        $data = ['location' => null, 'local' => null, 'active' => true];
        if ($fields['type'] == 'image') {
            $data['location'] = $fields['content']->store('images/user/profiles', 'public');
            $data['local'] = true;
        } else if ($fields['type'] == 'url') {
            $data['location'] = $fields['content'];
            $data['local'] = false;
        } else return response(['message' => 'Fatal error'], 500);

        $store = Store::find($store_id);
        if ($store->image) {
            $store->image->active = false;
            $store->image->save();
        }
        $imageStorage = Image::create($data);
        $store->image_id = $imageStorage->id;
        $store->save();
        return ['store' => $store, 'image' => $imageStorage];
    }
}
