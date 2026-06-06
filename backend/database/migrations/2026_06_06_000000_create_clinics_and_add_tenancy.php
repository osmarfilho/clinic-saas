<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $defaultClinicId = DB::table('clinics')->insertGetId([
            'name' => 'Clinic SaaS',
            'email' => 'contato@clinic.test',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['clinic_id', 'email']);
        });

        DB::table('users')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('patients')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable(false)->change();
            $table->dropUnique(['cpf']);
            $table->unique(['clinic_id', 'cpf']);
            $table->index(['clinic_id', 'ativo']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('appointments')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable(false)->change();
            $table->index(['clinic_id', 'starts_at', 'status']);
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('financial_transactions')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable(false)->change();
            $table->index(['clinic_id', 'type', 'status', 'due_date']);
        });

        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('clinic_settings')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable(false)->change();
            $table->dropUnique(['key']);
            $table->unique(['clinic_id', 'key']);
        });

        Schema::table('clinic_notifications', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('clinic_notifications')->update(['clinic_id' => $defaultClinicId]);

        Schema::table('clinic_notifications', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable(false)->change();
            $table->index(['clinic_id', 'user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('clinic_notifications', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'user_id', 'read_at', 'created_at']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->dropUnique(['clinic_id', 'key']);
            $table->unique('key');
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'type', 'status', 'due_date']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'starts_at', 'status']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'ativo']);
            $table->dropUnique(['clinic_id', 'cpf']);
            $table->unique('cpf');
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'email']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::dropIfExists('clinics');
    }
};
