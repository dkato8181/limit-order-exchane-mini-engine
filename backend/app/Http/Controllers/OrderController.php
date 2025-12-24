<?php

namespace App\Http\Controllers;

use App\Events\OrderMatched;
use App\Http\Requests\StoreOrderRequest;
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

        if(is_null($order)) {
            return response()->json([
                'message' => 'Order not found',
            ], 400);
        }

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
        $buyer = $trade->buyOrder->user;
        $seller = $trade->sellOrder->user;
        $symbol = $trade->buyOrder->symbol;
        $data = [
            $buyer->id => [
                'balance' => $buyer->balance,
                'asset' => $buyer->assets->where('symbol', '==', $symbol)->map(function ($asset) {
                    return [
                        'symbol' => $asset->symbol,
                        'amount' => $asset->amount,
                        'locked_amount' => $asset->locked_amount,
                    ];
                })->toArray(),
            ],
            $seller->id => [
                'balance' => $seller->balance,
                'asset' => $seller->assets->where('symbol', '==', $symbol)->map(function ($asset) {
                    return [
                        'symbol' => $asset->symbol,
                        'amount' => $asset->amount,
                        'locked_amount' => $asset->locked_amount,
                    ];
                })->toArray(),
            ],
        ];
        OrderMatched::dispatch($trade);
        return response()->json([
            'message' => 'Trade broadcasted successfully',
        ]);
    }
}
