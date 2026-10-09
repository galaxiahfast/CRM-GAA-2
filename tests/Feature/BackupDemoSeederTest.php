<?php

namespace Tests\Feature;

use App\Models\BackupUpload;
use Database\Seeders\BackupDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_fifteen_complete_company_trees_idempotently(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');

        $this->seed(BackupDemoSeeder::class);
        $this->seed(BackupDemoSeeder::class);

        $uploads = BackupUpload::query()->where('site', 'merida')->get();
        $this->assertCount(15, $uploads);
        $uploads->each(function (BackupUpload $upload): void {
            $this->assertSame(BackupUpload::STATUS_COMPLETED, $upload->status);
            $this->assertSame(['index', 'bak'], collect($upload->manifest)->pluck('category')->all());
            $this->assertTrue(Storage::disk('local')->exists($upload->storage_path));
        });
    }
}
