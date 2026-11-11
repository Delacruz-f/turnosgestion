<?php

namespace App\Support;

use App\Models\Service;
use App\Models\Ticket;

class QueueSnapshot
{
    public static function get(): array
    {
        $calls = Ticket::with(['counter', 'service'])
            ->whereDate('created_at', today())
            ->whereNotNull('called_at')
            ->whereIn('status', ['calling', 'serving', 'done'])
            ->orderByDesc('called_at')
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'code' => $t->code,
                'counter' => $t->counter?->name,
                'service' => $t->service->name,
                'status' => $t->status,
                'priority' => $t->priority,
            ])->values();

        // La cola real se cuenta en dos grupos para respetar el orden preferencial.
        $waiting = Service::where('active', true)
            ->withCount([
                'tickets as waiting' => fn ($q) => $q
                    ->where('status', 'waiting')->whereDate('created_at', today()),
                'tickets as waiting_priority' => fn ($q) => $q
                    ->where('status', 'waiting')->where('priority', true)->whereDate('created_at', today()),
            ])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'waiting' => $s->waiting,
                'priority' => $s->waiting_priority,
            ])
            ->values();

        return ['calls' => $calls, 'waiting' => $waiting];
    }
}
