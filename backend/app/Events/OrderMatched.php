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
            //new Channel("orders"),
            new PrivateChannel("orders.user.{$this->trade->buyOrder->user_id}"),
            new PrivateChannel("orders.user.{$this->trade->sellOrder->user_id}"),
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
                'assets' => $buyer->assets->toArray(),
            ],
            [
                'user_id' => $seller->id,
                'order_id' => $this->trade->sell_order_id,
                'balance' => $seller->balance,
                'assets' => $seller->assets->toArray(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }
}
