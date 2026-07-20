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
        Schema::create('backup_verification_histories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('backup_history_id')
                ->constrained('backup_histories')
                ->cascadeOnDelete();

            $table->enum('verification_status', [
                'Verified',
                'Missing',
                'Corrupted',
            ]);

            $table->text('remarks')->nullable();

            $table->timestamp('verified_at');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_verification_histories');
    }
};