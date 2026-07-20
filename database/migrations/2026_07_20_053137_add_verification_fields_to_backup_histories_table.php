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
        Schema::table('backup_histories', function (Blueprint $table) {

            $table->enum('verification_status', [
                'Pending',
                'Verified',
                'Missing',
                'Corrupted',
            ])->default('Pending')->after('status');

            $table->timestamp('verified_at')
                ->nullable()
                ->after('verification_status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {

            $table->dropColumn([
                'verification_status',
                'verified_at',
            ]);

        });
    }
};