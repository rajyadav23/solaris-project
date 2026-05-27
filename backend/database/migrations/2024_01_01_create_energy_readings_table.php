<?php
// Migration: create_energy_readings_table
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('solar_kw',    8, 2)->default(0);
            $table->decimal('wind_kw',     8, 2)->default(0);
            $table->decimal('demand_kw',   8, 2)->default(0);
            $table->decimal('battery_soc', 5, 2)->default(0);
            $table->decimal('temperature', 6, 2)->nullable();
            $table->decimal('wind_speed',  6, 2)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('energy_readings'); }
};

// ─────────────────────────────────────────────────────
// Migration: create_chat_messages_table
// (create as separate file: 2024_01_02_000000_create_chat_messages_table.php)
// ─────────────────────────────────────────────────────
/*
return new class extends Migration {
    public function up(): void {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant']);
            $table->text('message');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('chat_messages'); }
};
*/
