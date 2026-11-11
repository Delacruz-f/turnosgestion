<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /**
     * Regla de equidad: por cada turno preferencial se atienden 2 normales.
     * Evita que la cola preferencial (o la normal) muera de inanición.
     */
    public const NORMAL_PER_PRIORITY = 2;

    protected $fillable = [
        'token', 'service_id', 'counter_id', 'number', 'code', 'status',
        'document', 'priority', 'called_at', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'priority' => 'boolean',
        'called_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    /** Emite un turno con numeración diaria por servicio (segura ante concurrencia). */
    public static function issue(Service $service, ?string $document = null, bool $priority = false): self
    {
        return DB::transaction(function () use ($service, $document, $priority) {
            $last = static::where('service_id', $service->id)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->max('number');

            $number = ($last ?? 0) + 1;

            return static::create([
                'token' => (string) Str::uuid(),
                'service_id' => $service->id,
                'number' => $number,
                'code' => $service->prefix . str_pad($number, 3, '0', STR_PAD_LEFT) . ($priority ? 'P' : ''),
                'document' => $document,
                'priority' => $priority,
            ]);
        });
    }

    /** Turnos en espera de un servicio en una fecha, en el orden real de atención. */
    public function scopeWaitingOrdered(Builder $q): Builder
    {
        return $q->where('status', 'waiting')
            ->orderByDesc('priority')
            ->orderBy('id');
    }

    /**
     * ¿Toca llamar a un preferencial ahora?
     *
     * Regla única del sistema (la usan el panel, los reportes y position()):
     * un turno preferencial solo pasa delante cuando ya se llamaron al menos
     * NORMAL_PER_PRIORITY normales por cada preferencial ya llamado hoy.
     */
    public static function shouldCallPriorityNow(int $priorityCalled, int $normalCalled): bool
    {
        return $normalCalled >= $priorityCalled * static::NORMAL_PER_PRIORITY;
    }

    /** Contadores de turnos ya llamados hoy en un servicio, por prioridad. */
    public static function calledCounts(int $serviceId, string $date): array
    {
        $counts = static::where('service_id', $serviceId)
            ->whereDate('created_at', $date)
            ->whereIn('status', ['calling', 'serving', 'done', 'skipped'])
            ->whereNotNull('called_at')
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        return [(int) ($counts[1] ?? 0), (int) ($counts[0] ?? 0)]; // [preferenciales, normales]
    }

    /**
     * Personas que se atenderán antes que este turno en el mismo servicio.
     *
     * Simula el algoritmo de asignación con las colas de HOY: parte de los
     * contadores ya llamados y va sacando turnos con la misma regla que
     * AgentController::next(), deteniéndose al llegar a este turno.
     */
    public function position(): int
    {
        if ($this->status !== 'waiting') {
            return 0;
        }

        [$priorityServed, $normalServed] = static::calledCounts(
            $this->service_id, $this->created_at->toDateString()
        );

        $queue = static::where('service_id', $this->service_id)
            ->where('status', 'waiting')
            ->whereDate('created_at', $this->created_at->toDateString())
            ->orderBy('id')
            ->get(['id', 'priority']);

        $priorityQueue = $queue->where('priority', true)->values();
        $normalQueue = $queue->where('priority', false)->values();
        $ahead = 0;

        while (true) {
            $takePriority = $priorityQueue->isNotEmpty()
                && ($priorityQueue->first()->id === $this->id
                    || static::shouldCallPriorityNow($priorityServed, $normalServed));

            $next = $takePriority ? $priorityQueue->shift() : $normalQueue->shift();

            if (! $next || $next->id === $this->id) {
                return $ahead;
            }

            $next->priority ? $priorityServed++ : $normalServed++;
            $ahead++;
        }
    }
}
