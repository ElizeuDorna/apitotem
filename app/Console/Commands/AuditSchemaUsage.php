<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AuditSchemaUsage extends Command
{
    protected $signature = 'audit:schema-usage
        {--save : Salva o relatório em storage/app/audits}
        {--json : Exibe o resultado em JSON}
        {--include-framework : Inclui tabelas internas do Laravel/framework no relatório}
        {--limit=5 : Quantidade máxima de arquivos exibidos por tabela/campo}';

    protected $description = 'Audita tabelas e colunas potencialmente sem uso de runtime no código.';

    private array $ignoredColumns = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'email_verified_at',
    ];

    private array $frameworkTables = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
    ];

    public function handle(): int
    {
        $this->warn('Auditoria heurística: ausência de referência no código não prova remoção segura.');

        $tables = $this->loadSchemaTables();

        if ($tables === []) {
            $this->error('Nenhuma tabela foi encontrada para auditoria.');

            return self::FAILURE;
        }

        $files = $this->runtimeFiles();
        $fileContents = $this->loadFileContents($files);
        $limit = max(1, (int) $this->option('limit'));

        $report = [
            'connection' => DB::getDefaultConnection(),
            'database' => (string) DB::connection()->getDatabaseName(),
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'tables_scanned' => 0,
                'runtime_files_scanned' => count($fileContents),
                'tables_without_runtime_reference' => 0,
                'columns_without_runtime_reference' => 0,
            ],
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (! $this->option('include-framework') && in_array($table['name'], $this->frameworkTables, true)) {
                continue;
            }

            $report['summary']['tables_scanned']++;

            $tableMatches = $this->matchFiles($fileContents, $this->tableNeedles($table['name']));
            $unusedColumns = [];

            foreach ($table['columns'] as $column) {
                if (in_array($column, $this->ignoredColumns, true)) {
                    continue;
                }

                $columnMatches = $this->matchFiles($fileContents, $this->columnNeedles($column));

                if ($columnMatches === []) {
                    $unusedColumns[] = $column;
                }
            }

            if ($tableMatches === []) {
                $report['summary']['tables_without_runtime_reference']++;
            }

            $report['summary']['columns_without_runtime_reference'] += count($unusedColumns);

            $report['tables'][] = [
                'table' => $table['name'],
                'runtime_reference_files' => array_slice($tableMatches, 0, $limit),
                'runtime_reference_count' => count($tableMatches),
                'columns_without_runtime_reference' => $unusedColumns,
            ];
        }

        if ($this->option('save')) {
            $this->saveReport($report);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderReport($report, $limit);

        return self::SUCCESS;
    }

    private function renderReport(array $report, int $limit): void
    {
        $this->newLine();
        $this->info('Resumo da auditoria');
        $this->table(
            ['Conexão', 'Banco', 'Tabelas', 'Arquivos varridos', 'Tabelas suspeitas', 'Colunas suspeitas'],
            [[
                $report['connection'],
                $report['database'],
                $report['summary']['tables_scanned'],
                $report['summary']['runtime_files_scanned'],
                $report['summary']['tables_without_runtime_reference'],
                $report['summary']['columns_without_runtime_reference'],
            ]]
        );

        $suspectTables = array_values(array_filter(
            $report['tables'],
            fn (array $table): bool => $table['runtime_reference_count'] === 0 || $table['columns_without_runtime_reference'] !== []
        ));

        if ($suspectTables === []) {
            $this->info('Nenhuma tabela ou coluna suspeita foi encontrada pela heurística atual.');

            return;
        }

        foreach ($suspectTables as $table) {
            $this->newLine();
            $headline = $table['table'];

            if ($table['runtime_reference_count'] === 0) {
                $this->warn("Tabela possivelmente órfã: {$headline}");
            } else {
                $this->line("Tabela com colunas suspeitas: {$headline}");
            }

            if ($table['runtime_reference_files'] !== []) {
                $this->line('Referências de runtime encontradas:');
                foreach ($table['runtime_reference_files'] as $file) {
                    $this->line(' - '.$file);
                }

                if ($table['runtime_reference_count'] > $limit) {
                    $this->line(' - ...');
                }
            }

            if ($table['columns_without_runtime_reference'] !== []) {
                $this->line('Colunas possivelmente sem uso: '.implode(', ', $table['columns_without_runtime_reference']));
            }
        }
    }

    private function saveReport(array $report): void
    {
        $directory = storage_path('app/audits');
        File::ensureDirectoryExists($directory);

        $file = $directory.'/schema-usage-'.now()->format('Ymd-His').'.json';
        File::put($file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Relatório salvo em: '.$file);
    }

    private function runtimeFiles(): array
    {
        $directories = [
            app_path(),
            base_path('routes'),
            resource_path('views'),
            base_path('tests'),
            database_path('factories'),
            database_path('seeders'),
            config_path(),
        ];

        $files = [];

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function loadFileContents(array $files): array
    {
        $contents = [];

        foreach ($files as $file) {
            $contents[$file] = strtolower((string) File::get($file));
        }

        return $contents;
    }

    private function matchFiles(array $fileContents, array $needles): array
    {
        $needles = array_values(array_filter(array_map(
            fn (?string $needle): string => strtolower(trim((string) $needle)),
            $needles
        )));

        if ($needles === []) {
            return [];
        }

        $matches = [];

        foreach ($fileContents as $file => $content) {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($content, $needle)) {
                    $matches[] = str_replace(base_path().'/', '', $file);
                    break;
                }
            }
        }

        return $matches;
    }

    private function loadSchemaTables(): array
    {
        $driver = DB::getDriverName();
        $database = (string) DB::connection()->getDatabaseName();

        return match ($driver) {
            'mysql', 'mariadb' => $this->loadMySqlSchema($database),
            'sqlite' => $this->loadSqliteSchema(),
            'pgsql' => $this->loadPgSqlSchema($database),
            default => [],
        };
    }

    private function loadMySqlSchema(string $database): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$database]
        );

        return $this->groupSchemaRows($rows, 'TABLE_NAME', 'COLUMN_NAME');
    }

    private function loadSqliteSchema(): array
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $grouped = [];

        foreach ($tables as $table) {
            $name = (string) $table->name;
            $columns = DB::select('PRAGMA table_info('.$name.')');

            $grouped[] = [
                'name' => $name,
                'columns' => array_map(fn ($column) => (string) $column->name, $columns),
            ];
        }

        return $grouped;
    }

    private function loadPgSqlSchema(string $database): array
    {
        $rows = DB::select(
            'SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_catalog = ? AND table_schema = ?
             ORDER BY table_name, ordinal_position',
            [$database, 'public']
        );

        return $this->groupSchemaRows($rows, 'table_name', 'column_name');
    }

    private function groupSchemaRows(array $rows, string $tableKey, string $columnKey): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $table = (string) $row->{$tableKey};
            $column = (string) $row->{$columnKey};

            if (! isset($grouped[$table])) {
                $grouped[$table] = [
                    'name' => $table,
                    'columns' => [],
                ];
            }

            $grouped[$table]['columns'][] = $column;
        }

        return array_values($grouped);
    }

    private function singularize(string $value): string
    {
        return match (true) {
            str_ends_with($value, 'ies') => substr($value, 0, -3).'y',
            str_ends_with($value, 's') => substr($value, 0, -1),
            default => $value,
        };
    }

    private function tableNeedles(string $table): array
    {
        $singular = $this->singularize($table);

        return array_values(array_unique(array_filter([
            $table,
            $singular,
            str_replace('_', '', $table),
            str_replace('_', '', $singular),
            strtolower(Str::studly($table)),
            strtolower(Str::studly($singular)),
        ])));
    }

    private function columnNeedles(string $column): array
    {
        return array_values(array_unique(array_filter([
            $column,
            Str::camel($column),
            strtolower(Str::studly($column)),
            str_replace('_', '', $column),
        ])));
    }
}