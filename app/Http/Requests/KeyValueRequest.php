<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class KeyValueRequest
{
    const ITEM_NOT_FOUND = -1;
    const ITEM_NOT_UPDATED = 0;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public static function requirements(Request $request, array $requirement)
    {
        $name = $request->validate([
            'name' => 'required|string|in:' . implode(",",array_keys($requirement)),
            'value' => ''
        ]);
        return $request->validate([
            'name' => 'required|string',
            'value' => $requirement[$name['name']]
        ]);
    }

    public static function updateModel(string $classStr, int $id, array $fields){
        $class = $classStr::find($id);
        if (!$class) {
            return self::ITEM_NOT_FOUND;
        }

        $class[$fields['name']] = $fields['value'];
        if ($class->save())
            return $class;
        return self::ITEM_NOT_UPDATED;
    }

    public static function updateModelWithResponse(string $classStr, int $id, array $fields, Closure $callback){
        $class = self::updateModel($classStr, $id, $fields);
        if ($class == self::ITEM_NOT_FOUND) {
            return response([
                'message' => 'Item not found!'
            ], 401);
        }
        if ($class == self::ITEM_NOT_UPDATED) {
            return response([
                'message' => 'An unknown error has occured!!'
            ], 501);
        }
        return $callback($class);
    }
}
