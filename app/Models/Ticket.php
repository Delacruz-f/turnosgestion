<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'token', 'service_id', 'counter_id', 'number', 'code', 'status',
        'document', 'called_at', 'started_at', 'finished_at',
    ];

    protected $casts = [
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
    public static function issue(Service $service, ?string $document = null): self
    {
        return DB::transaction(function () use ($service, $document) {
            $last = static::where('service_id', $service->id)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->max('number');

            $number = ($last ?? 0) + 1;

            return static::create([
                'token' => (string) Str::uuid(),
                'service_id' => $service->id,
                'number' => $number,
                'code' => $service->prefix . str_pad($number, 3, '0', STR_PAD_LEFT),
                'document' => $document,
            ]);
        });
    }

    /** Personas esperando antes de este turno en el mismo servicio. */
    public function position(): int
    {
        if ($this->status !== 'waiting') {
            return 0;
        }

        return static::where('service_id', $this->service_id)
            ->where('status', 'waiting')
            ->whereDate('created_at', $this->created_at->toDateString())
            ->where('id', '<', $this->id)
            ->count();
    }
}
