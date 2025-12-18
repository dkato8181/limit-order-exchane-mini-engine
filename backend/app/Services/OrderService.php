<?php

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Type\Decimal;

class OrderService
{
    public static $commisionRate = 1.5;

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
                $lockedUser->decrement('balance', $totalCost);
            } else if ($orderData['side'] === 'sell') {
                $asset = $lockedUser->assets()?->where('symbol', $orderData['symbol'])?->lockForUpdate()?->first();
                $asset->increment('locked_amount', $orderData['amount']);
                $asset->decrement('amount', $orderData['amount']);
            }
            $order = Order::create($order);
            DB::commit();
            $this->matchOrders($order);
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
                $lockedUser->increment('balance', $totalCost);
            } else if ($order['side'] === 'sell') {
                $asset = $lockedUser->assets()?->where('symbol', $order['symbol'])?->lockForUpdate()?->first();
                $asset->decrement('locked_amount', $order['amount']);
                $asset->increment('amount', $order['amount']);
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


    private function matchOrders(Order $newOrder)
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
            return;
        }

        $buyOrder = $newOrder->side === 'buy' ? $newOrder : $existingOrder;
        $sellOrder = $newOrder->side === 'sell' ? $newOrder : $existingOrder;

        $tradePrice = $newOrder->side === 'buy' ? min($newOrder->price, $existingOrder->price) : max($newOrder->price, $existingOrder->price);

        $this->settleOrder($buyOrder, $sellOrder, $tradePrice, $newOrder->amount);
    }

    private function settleOrder(Order $buyOrder, Order $sellOrder, Decimal $tradePrice, Decimal $tradeAmount)
    {
        DB::beginTransaction();
        try {
            $buyOrder = Order::lockForUpdate()->find($buyOrder->id);
            $sellOrder = Order::lockForUpdate()->find($sellOrder->id);

            $buyerAsset = $buyOrder->user->assets()->where('symbol', $buyOrder->symbol)->lockForUpdate()->first();
            $buyerAsset->amount += $tradeAmount;
            $buyerAsset->save();

            $sellerAsset = $sellOrder->user->assets()->where('symbol', $sellOrder->symbol)->lockForUpdate()->first();
            $sellerAsset->locked_amount -= $tradeAmount;
            $sellerAsset->save();

            $seller = User::lockForUpdate()->find($sellOrder->user->id);
            $sellerProceeds = ($tradePrice * $tradeAmount) - ($tradePrice * $tradeAmount * (OrderService::$commisionRate / 100));
            $seller->balance += $sellerProceeds;
            $seller->save();

            $buyOrder->status = OrderStatus::FILLED->value;
            $sellOrder->status = OrderStatus::FILLED->value;
            $buyOrder->save();
            $sellOrder->save();

            Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'price' => $tradePrice,
                'amount' => $tradeAmount,
                'commission_rate' => OrderService::$commisionRate,
            ]);

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Trade matching failed: ' . $e->getMessage());
        }

        return;
    }
}
