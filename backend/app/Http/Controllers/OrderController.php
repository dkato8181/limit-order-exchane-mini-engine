<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Trade;
use App\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol');
        $orders = Order::query()
                    ->when($symbol, function ($query) use ($symbol) {
                        $query->where('symbol', $symbol);
                    })
                    ->where('status', OrderStatus::OPEN->value)
                    ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'amount' => 'required|numeric',
            'price' => 'required|numeric|min:0.0001',
        ]);

        $order = null;

        if($request->side === 'buy') {
            $totalCost = $request->amount * $request->price;
            if ($request->user()->balance < $totalCost) {
                return response()->json([
                    'message' => 'Insufficient balance to place buy order',
                ], 400);
            }
            $newBalance = $request->user()->balance - $totalCost;
            DB::transaction(function () use ($newBalance, $request, $order) {
                $request->user()->update(['balance' => $newBalance]);
                $order = Order::create([
                        'user_id' => $request->user()->id,
                        'symbol' => $request->symbol,
                        'side' => $request->side,
                        'amount' => $request->amount,
                        'price' => $request->price,
                        'status' => OrderStatus::OPEN->value,
                    ]);
                $this->matchOrders($order);
            });
        }
        else {
            DB::transaction(function () use ($request) {
                $asset = $request->user()->assets()->where('symbol', $request->symbol)->first();
                if (!$asset || $asset->amount < $request->amount) {
                    return response()->json([
                        'message' => 'Insufficient asset amount to place sell order',
                    ], 400);
                }
                $asset->amount -= $request->amount;
                $asset->locked_amount = $request->amount;
                $asset->save();

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'symbol' => $request->symbol,
                    'side' => $request->side,
                    'amount' => $request->amount,
                    'price' => $request->price,
                    'status' => OrderStatus::OPEN->value,
                ]);
                $this->matchOrders($order);
            });
        }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
        ], 201);
    }

    public function cancel(Request $request, $id)
    {
        $order = Order::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

        if($order->status->value !== OrderStatus::OPEN->value) {
            return response()->json([
                'message' => 'Only open orders can be cancelled',
            ], 400);
        }

        if($order->side === 'buy') {
            $totalCost = $order->amount * $order->price;
            $newBalance = $request->user()->balance + $totalCost;
            DB::transaction(function () use ($newBalance,$order,$request) {
                $request->user()->update(['balance' => $newBalance]);
                $order->status = OrderStatus::CANCELLED;
                $order->save();
            });
        }
        else{
            DB::transaction(function () use ($order, $request) {
                $asset = $request->user()->assets()->where('symbol', $order->symbol)->first();
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

    private function matchOrders(Order $newOrder)
    {
        $matchingOrder = Order::query()
                            ->where('symbol', $newOrder->symbol)
                            ->where('status', OrderStatus::OPEN->value)
                            ->where('user_id', '!=', $newOrder->user_id)
                            ->where('amount', '=', $newOrder->amount)
                            ->when($newOrder->side === 'buy', function ($query) use ($newOrder) {
                                $query->where('price', '<=', $newOrder->price);
                                $query->where('side', 'sell');
                                $query->orderBy('price', 'asc');
                            }, function ($query) use ($newOrder) {
                                $query->where('price', '>=', $newOrder->price);
                                $query->where('side', 'buy');
                                $query->orderBy('price', 'desc');
                            })
                            ->get()
                            ->first();
        if (is_null($matchingOrder)) {
            return;
        }
        //broadcast events
        $symbol = $newOrder->symbol;
        if($newOrder->side === 'buy') {
            $tradePrice = min($newOrder->price, $matchingOrder->price);
        }
        else {
            $tradePrice = max($newOrder->price, $matchingOrder->price);
        }

        $trade = Trade::create([
            'buy_order_id' => $newOrder->side === 'buy' ? $newOrder->id : $matchingOrder->id,
            'sell_order_id' => $newOrder->side === 'sell' ? $newOrder->id : $matchingOrder->id,
            'price' => $tradePrice,
            'amount' => $newOrder->amount,
            'commission_rate' => 1.5
        ]);

        $commission = $trade->price * $trade->amount * $trade->commission_rate;
        DB::transaction(function () use ($newOrder, $matchingOrder, $trade, $commission, $symbol) {
            if($newOrder->side ==='buy') {
                $buyerAsset = $newOrder->user()->asset('symbol', $symbol)->first();
                $buyerAsset->amount += $trade->amount;
                $buyerAsset->save();

                $sellerAsset = $matchingOrder->user()->asset('symbol', $symbol)->first();
                $sellerAsset->locked_amount -= $trade->amount;
                $sellerAsset->save();

                $seller = $matchingOrder->user;;
                $sellerProceeds = ($trade->price * $trade->amount) - $commission;
                $seller->balance += $sellerProceeds;
                $seller->save();
            }
            else {
                $buyerAsset = $matchingOrder->user()->asset('symbol', $symbol)->first();
                $buyerAsset->amount += $trade->amount;
                $buyerAsset->save();

                $sellerAsset = $newOrder->user()->asset('symbol', $symbol)->first();
                $sellerAsset->locked_amount -= $trade->amount;
                $sellerAsset->save();

                $seller = $newOrder->user();
                $sellerProceeds = ($trade->price * $trade->amount) - $commission;
                $seller->balance += $sellerProceeds;
                $seller->save();
            }
            $newOrder->status = OrderStatus::FILLED->value;
            $matchingOrder->status = OrderStatus::FILLED->value;
            $newOrder->save();
            $matchingOrder->save();
        });
    }
}
