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
            $table->enum('storage_disk', ['local', 'dropbox', 'both'])->default('local')->after('status');
            $table->string('dropbox_path')->nullable()->after('storage_disk');
            $table->decimal('duration_seconds', 8, 2)->default(0)->after('dropbox_path');
            $table->string('checksum_sha256', 64)->nullable()->after('duration_seconds');
        });

        Schema::create('backup_restore_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_history_id')->nullable()->constrained('backup_histories')->nullOnDelete();
            $table->string('file_name');
            $table->enum('status', ['Success', 'Failed'])->default('Success');
            $table->decimal('duration_seconds', 8, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_restore_histories');

        Schema::table('backup_histories', function (Blueprint $table) {
            $table->dropColumn([
                'storage_disk',
                'dropbox_path',
                'duration_seconds',
                'checksum_sha256',
            ]);
        });
    }
};
