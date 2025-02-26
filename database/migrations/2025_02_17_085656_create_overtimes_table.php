<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi dengan user
            $table->date('date'); // Tanggal lembur
            $table->double('overtime_hours'); // Jumlah jam lembur
            $table->boolean('status'); // 0 for Tidak Masuk, 1 for Masuk
            $table->string('day_type')->default('regular');
            $table->double('total_overtime'); // Total harga lemburan
            $table->json('overtime_details');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
