<?php

namespace Tests\Feature\Deployment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class ProductionReadinessEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function token_mismatch_status_renders_custom_419_page(): void
    {
        Route::middleware('web')->get('/_test_419_rendering', function () {
            abort(419);
        });

        $response = $this->get('/_test_419_rendering');

        $response->assertStatus(419);
        $response->assertSee('Session expired');
    }

    #[Test]
    public function manual_backup_failure_returns_safe_error_message(): void
    {
        Permission::findOrCreate('settings.backups.manage', 'web');

        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo('settings.backups.manage');

        Artisan::shouldReceive('call')
            ->once()
            ->andThrow(new RuntimeException('backup command failed'));

        $response = $this->actingAs($user)
            ->from(route('settings.backups.index'))
            ->post(route('settings.backups.run'));

        $response->assertRedirect(route('settings.backups.index'));
        $response->assertSessionHasErrors('backup_run');
    }

    #[Test]
    public function report_zip_bundle_only_contains_permitted_reports(): void
    {
        Permission::findOrCreate('reports.view', 'web');
        Permission::findOrCreate('reports.download', 'web');
        Permission::findOrCreate('reports.revenue.view', 'web');

        /** @var User $user */
        $user = User::factory()->create();
        $user->givePermissionTo([
            'reports.view',
            'reports.download',
            'reports.revenue.view',
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.download_all'));

        $response->assertOk();
        $response->assertDownload();

        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);

        ob_start();
        $baseResponse->sendContent();
        $zipBinary = (string) ob_get_clean();
        $this->assertStringStartsWith('PK', $zipBinary);

        $zipPath = tempnam(sys_get_temp_dir(), 'reports-test-');
        $this->assertIsString($zipPath);
        file_put_contents($zipPath, $zipBinary);

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        $this->assertTrue($opened === true, 'Unable to open generated reports zip file.');

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && $name !== '') {
                $entries[] = $name;
            }
        }
        $zip->close();

        $this->assertContains('01-revenue.pdf', $entries);
        $this->assertNotContains('02-expense.pdf', $entries);
        $this->assertNotContains('03-financial.pdf', $entries);
        $this->assertNotContains('12-student-due.pdf', $entries);

        @unlink($zipPath);
    }
}
