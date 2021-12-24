<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(){
        $promos = Promo::all();

        return response()->json($promos);
    }

    public function store(Request $request){
        $fields = $request->validate([
            'name' => 'required',
            'type' => 'required',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'seasons' => 'nullable',
            'value' => 'required',
            'visible' => 'boolean',
            'usable' => 'boolean',
            'code' => 'nullable'
        ]);
        $promo = Promo::create($fields);
        return response()->json($promo);
    }

    public function show($id){
        $promo = Promo::find($id);
        return response()->json($promo);
    }

    public function update(Request $request, $id){
        $promo = Promo::find($id);
        // Validate request
        // 'name', 'type', 'start_date', 'end_date', 'seasons', 'value', 'visible', 'usable', 'code'
        $fields = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'start_date' => 'date',
            'end_date' => 'date',
            'seasons' => 'required|string',
            'value' => 'required|string',
            // 'visible' => 'boolean',
            // 'usable' => 'boolean',
            'code' => 'string|unique:promos,code'
        ]);
        // $fields["visible"] = $fields["visible"] ?? false;
        // $fields["usable"] = $fields["usable"] ?? false;
        $promo->update($fields);
        return response()->json($promo);
    }

    public function destroy($id){
        $promo = Promo::find($id);
        $promo->delete();
        return response()->json(null, 204);
    }

    public function getPromoByCode(Request $request){
        $promo = Promo::where('code', $request->code)->first();
        return response()->json($promo);
    }



}
