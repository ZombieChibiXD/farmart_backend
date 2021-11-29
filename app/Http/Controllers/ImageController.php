<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function add_user_profile(Request $request)
    {
        $fields = $request->validate([
            'type'=>'required|string|in:image,url',
            'content'=>'required',
        ]);
        $fields = $request->validate([
            'type'=>'required|string|in:image,url',
            'content'=>'required|' . $fields['type'],
        ]);
        $data = [
            'location' => null,
            'local' => null,
            'active' => true,
        ];
        if($fields['type'] == 'image'){
            $data['location'] = $fields['content']->store('images/user/profiles', 'public');
            $data['local'] = true;
        }
        else if($fields['type'] == 'url'){
            $data['location'] = $fields['content'];
            $data['local'] = false;
        }
        else return response(['message'=>'Fatal error'], 500);

        $user = $request->user();
        if($user->image){
            $user->image->active = false;
            $user->image->save();
        }
        $imageStorage = Image::create($data);
        $user->image_id = $imageStorage->id;
        $user->save();
        $user = $request->user();
        return ['user'=>$user, 'image'=>$imageStorage];
    }
}
