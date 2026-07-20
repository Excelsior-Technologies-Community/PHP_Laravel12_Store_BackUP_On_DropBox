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
        Schema::create('backup_cleanup_histories', function (Blueprint $table) {

            $table->id();

            // Number of backups kept after cleanup
            $table->unsignedInteger('retention_limit');

            // Number of backups deleted
            $table->unsignedInteger('deleted_backups')->default(0);

            // Total storage space freed
            $table->string('freed_space')->nullable();

            // Cleanup completion status
            $table->enum('status', [
                'Success',
                'Failed'
            ])->default('Success');

            // Optional notes
            $table->text('remarks')->nullable();

            // Cleanup execution time
            $table->timestamp('cleaned_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_cleanup_histories');
    }
};