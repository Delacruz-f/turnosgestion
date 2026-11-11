<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = ['name', 'prefix', 'description', 'active'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }
}
