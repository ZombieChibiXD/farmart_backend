<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class KeyValueRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public static function requirements(Request $request, array $requirement)
    {
        $name = $request->validate([
            'name' => 'required|string|in:' . implode(",",array_keys($requirement)),
            'value' => 'required'
        ]);
        return $request->validate([
            'name' => 'required|string',
            'value' => $requirement[$name['name']]
        ]);
    }

    public static function updateModel(string $classStr, int $id, array $fields){
        $class = $classStr::find($id);
        if (!$class) {
            return response([
                'message' => 'Item does not exist!'
            ], 401);
        }

        $class[$fields['name']] = $fields['value'];
        if ($class->save())
            return response($class);
        return response(['message' => 'Failure'], 500);
    }
}
