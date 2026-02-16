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
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['listrik', 'air']);
            $table->decimal('last_value', 10, 2)->comment('Current balance (KWH or m³)');
            $table->decimal('threshold', 10, 2)->default(5.00)->comment('Alert threshold');
            $table->string('unit', 10)->nullable()->comment('Unit (KWH, m³, etc)');
            $table->string('updated_by', 50)->nullable()->comment('Who updated last');
            $table->timestamps();

            // Unique constraint: one meter per type per room
            $table->unique(['room_id', 'type']);
            
            // Index for owner queries
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
