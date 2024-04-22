<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $packageFor = $request->package_for;

        $cart =  Cart::with('package')->orderBy('id', 'desc')->where([
            'user_id' => auth()->user()->id,
            'record_type' => $packageFor ? $packageFor : 1,
        ])->get();

        return CartResource::collection($cart);
    }

    public function store(Request $request)
    {
        $request->validate([
            'record_type' => 'required|integer',
            'course_id' => 'required|integer',
            'package_id' => 'required|integer',
        ]);

        $cart = Cart::updateOrCreate(
            [
                'user_id' => auth()->user()->id,
                'record_id' => $request->course_id,
                'record_type' => $request->record_type
            ],
            [
                'user_id' => auth()->user()->id,
                'record_id' => $request->course_id,
                'record_type' => $request->record_type,
                'package_id' => $request->package_id,
            ]
        );

        if (!$cart) {
            return response()->json([
                'status' => false,
                'data' => [],
                'message' => 'Something went wrong'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $cart,
            'message' => 'Package added'
        ]);
    }

    public function destroy(Cart $cart)
    {
        if (!$cart->delete()) {
            return response()->json([
                'status' => false,
                'data' => [],
                'message' => 'Something went wrong'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [],
            'message' => 'Package removed'
        ]);
    }
}
