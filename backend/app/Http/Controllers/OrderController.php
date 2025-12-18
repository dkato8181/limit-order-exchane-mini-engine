<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\OrderStatus;
use App\Services\OrderService;
use Illuminate\Http\Request;

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

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        try {
            $orderService->canPlaceOrder($request->user(), $request->all());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        $order = $orderService->createOrder($request->user(), $request->all());

        if(!$order) {
            return response()->json([
                'message' => 'Failed to place order',
            ], 500);
        }

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order,
        ]);
    }

    public function cancel(Request $request, $id, OrderService $orderService)
    {
        $order = Order::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

        if($order->status->value !== OrderStatus::OPEN->value) {
            return response()->json([
                'message' => 'Only open orders can be cancelled',
            ], 400);
        }

        $orderCancelled = $orderService->cancelOrder($request->user(), $order);

        if(!$orderCancelled) {
            return response()->json([
                'message' => 'Failed to cancel order',
            ], 500);
        }

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order,
        ]);
    }
}
