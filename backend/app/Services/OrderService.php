<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\OrderStatus;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public static string $commisionRate = "1.5";

    public function canPlaceOrder($user, $orderData)
    {
        $totalCost = $orderData['amount'] * $orderData['price'];
        $assetAmount = $user->assets()->where('symbol', $orderData['symbol'])?->first()?->amount ?? 0;
        if ($orderData['side'] === 'buy' && $user->balance < $totalCost ) {
            throw new Exception('Insufficient balance to place buy order');
        }
        else if ($orderData['side'] === 'sell' && $assetAmount < $orderData['amount']) {
            throw new Exception('Insufficient asset amount to place sell order');
        }
    }

    public function createOrder($user, $orderData): bool|Order
    {
        $order = array_merge(
                    [
                        'user_id' => $user->id,
                        'status' => OrderStatus::OPEN->value
                    ],
                    $orderData);

        $totalCost = $orderData['amount'] * $orderData['price'];

        DB::beginTransaction();

        try {
            $lockedUser = User::lockForUpdate()->find($user->id);
            if ($orderData['side'] === 'buy') {
                $lockedUser->balance = bcsub($lockedUser->balance, $totalCost, 8);
            } else if ($orderData['side'] === 'sell') {
                $asset = $lockedUser->assets()?->where('symbol', $orderData['symbol'])?->lockForUpdate()?->first();
                $asset->locked_amount = bcadd($asset->locked_amount, $orderData['amount'], 8);
                $asset->amount = bcsub($asset->amount, $orderData['amount'], 8);
                $asset->save();
            }
            $lockedUser->save();
            $order = Order::create($order);
            DB::commit();
            $trade = $this->matchOrders($order);
            if($trade!==false) {
                $data = $trade->load(['buyOrder.user.assets', 'sellOrder.user.assets']);
                OrderMatched::dispatch($data);
            }
            return $order;
        } catch (Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            DB::rollBack();
            return false;
        }
    }

    public function cancelOrder($user, $order): bool
    {
        $totalCost = $order['amount'] * $order['price'];

        DB::beginTransaction();

        try {
            $lockedUser = User::lockForUpdate()->find($user->id);
            if ($order['side'] === 'buy') {
                $lockedUser->balance = bcadd($lockedUser->balance, $totalCost, 8);
            } else if ($order['side'] === 'sell') {
                $asset = $lockedUser->assets()?->where('symbol', $order['symbol'])?->lockForUpdate()?->first();
                $asset->locked_amount = bcsub($asset->locked_amount, $order['amount'], 8);
                $asset->amount = bcadd($asset->amount, $order['amount'], 8);
            }
            $order->status = OrderStatus::CANCELLED;
            $order->save();
            DB::commit();
        } catch (Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            DB::rollBack();
            return false;
        }

        return true;
    }


    private function matchOrders(Order $newOrder): bool|Trade
    {
        $existingOrder = Order::query()
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

        if (is_null($existingOrder)) {
            return false;
        }

        $buyOrder = $newOrder->side === 'buy' ? $newOrder : $existingOrder;
        $sellOrder = $newOrder->side === 'sell' ? $newOrder : $existingOrder;

        $tradePrice = $newOrder->side === 'buy' ? min($newOrder->price, $existingOrder->price) : max($newOrder->price, $existingOrder->price);

        return $this->settleOrder($buyOrder, $sellOrder, $tradePrice, $newOrder->amount);
    }

    private function settleOrder(Order $buyOrder, Order $sellOrder, string $tradePrice, string $tradeAmount): bool|Trade
    {
        DB::beginTransaction();
        try {
            $buyOrder = Order::lockForUpdate()->find($buyOrder->id);
            $sellOrder = Order::lockForUpdate()->find($sellOrder->id);

            $buyerAsset = $buyOrder->user->assets()->where('symbol', $buyOrder->symbol)->lockForUpdate()->first();
            if(is_null($buyerAsset)) {
                $buyerAsset = $buyOrder->user->assets()->create([
                    'symbol' => $buyOrder->symbol,
                    'amount' => 0,
                    'locked_amount' => 0,
                ]);
            }
            $buyerAsset->amount = bcadd($buyerAsset->amount, $tradeAmount);
            $buyerAsset->save();

            $sellerAsset = $sellOrder->user->assets()->where('symbol', $sellOrder->symbol)->lockForUpdate()->first();
            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $tradeAmount);
            $sellerAsset->save();

            $seller = User::lockForUpdate()->find($sellOrder->user->id);
            $gross = bcmul($tradePrice, $tradeAmount, 8);
            $rate  = bcdiv(OrderService::$commisionRate, "100", 8);
            $sellerProceeds = bcsub($gross, bcmul($gross, $rate, 8));
            $seller->balance = bcadd($seller->balance, $sellerProceeds, 8);
            $seller->save();

            $buyOrder->status = OrderStatus::FILLED->value;
            $sellOrder->status = OrderStatus::FILLED->value;
            $buyOrder->save();
            $sellOrder->save();

            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'price' => $tradePrice,
                'amount' => $tradeAmount,
                'commission_rate' => OrderService::$commisionRate,
            ]);

            DB::commit();
            return $trade;
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Trade matching failed: ' . $e->getMessage());
        }

        return false;
    }
}
