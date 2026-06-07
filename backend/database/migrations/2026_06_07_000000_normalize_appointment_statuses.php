<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('appointments')
            ->where('status', 'confirmed')
            ->update(['status' => 'scheduled']);

        DB::table('appointments')
            ->where('status', 'canceled')
            ->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
    }
};
