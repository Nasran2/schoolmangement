<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RunDailyBackup extends Command
{
    protected $signature = 'school:backup {--retentionDays=} {--without-uploads}';

    protected $description = 'Create a ZIP backup (database + uploaded files) with retention cleanup';

    public function handle(): int
    {
        $enabled = app('settings')->get('backups.enabled', '1') === '1';
        if (! $enabled) {
            $this->info('Backups are disabled in settings.');
            return Command::SUCCESS;
        }

        $retentionDays = (int) ($this->option('retentionDays')
            ?: app('settings')->get('backups.retention_days', '30')
            ?: 30);
        if ($retentionDays < 1) {
            $retentionDays = 30;
        }

        $includeUploads = ! $this->option('without-uploads');

        if (! class_exists(ZipArchive::class)) {
            $this->error('PHP ZipArchive extension is not available.');
            app('settings')->setMany([
                'backups.last_run_at' => now()->toDateTimeString(),
                'backups.last_status' => 'failed',
                'backups.last_error' => 'ZipArchive extension is not available',
            ], 'backups');
            return Command::FAILURE;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists('backups')) {
            $disk->makeDirectory('backups');
        }

        $timestamp = now()->format('Ymd_His');
        $fileName = "backup_{$timestamp}.zip";
        $relativeZipPath = 'backups/'.$fileName;
        $absoluteZipPath = $disk->path($relativeZipPath);

        $tempSqlPath = $disk->path('backups/.tmp_database_'.$timestamp.'.sql');

        try {
            $this->dumpDatabaseTo($tempSqlPath);

            $zip = new ZipArchive();
            $opened = $zip->open($absoluteZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($opened !== true) {
                throw new \RuntimeException('Could not create ZIP archive.');
            }

            $zip->addFile($tempSqlPath, 'database.sql');

            if ($includeUploads) {
                $uploadsDir = storage_path('app/public');
                $this->addDirectoryToZip($zip, $uploadsDir, 'storage_public');
            }

            $zip->close();

            if (is_file($tempSqlPath)) {
                @unlink($tempSqlPath);
            }

            $this->cleanupOldBackups($retentionDays);

            app('settings')->setMany([
                'backups.last_run_at' => now()->toDateTimeString(),
                'backups.last_status' => 'ok',
                'backups.last_file' => $fileName,
                'backups.last_error' => '',
            ], 'backups');

            app(AuditLogger::class)->log(
                'system.backup.create',
                null,
                'Backup created',
                [
                    'file' => $fileName,
                    'retention_days' => $retentionDays,
                    'include_uploads' => $includeUploads,
                ]
            );

            $this->info("Backup created: {$fileName}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            if (is_file($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            if (is_file($absoluteZipPath)) {
                @unlink($absoluteZipPath);
            }

            app('settings')->setMany([
                'backups.last_run_at' => now()->toDateTimeString(),
                'backups.last_status' => 'failed',
                'backups.last_error' => $e->getMessage(),
            ], 'backups');

            app(AuditLogger::class)->log(
                'system.backup.failed',
                null,
                'Backup failed',
                [
                    'error' => $e->getMessage(),
                ]
            );

            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function dumpDatabaseTo(string $absoluteSqlPath): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver !== 'mysql') {
            throw new \RuntimeException("Database driver '{$driver}' is not supported by this backup command.");
        }

        $pdo = $connection->getPdo();
        $dbName = (string) $connection->getDatabaseName();

        $fh = fopen($absoluteSqlPath, 'wb');
        if (! $fh) {
            throw new \RuntimeException('Could not write database dump file.');
        }

        fwrite($fh, "-- Database backup for {$dbName}\n");
        fwrite($fh, '-- Generated at '.now()->toDateTimeString()."\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        foreach ($tables as $row) {
            $arr = (array) $row;
            $table = (string) (array_values($arr)[0] ?? '');
            if ($table === '') {
                continue;
            }

            $tableEscaped = str_replace('`', '``', $table);

            fwrite($fh, "-- ----------------------------\n");
            fwrite($fh, "-- Table structure for `{$tableEscaped}`\n");
            fwrite($fh, "-- ----------------------------\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$tableEscaped}`;\n");

            $createRow = DB::select("SHOW CREATE TABLE `{$tableEscaped}`")[0] ?? null;
            if (! $createRow) {
                continue;
            }
            $createArr = (array) $createRow;
            $createSql = (string) ($createArr['Create Table'] ?? '');
            if ($createSql === '') {
                continue;
            }
            fwrite($fh, $createSql.";\n\n");

            fwrite($fh, "-- ----------------------------\n");
            fwrite($fh, "-- Records of `{$tableEscaped}`\n");
            fwrite($fh, "-- ----------------------------\n");

            $stmt = $pdo->query("SELECT * FROM `{$tableEscaped}`");
            if (! $stmt) {
                fwrite($fh, "\n");
                continue;
            }

            $columns = null;
            while (($rowData = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                if ($columns === null) {
                    $columns = array_keys($rowData);
                }

                $colSql = implode(', ', array_map(fn ($c) => '`'.str_replace('`', '``', (string) $c).'`', $columns));
                $valuesSql = implode(', ', array_map(function ($col) use ($pdo, $rowData) {
                    $value = $rowData[$col] ?? null;
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }
                    if (is_int($value) || is_float($value)) {
                        return (string) $value;
                    }
                    if (is_numeric($value) && ! preg_match('/^0\d+$/', (string) $value)) {
                        return (string) $value;
                    }

                    return $pdo->quote((string) $value);
                }, $columns));

                fwrite($fh, "INSERT INTO `{$tableEscaped}` ({$colSql}) VALUES ({$valuesSql});\n");
            }

            fwrite($fh, "\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $absoluteDir, string $zipPrefix): void
    {
        $absoluteDir = rtrim($absoluteDir, '/');
        if (! is_dir($absoluteDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }

            $fullPath = $file->getPathname();
            $relative = ltrim(str_replace($absoluteDir, '', $fullPath), '/');
            $zipPath = trim($zipPrefix.'/' . $relative, '/');

            $zip->addFile($fullPath, $zipPath);
        }
    }

    private function cleanupOldBackups(int $retentionDays): void
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'))
            ->filter(fn (string $p) => str_ends_with($p, '.zip'))
            ->values();

        $cutoff = now()->subDays($retentionDays)->timestamp;

        foreach ($files as $path) {
            $lastModified = $disk->lastModified($path);
            if ($lastModified < $cutoff) {
                $disk->delete($path);
            }
        }
    }
}
