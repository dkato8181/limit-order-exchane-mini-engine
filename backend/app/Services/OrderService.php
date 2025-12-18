<?php

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
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

        DB::beginTransaction();
        try {
            $trade = Trade::create([
                'buy_order_id' => $newOrder->side === 'buy' ? $newOrder->id : $matchingOrder->id,
                'sell_order_id' => $newOrder->side === 'sell' ? $newOrder->id : $matchingOrder->id,
                'price' => $tradePrice,
                'amount' => $newOrder->amount,
                'commission_rate' => 1.5
            ]);
            $commission = $trade->price * $trade->amount * ($trade->commission_rate / 100);
            if($newOrder->side ==='buy') {
                $buyerAsset = $newOrder->user->assets()->where('symbol', $symbol)->first();
                $buyerAsset->amount += $trade->amount;
                $buyerAsset->save();

                $sellerAsset = $matchingOrder->user->assets()->where('symbol', $symbol)->first();
                $sellerAsset->locked_amount -= $trade->amount;
                $sellerAsset->save();

                $seller = $matchingOrder->user;;
                $sellerProceeds = ($trade->price * $trade->amount) - $commission;
                $seller->balance += $sellerProceeds;
                $seller->save();
            }
            else {
                $buyerAsset = $matchingOrder->user->assets()->where('symbol', $symbol)->first();
                $buyerAsset->amount += $trade->amount;
                $buyerAsset->save();

                $sellerAsset = $newOrder->user->assets()->where('symbol', $symbol)->first();
                $sellerAsset->locked_amount -= $trade->amount;
                $sellerAsset->save();

                $seller = $newOrder->user;
                $sellerProceeds = ($trade->price * $trade->amount) - $commission;
                $seller->balance += $sellerProceeds;
                $seller->save();
            }
            $newOrder->status = OrderStatus::FILLED->value;
            $matchingOrder->status = OrderStatus::FILLED->value;
            $newOrder->save();
            $matchingOrder->save();
            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Trade matching failed: ' . $e->getMessage());
            return;
        }
    }
}
