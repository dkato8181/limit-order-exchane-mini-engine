<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Trade $trade)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->trade->buyOrder->user->id}"),
            new PrivateChannel("user.{$this->trade->sellOrder->user->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        $buyer = $this->trade->buyOrder->user;
        $seller = $this->trade->sellOrder->user;
        $symbol = $this->trade->buyOrder->symbol;
        return [
            [
                'user_id' => $buyer->id,
                'order_id' => $this->trade->buy_order_id,
                'balance' => $buyer->balance,
                'asset' => $buyer->assets->where('symbol', '==', $symbol)->map(function ($asset) {
                    return [
                        'symbol' => $asset->symbol,
                        'amount' => $asset->amount,
                    ];
                })->toArray(),
            ],
            [
                'user_id' => $seller->id,
                'order_id' => $this->trade->sell_order_id,
                'balance' => $seller->balance,
                'asset' => $seller->assets->where('symbol', '==', $symbol)->map(function ($asset) {
                    return [
                        'symbol' => $asset->symbol,
                        'amount' => $asset->amount,
                    ];
                })->toArray(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }
}
