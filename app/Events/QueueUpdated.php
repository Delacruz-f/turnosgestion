<?php

namespace App\Events;

use App\Support\QueueSnapshot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class QueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    /** $announce: código a anunciar por voz (llamada o re-llamada). */
    public function __construct(
        public ?string $announce = null,
        public ?string $counter = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('queue')];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'snapshot' => QueueSnapshot::get(),
            'announce' => $this->announce,
            'counter' => $this->counter,
        ];
    }
}
