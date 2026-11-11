<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\Counter;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    private function counter(Request $request): Counter
    {
        return Counter::with('service')
            ->where('user_id', $request->user()->id)
            ->where('active', true)
            ->firstOrFail(); // 404 si el usuario no tiene ventanilla asignada
    }

    private function current(Counter $counter): ?Ticket
    {
        return Ticket::where('counter_id', $counter->id)
            ->whereIn('status', ['calling', 'serving'])
            ->latest('called_at')->first();
    }

    public function panel(Request $request)
    {
        $counter = $this->counter($request);

        return view('agent.panel', [
            'counter' => $counter,
            'ticket' => $this->current($counter),
            'waiting' => Ticket::where('service_id', $counter->service_id)
                ->where('status', 'waiting')->whereDate('created_at', today())->count(),
            'served' => Ticket::where('counter_id', $counter->id)
                ->where('status', 'done')->whereDate('created_at', today())->count(),
        ]);
    }

    public function next(Request $request)
    {
        $counter = $this->counter($request);

        if ($this->current($counter)) {
            return back()->with('error', 'Finaliza el turno actual antes de llamar al siguiente.');
        }

        $ticket = DB::transaction(function () use ($counter) {
            $t = Ticket::where('service_id', $counter->service_id)
                ->where('status', 'waiting')
                ->whereDate('created_at', today())
                ->orderBy('id')->lockForUpdate()->first();

            $t?->update(['status' => 'calling', 'counter_id' => $counter->id, 'called_at' => now()]);

            return $t;
        });

        if (! $ticket) {
            return back()->with('error', 'No hay turnos en espera.');
        }

        QueueUpdated::dispatch($ticket->code, $counter->name);

        return back();
    }

    public function recall(Request $request)
    {
        $counter = $this->counter($request);
        $ticket = $this->current($counter);

        if ($ticket) {
            $ticket->update(['called_at' => now()]);
            QueueUpdated::dispatch($ticket->code, $counter->name);
        }

        return back();
    }

    public function start(Request $request)
    {
        $counter = $this->counter($request);
        $this->current($counter)?->update(['status' => 'serving', 'started_at' => now()]);
        QueueUpdated::dispatch();

        return back();
    }

    public function finish(Request $request)
    {
        $counter = $this->counter($request);
        $this->current($counter)?->update(['status' => 'done', 'finished_at' => now()]);
        QueueUpdated::dispatch();

        return back();
    }

    public function skip(Request $request)
    {
        $counter = $this->counter($request);
        $this->current($counter)?->update(['status' => 'skipped', 'finished_at' => now()]);
        QueueUpdated::dispatch();

        return back();
    }
}
