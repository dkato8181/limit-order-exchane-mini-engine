<?php

namespace App\Http\Controllers;

use App\Events\OrderMatched;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\OrderStatus;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol');
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $orders = Order::query()
                    ->when($symbol, function ($query) use ($symbol) {
                        $query->where('symbol', $symbol);
                        })
                    ->when($status, function ($query) use ($status) {
                        $query->where('status', $status);
                    })
                    ->when($userId, function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ], 200);
    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $orderData = array_merge($request->validated(), ['user_id' => $request->user()->id]);

        try {
            $orderService->canPlaceOrder($orderData);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        $order = $orderService->createOrder($orderData);

        if(!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => new OrderResource($order),
        ], 201);
    }

    public function cancel(Request $request, $id, OrderService $orderService)
    {
        $order = Order::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

        if(is_null($order)) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if($order->status->value !== OrderStatus::OPEN->value) {
            return response()->json([
                'success' => false,
                'message' => 'Only open orders can be cancelled',
            ], 403);
        }

        $orderCancelled = $orderService->cancelOrder($order);

        if(!$orderCancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
        ]);
    }

    public function availableAssets()
    {
        $assets = Asset::query()
            ->distinct()
            ->pluck('symbol');

        return response()->json([
            'assets' => $assets
        ]);
    }

    public function broadcast($id)
    {
        $trade = Trade::find($id)->load(['buyOrder.user.assets', 'sellOrder.user.assets']);
        OrderMatched::dispatch($trade);

        return response()->json([
            'message' => 'Trade broadcasted successfully',
        ]);
    }
}
