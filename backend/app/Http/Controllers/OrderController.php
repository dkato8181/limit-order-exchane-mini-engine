<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol');
        $order = Order::query()
                    ->when($symbol, function ($query) use ($symbol) {
                        $query->where('symbol', $symbol);
                    })
                    ->where('status', 'open')
                    ->get();

        return response()->json([
            'orders' => $order,
        ]);
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'symbol' => 'required|string',
            'type' => 'required|in:buy,sell',
            'amount' => 'required|numeric',
            'price' => 'required|numeric|min:0.0001',
        ]);

        $order = null;

        if($request->side === 'buy') {

            $totalCost = $request->amount * $request->price;
            if (auth()->user()->balance < $totalCost) {
                return response()->json([
                    'message' => 'Insufficient balance to place buy order',
                ], 400);
            }
            $newBalance = auth()->user()->balance - $totalCost;
            DB::transaction(function () use ($newBalance, $request, $order) {
            auth()->user()->update(['balance' => $newBalance]);

            $order = Order::create([
                        'user_id' => auth()->id(),
                        'symbol' => $request->symbol,
                        'type' => $request->type,
                        'amount' => $request->amount,
                        'price' => $request->price,
                        'status' => OrderStatus::OPEN->value,
                    ]);
            });
        }
        else {
            DB::transaction(function () use ($request) {
                $asset = auth()->user()->assets()->where('symbol', $request->symbol)->first();
                if (!$asset || $asset->amount < $request->amount) {
                    return response()->json([
                        'message' => 'Insufficient asset amount to place sell order',
                    ], 400);
                }
                $asset->locked_amount = $request->amount;
                $asset->save();

                $order = Order::create([
                    'user_id' => auth()->id(),
                    'symbol' => $request->symbol,
                    'type' => $request->type,
                    'amount' => $request->amount,
                    'price' => $request->price,
                    'status' => OrderStatus::OPEN->value,
                ]);
            });
        }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
        ], 201);
    }

    public function cancel(Order $order)
    {
        if($order->status !== OrderStatus::OPEN) {
            return response()->json([
                'message' => 'Only open orders can be cancelled',
            ], 400);
        }
        if($order->side === 'buy') {

            $totalCost = $order->amount * $order->price;
            $newBalance = auth()->user()->balance + $totalCost;
            DB::transaction(function () use ($newBalance,$order) {
            auth()->user()->update(['balance' => $newBalance]);
            $order->status = OrderStatus::CANCELLED;
            $order->save();
            });
        }
        else{
            DB::transaction(function () use ($order) {
                $asset = auth()->user()->assets()->where('symbol', $order->symbol)->first();
                if ($asset) {
                    $asset->locked_amount -= $order->amount;
                    $asset->save();
                }
                $order->status = OrderStatus::CANCELLED;
                $order->save();
            });
        }
        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order,
        ]);
    }
}
