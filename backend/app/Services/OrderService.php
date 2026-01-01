<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
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

    public function canPlaceOrder($orderData)
    {
        $totalCost = $orderData['amount'] * $orderData['price'];
        $user = User::find($orderData['user_id']);
        $assetAmount = $user->assets()->where('symbol', $orderData['symbol'])?->first()?->amount ?? 0;
        if ($orderData['side'] === 'buy' && $user->balance < $totalCost ) {
            throw new Exception('Insufficient balance to place buy order');
        }
        else if ($orderData['side'] === 'sell' && $assetAmount < $orderData['amount']) {
            throw new Exception('Insufficient asset amount to place sell order');
        }
    }

    public function createOrder($orderData, $matchOrder = true): bool|Order
    {
        DB::beginTransaction();

        try {
            if ($orderData['side'] === 'buy') {
                $totalCost = $orderData['amount'] * $orderData['price'];
                $lockedUser = User::lockForUpdate()->find($orderData['user_id']);
                $lockedUser->balance = bcsub($lockedUser->balance, $totalCost, 8);
                $lockedUser->save();
            } else if ($orderData['side'] === 'sell') {
                $lockedAsset = Asset::lockForUpdate()
                                    ->where('user_id', $orderData['user_id'])
                                    ->where('symbol', $orderData['symbol'])
                                    ->first();
                $lockedAsset->locked_amount = bcadd($lockedAsset->locked_amount, $orderData['amount'], 8);
                $lockedAsset->amount = bcsub($lockedAsset->amount, $orderData['amount'], 8);
                $lockedAsset->save();
            }
            $order = Order::create($orderData);
            DB::commit();
            if(!$matchOrder) {
                return $order;
            }
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

    public function cancelOrder($orderData): bool
    {
        DB::beginTransaction();

        try {
            if ($orderData['side'] === 'buy') {
                $totalCost = $orderData['amount'] * $orderData['price'];
                $lockedUser = User::lockForUpdate()->find($orderData['user_id']);
                $lockedUser->balance = bcadd($lockedUser->balance, $totalCost, 8);
                $lockedUser->save();
            } else if ($orderData['side'] === 'sell') {
                $lockedAsset = Asset::lockForUpdate()
                                    ->where('user_id', $orderData['user_id'])
                                    ->where('symbol', $orderData['symbol'])
                                    ->first();
                $lockedAsset->locked_amount = bcsub($lockedAsset->locked_amount, $orderData['amount'], 8);
                $lockedAsset->amount = bcadd($lockedAsset->amount, $orderData['amount'], 8);
                $lockedAsset->save();
            }
            $order = Order::lockForUpdate()->find($orderData['id']);
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
