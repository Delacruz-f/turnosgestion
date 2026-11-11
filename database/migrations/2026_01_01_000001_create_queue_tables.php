<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('prefix', 3)->unique();
            $t->string('description')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('counters', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->uuid('token')->unique();
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('number');
            $t->string('code', 8);
            $t->string('status', 12)->default('waiting'); // waiting|calling|serving|done|skipped
            $t->string('document', 20)->nullable();
            $t->timestamp('called_at')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
            $t->index(['service_id', 'status', 'id']);
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
    }
};
