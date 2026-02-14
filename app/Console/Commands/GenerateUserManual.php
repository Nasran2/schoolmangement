<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class GenerateUserManual extends Command
{
    protected $signature = 'school:user-manual
        {--path=reports/user_manual.pdf : Output path relative to storage/app}
        {--public : Also write a copy to public/user_manual.pdf}
        {--company= : Vendor / company name to show in the manual}
        {--school= : School name to show in the manual}';

    protected $description = 'Generate a customer-facing user manual PDF (step-by-step usage guide).';

    public function handle(): int
    {
        $company = trim((string) ($this->option('company') ?? ''));
        $school = trim((string) ($this->option('school') ?? ''));

        $outputRelativePath = ltrim((string) $this->option('path'), '/');
        if ($outputRelativePath === '') {
            $outputRelativePath = 'reports/user_manual.pdf';
        }

        $storageAppPath = storage_path('app');
        $outputAbsolutePath = $storageAppPath . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $outputRelativePath);
        File::ensureDirectoryExists(dirname($outputAbsolutePath));

        $data = [
            'generated_at' => now()->toDateTimeString(),
            'company' => $company,
            'school' => $school,
            'app' => [
                'name' => (string) config('app.name'),
                'laravel_version' => App::version(),
                'php_version' => PHP_VERSION,
            ],
        ];

        $pdf = Pdf::loadView('reports.user_manual', $data)
            ->setPaper('a4', 'portrait');

        $pdf->save($outputAbsolutePath);
        $this->info('User manual PDF generated: ' . $outputAbsolutePath);

        if ((bool) $this->option('public')) {
            $publicPath = public_path('user_manual.pdf');
            File::copy($outputAbsolutePath, $publicPath);
            $this->info('Public copy written: ' . $publicPath);
        }

        return self::SUCCESS;
    }
}
