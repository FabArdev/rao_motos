<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un backup de la base de datos PostgreSQL en storage/app/backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando backup de base de datos...');

        // Verificar que existe pg_dump
        if (! $this->verificarPgDump()) {
            $this->error('❌ No se encontró pg_dump en el sistema.');
            $this->error('Asegúrate de tener PostgreSQL instalado y pg_dump en tu PATH.');

            return 1;
        }

        // Crear directorio de backups si no existe
        $backupDir = storage_path('app/backups');
        if (! file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
            $this->info("📁 Directorio de backups creado: {$backupDir}");
        }

        // Generar nombre de archivo
        $filename = 'backup_'.date('Ymd_His').'.sql';
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        // Obtener configuración de base de datos
        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port');
        $dbName = config('database.connections.pgsql.database');
        $dbUser = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');

        // Construir comando pg_dump
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -F p -b -v -f %s %s 2>&1',
            escapeshellarg($dbPassword),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($filepath),
            escapeshellarg($dbName)
        );

        // Ejecutar comando
        $this->info('Ejecutando pg_dump...');
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($filepath)) {
            $size = $this->formatBytes(filesize($filepath));
            $this->info('✅ Backup completado exitosamente!');
            $this->info("📄 Archivo: {$filename}");
            $this->info("📦 Tamaño: {$size}");
            $this->info("📂 Ubicación: {$filepath}");

            return 0;
        } else {
            $this->error('❌ Error al generar el backup.');
            if (! empty($output)) {
                $this->error('Salida del comando:');
                foreach ($output as $line) {
                    $this->error($line);
                }
            }

            return 1;
        }
    }

    /**
     * Verificar si pg_dump está disponible
     */
    private function verificarPgDump(): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump' : 'which pg_dump';
        exec($command, $output, $returnVar);

        return $returnVar === 0;
    }

    /**
     * Formatear bytes a tamaño legible
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
