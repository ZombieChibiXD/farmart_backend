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
        $promo = Promo::create($request->all());
        return response()->json($promo);
    }

    public function show($id){
        $promo = Promo::find($id);
        return response()->json($promo);
    }

    public function update(Request $request, $id){
        $promo = Promo::find($id);
        $promo->update($request->all());
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
