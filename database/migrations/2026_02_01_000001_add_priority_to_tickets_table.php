<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->boolean('priority')->default(false)->after('document');
            $t->index(['service_id', 'status', 'priority', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->dropIndex(['service_id', 'status', 'priority', 'id']);
            $t->dropColumn('priority');
        });
    }
};
