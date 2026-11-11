<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function index()
    {
        $services = Service::where('active', true)
            ->withCount(['tickets as waiting' => fn ($q) => $q
                ->where('status', 'waiting')->whereDate('created_at', today())])
            ->orderBy('name')->get();

        return view('kiosk.index', compact('services'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'document' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
            'priority' => ['nullable', 'boolean'],
        ]);

        $service = Service::where('active', true)->findOrFail($data['service_id']);
        $ticket = Ticket::issue($service, $data['document'] ?? null, $request->boolean('priority'));

        QueueUpdated::dispatch();

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['service', 'counter']);

        return view('kiosk.ticket', ['ticket' => $ticket, 'position' => $ticket->position()]);
    }

    public function status(Ticket $ticket)
    {
        $ticket->load(['service', 'counter']);

        return response()->json([
            'code' => $ticket->code,
            'status' => $ticket->status,
            'position' => $ticket->position(),
            'counter' => $ticket->counter?->name,
        ]);
    }
}
