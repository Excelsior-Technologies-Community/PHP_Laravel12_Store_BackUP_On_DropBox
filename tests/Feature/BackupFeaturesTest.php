<?php

namespace Tests\Feature;

use App\Models\BackupHistory;
use App\Models\BackupRestoreHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test 1: Dashboard page loads with stats, Dropbox widgets and Analytics
     */
    public function test_backup_dashboard_loads_successfully(): void
    {
        $response = $this->get(route('backup.index'));

        $response->assertStatus(200);
        $response->assertSee('Cloud Storage Quota');
        $response->assertSee('Backup Size Growth &amp; Activity (Last 30 Days)', false);
        $response->assertSee('Backup Retention Policy');
    }

    /**
     * Test 2: Upload backup page loads with dual storage options
     */
    public function test_backup_create_page_loads_with_storage_options(): void
    {
        $response = $this->get(route('backup.create'));

        $response->assertStatus(200);
        $response->assertSee('Dual Storage');
        $response->assertSee('Dropbox Only');
        $response->assertSee('Local Only');
    }

    /**
     * Test 3: Analytics API returns JSON data for 30-day Chart.js
     */
    public function test_analytics_api_returns_valid_json_structure(): void
    {
        $response = $this->get(route('backup.analytics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'chart' => [
                'labels',
                'sizes_mb',
                'counts',
            ],
            'distribution' => [
                'local',
                'dropbox',
                'dual',
            ],
            'verification',
            'summary' => [
                'avg_duration',
                'total_restores',
                'success_restores',
            ],
        ]);
    }

    /**
     * Test 4: Dropbox connection test and quota endpoints
     */
    public function test_dropbox_api_endpoints_work(): void
    {
        $testResponse = $this->get(route('backup.dropbox.test'));
        $testResponse->assertStatus(200);
        $testResponse->assertJsonStructure(['connected', 'message']);

        $quotaResponse = $this->get(route('backup.dropbox.quota'));
        $quotaResponse->assertStatus(200);
        $quotaResponse->assertJsonStructure([
            'total_bytes',
            'used_bytes',
            'available_bytes',
            'total_formatted',
            'used_formatted',
            'available_formatted',
            'percent_used',
        ]);
    }

    /**
     * Test 5: Uploading backup zip creates record with storage_disk & checksum
     */
    public function test_uploading_backup_creates_history_record(): void
    {
        // Create a temporary valid zip
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tempZipPath, ZipArchive::CREATE);
        $zip->addFromString('dump.sql', '-- database dump test');
        $zip->close();

        $uploadedFile = new UploadedFile(
            $tempZipPath,
            'test_backup.zip',
            'application/zip',
            null,
            true
        );

        $response = $this->post(route('backup.store'), [
            'backup_file' => $uploadedFile,
            'storage_disk' => 'both',
        ]);

        $response->assertRedirect(route('backup.index'));

        $this->assertDatabaseHas('backup_histories', [
            'storage_disk' => 'both',
            'status' => 'Success',
        ]);

        if (file_exists($tempZipPath)) {
            @unlink($tempZipPath);
        }
    }

    /**
     * Test 6: Generating direct secure temporary share link
     */
    public function test_generate_share_link_returns_valid_json(): void
    {
        $backup = BackupHistory::create([
            'file_name' => 'share_test.zip',
            'file_size' => '100 KB',
            'status' => 'Success',
            'storage_disk' => 'local',
            'duration_seconds' => 1.5,
            'checksum_sha256' => 'dummychecksum',
        ]);

        $response = $this->get(route('backup.share', $backup->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'type',
            'url',
        ]);
    }

    /**
     * Test 7: One-click Database restore flow extracts SQL and logs restore history
     */
    public function test_restore_database_flow(): void
    {
        // Create an actual zip in storage with a dummy sql
        $fileName = 'restore_test_' . time() . '.zip';
        $fullPath = storage_path('app/public/backups/' . $fileName);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($fullPath, ZipArchive::CREATE);
        $zip->addFromString('dump.sql', 'CREATE TABLE IF NOT EXISTS restore_test_tbl (id INTEGER PRIMARY KEY);');
        $zip->close();

        $backup = BackupHistory::create([
            'file_name' => $fileName,
            'file_size' => '50 KB',
            'status' => 'Success',
            'storage_disk' => 'local',
            'duration_seconds' => 1.2,
        ]);

        $response = $this->post(route('backup.restore', $backup->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_restore_histories', [
            'backup_history_id' => $backup->id,
            'status' => 'Success',
        ]);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * Test 8: Restore history page displays logs
     */
    public function test_restore_history_page_loads(): void
    {
        $response = $this->get(route('backup.restore.history'));

        $response->assertStatus(200);
        $response->assertSee('Database Restore History');
    }
}
