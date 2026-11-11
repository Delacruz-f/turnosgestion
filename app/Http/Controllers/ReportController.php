<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Fecha del reporte: por defecto hoy. Se valida para no aceptar basura.
        $date = $request->date('date')?->toDateString()
            ?? Carbon::today()->toDateString();

        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $base = fn () => Ticket::whereBetween('tickets.created_at', [$from, $to]);

        // Totales del día en una sola consulta.
        $totals = (clone $base())
            ->selectRaw("
                count(*) as issued,
                sum(tickets.status = 'done') as served,
                sum(tickets.status = 'skipped') as skipped,
                sum(tickets.priority = 1) as priority_total
            ")
            ->first();

        // Por servicio: volumen y tiempos medios (calculados en la base de datos).
        $byService = (clone $base())
            ->join('services', 'services.id', '=', 'tickets.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderBy('services.name')
            ->selectRaw("
                services.name as service,
                count(*) as issued,
                sum(tickets.status = 'done') as served,
                sum(tickets.status = 'skipped') as skipped,
                avg(timestampdiff(second, tickets.created_at, tickets.called_at)) as wait_seconds,
                avg(timestampdiff(second, tickets.started_at, tickets.finished_at)) as serve_seconds
            ")
            ->get();

        // Turnos emitidos por hora (0-23) para el gráfico de barras.
        $hourlyRows = (clone $base())
            ->selectRaw('hour(tickets.created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        // Ventana del gráfico: de la primera a la última hora con turnos (mínimo 8 h).
        $hoursWithData = $hourlyRows->keys()->map(fn ($h) => (int) $h);
        $first = $hoursWithData->isNotEmpty() ? max(0, $hoursWithData->min() - 1) : 8;
        $last = $hoursWithData->isNotEmpty() ? min(23, $hoursWithData->max() + 1) : 20;
        if ($last - $first < 7) {
            $last = min(23, $first + 7);
        }

        $hourly = collect(range($first, $last))
            ->map(fn ($h) => ['hour' => $h, 'total' => (int) $hourlyRows->get($h, 0)]);

        // Media global del día.
        $averages = (clone $base())
            ->selectRaw("
                avg(timestampdiff(second, tickets.created_at, tickets.called_at)) as wait_seconds,
                avg(timestampdiff(second, tickets.started_at, tickets.finished_at)) as serve_seconds
            ")
            ->first();

        return view('agent.reports', [
            'date' => $date,
            'totals' => $totals,
            'byService' => $byService,
            'hourly' => $hourly,
            'averages' => $averages,
        ]);
    }
}
