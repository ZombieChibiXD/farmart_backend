<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

use function PHPSTORM_META\type;

class ImageController extends Controller
{
    public static function imageValidation(Request $request, string $storage, string $field = 'image'){
        $fields = $request->validate([
            'type' => 'required|string|in:image,url'
        ]);
        $data = [];

        if ($fields['type'] == 'image') {
            if($request->hasFile('image')){
                $files = $request->file($field);
                if(gettype($files) != 'array')  $files = [$files];
                foreach($files as $file) $data []= [
                    'location' => $file->store($storage, 'public'), 'local'=>true
                ];
            }
        } else if ($fields['type'] == 'url') {
            $fields = $request->validate([
                'type' => 'required|string|in:image,url',
                'urls' => 'required|url',
            ]);
            $urls = explode('\n',$fields['content']);
            foreach ($urls as $url) $data []= ['location' => $url, 'local'=>false];
        } else return response(['message' => 'Fatal error'], 500);
        return $data;
    }
    public function user_profile(Request $request)
    {
        $user = $request->user();
        if ($user->image) {
            $user->image->active = false;
            $user->image->save();
        }
        $data = self::imageValidation($request,'images/user/profiles')[0];
        $imageStorage = Image::create($data);
        $user->image_id = $imageStorage->id;
        $user->save();
        return ['user' => $user, 'image' => $imageStorage];
    }

    public function store_profile(Request $request)
    {
        $store_id = $request->route('store_id');
        $store = Store::find($store_id);
        if ($store->image) {
            $store->image->active = false;
            $store->image->save();
        }
        $data = self::imageValidation($request,'images/stores/' . $store_id)[0];
        $imageStorage = Image::create($data);
        $store->image_id = $imageStorage->id;
        $store->save();
        return ['store' => $store, 'image' => $imageStorage];
    }

    public function add_product_image(Request $request){
        $store_id = $request->route('store_id');
        $product_id = $request->route('product_id');
        $data = self::imageValidation(
            $request, "images/stores/" . $store_id . "/product/". $product_id
        );
        $imagesStorages = [];
        $product = Product::find($product_id);
        foreach ($data as $datum){
            $imagesStorage = Image::create($datum);
            $imagesStorages []= $imagesStorage;
            $product->images()->attach($imagesStorage->id);
        }
        return ['images'=>$imagesStorages,'product'=>$product];
    }

    public function remove_product_image(Request $request){
        $product_id = $request->route('product_id');
        $image_id = $request->route('image_id');
        $product = Product::find($product_id);
        if($product->images->contains($image_id)){
            $product->images()->detach($image_id);
            Image::destroy($image_id);
            unset($product->images);
            unset($product->product_images);
        }
        return $product;
    }
}
