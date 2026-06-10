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
        Schema::create('plate_transfers_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plate_id')->constrained('plates')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnUpdate()->noActionOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnUpdate()->noActionOnDelete();
            $table->timestamp('transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plate_transfers_histories');
    }
};
