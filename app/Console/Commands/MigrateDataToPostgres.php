<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateDataToPostgres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:transfer-to-postgres';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer all application data from SQLite to the PostgreSQL database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting data transfer from SQLite to PostgreSQL...');

        $sqlitePath = database_path('database.sqlite');
        if (! file_exists($sqlitePath)) {
            $this->error("SQLite database file not found at: {$sqlitePath}");

            return Command::FAILURE;
        }

        // Explicitly set SQLite database path so DB_DATABASE=postgres in .env doesn't override it
        config(['database.connections.sqlite.database' => $sqlitePath]);
        DB::purge('sqlite');

        // Ordered list of tables to ensure proper foreign key order
        $tables = [
            'users',
            'departments',
            'salary_grades',
            'company_settings',
            'employees',
            'attendances',
            'trip_incomes',
            'performance_bonuses',
            'payroll_batches',
            'salary_computations',
            'compensation_adjustments',
            'claims',
            'hmo_enrollments',
            'accident_claims',
            'budget_requisitions',
            'payroll_audit_trails',
            'ai_compliance_logs',
            'thirteenth_month_batches',
            'thirteenth_month_computations',
        ];

        // Disable foreign key checks for PostgreSQL session during import
        try {
            DB::connection('pgsql')->statement("SET session_replication_role = 'replica';");
        } catch (\Throwable $e) {
            $this->warn('Could not set session_replication_role, proceeding with ordered delete.');
        }

        // Truncate PostgreSQL tables in reverse order first
        $reverseTables = array_reverse($tables);
        foreach ($reverseTables as $table) {
            if (Schema::connection('pgsql')->hasTable($table)) {
                DB::connection('pgsql')->table($table)->delete();
            }
        }

        foreach ($tables as $table) {
            if (! Schema::connection('sqlite')->hasTable($table)) {
                $this->warn("Table '{$table}' does not exist in SQLite database. Skipping.");

                continue;
            }

            $count = DB::connection('sqlite')->table($table)->count();
            if ($count === 0) {
                $this->line("Table '{$table}' has 0 records in SQLite. Skipping.");

                continue;
            }

            $this->info("Transferring {$count} records from '{$table}'...");

            $records = DB::connection('sqlite')->table($table)->get();

            $chunkSize = 250;
            $chunks = array_chunk($records->map(fn ($item) => (array) $item)->toArray(), $chunkSize);

            foreach ($chunks as $chunk) {
                DB::connection('pgsql')->table($table)->insert($chunk);
            }

            // Sync PostgreSQL auto-increment sequence if table has 'id' column
            if (Schema::connection('pgsql')->hasColumn($table, 'id')) {
                try {
                    DB::connection('pgsql')->statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1)) FROM {$table};");
                } catch (\Throwable $e) {
                    // Ignore sequence sync errors for non-serial PKs
                }
            }

            $this->info("Successfully transferred '{$table}'.");
        }

        // Re-enable foreign key checks for PostgreSQL session
        try {
            DB::connection('pgsql')->statement("SET session_replication_role = 'origin';");
        } catch (\Throwable $e) {
            // Ignore
        }

        $this->info('Data transfer completed successfully!');

        return Command::SUCCESS;
    }
}
