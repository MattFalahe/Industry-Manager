<?php

namespace IndustryManager\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use IndustryManager\Helpers\IndustryData;

/**
 * Imports ONLY the industry + planetary recipe SDE tables Industry Manager
 * needs, straight from Fuzzwork's `latest/` dump — decoupled from SeAT's core
 * `eve:update:sde`.
 *
 * Why a dedicated command instead of registerSdeTables():
 *   - eve:update:sde re-downloads the ENTIRE core SDE (heavy) and is coupled
 *     to a version-pinned Fuzzwork path that can 404 (e.g. chrFactions on a
 *     rotated version). This command pulls only our ~7 tables from the stable
 *     `latest/` directory, so it can't be broken by the core SDE's path.
 *   - It's a command we own, which keeps operator-facing instructions citing
 *     only our own commands.
 *
 * Re-runnable: each table is dropped and recreated, so this also refreshes the
 * recipe data after an EVE patch.
 */
class ImportSdeCommand extends Command
{
    protected $signature = 'industry-manager:import-sde {--keep-files : keep the downloaded CSVs in storage}';

    protected $description = 'Download + import the industry & planetary recipe SDE tables Industry Manager needs (from Fuzzwork latest). Independent of SeAT core SDE.';

    private string $base = 'https://www.fuzzwork.co.uk/dump/latest/';

    /**
     * Per-table column defs + indexes. Column types: int / big / double /
     * string. Columns are matched to CSV headers by NAME, so column reordering
     * in the dump is tolerated.
     */
    private function manifest(): array
    {
        return [
            IndustryData::TABLE_ACTIVITY => [
                'columns' => ['typeID' => 'int', 'activityID' => 'int', 'time' => 'big'],
                'indexes' => [['typeID', 'activityID']],
            ],
            IndustryData::TABLE_MATERIALS => [
                'columns' => ['typeID' => 'int', 'activityID' => 'int', 'materialTypeID' => 'int', 'quantity' => 'big'],
                'indexes' => [['typeID', 'activityID'], ['materialTypeID']],
            ],
            IndustryData::TABLE_PRODUCTS => [
                'columns' => ['typeID' => 'int', 'activityID' => 'int', 'productTypeID' => 'int', 'quantity' => 'big'],
                'indexes' => [['typeID', 'activityID'], ['productTypeID']],
            ],
            IndustryData::TABLE_SKILLS => [
                'columns' => ['typeID' => 'int', 'activityID' => 'int', 'skillID' => 'int', 'level' => 'int'],
                'indexes' => [['typeID', 'activityID']],
            ],
            IndustryData::TABLE_PROBABILITIES => [
                'columns' => ['typeID' => 'int', 'activityID' => 'int', 'productTypeID' => 'int', 'probability' => 'double'],
                'indexes' => [['typeID', 'activityID']],
            ],
            IndustryData::TABLE_PI_SCHEMATICS => [
                'columns' => ['schematicID' => 'int', 'schematicName' => 'string', 'cycleTime' => 'int'],
                'indexes' => [['schematicID']],
            ],
            IndustryData::TABLE_PI_TYPEMAP => [
                'columns' => ['schematicID' => 'int', 'typeID' => 'int', 'quantity' => 'big', 'isInput' => 'int'],
                'indexes' => [['schematicID'], ['typeID'], ['isInput']],
            ],
        ];
    }

    public function handle(): int
    {
        if (! function_exists('bzopen')) {
            $this->error('The PHP bz2 extension is required to decompress the SDE dumps but is not loaded.');

            return self::FAILURE;
        }

        $storage = storage_path('sde/industry-manager/');
        if (! File::exists($storage)) {
            File::makeDirectory($storage, 0755, true);
        }

        $client = new Client(['timeout' => 600, 'connect_timeout' => 30]);
        $failures = 0;

        foreach ($this->manifest() as $table => $def) {
            $this->line('');
            $this->info("=> {$table}");

            $bz = $storage . $table . '.csv.bz2';
            $csv = $storage . $table . '.csv';

            if (! $this->download($client, $this->base . $table . '.csv.bz2', $bz)) {
                $failures++;
                continue;
            }

            try {
                $this->decompress($bz, $csv);
                $count = $this->importCsv($table, $def, $csv);
                $this->line("   imported {$count} rows");
            } catch (\Throwable $e) {
                $this->error('   failed: ' . $e->getMessage());
                $failures++;
            }

            if (! $this->option('keep-files')) {
                @unlink($bz);
                @unlink($csv);
            }
        }

        IndustryData::flush();

        // Stamp a fresh recipe version so cached recipes (keyed by
        // IndustryData::recipeVersion) are invalidated on this re-import.
        try {
            \Seat\Services\Settings\Seat::set('industry_manager_sde_version', (string) time());
        } catch (\Throwable $e) {
            // non-fatal; recipes will still refresh once core SDE version changes
        }

        $this->line('');
        if ($failures > 0) {
            $this->warn("Completed with {$failures} table(s) failing. Industry Manager will still work for whatever imported; re-run to retry.");

            return self::FAILURE;
        }

        $this->info('Industry Manager recipe data imported successfully.');

        return self::SUCCESS;
    }

    private function download(Client $client, string $url, string $destination): bool
    {
        try {
            $fh = fopen($destination, 'w');
            $res = $client->request('GET', $url, ['sink' => $fh]);
            fclose($fh);

            if ($res->getStatusCode() !== 200) {
                $this->error('   download failed (' . $res->getStatusCode() . '): ' . $url);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->error('   download error: ' . $e->getMessage());

            return false;
        }
    }

    private function decompress(string $src, string $dst): void
    {
        $in = bzopen($src, 'r');
        if ($in === false) {
            throw new \RuntimeException('could not open archive ' . $src);
        }
        $out = fopen($dst, 'w');
        while (($chunk = bzread($in, 8192)) !== false && $chunk !== '') {
            fwrite($out, $chunk);
        }
        bzclose($in);
        fclose($out);
    }

    private function importCsv(string $table, array $def, string $csv): int
    {
        if (! File::exists($csv)) {
            throw new \RuntimeException('decompressed CSV missing');
        }

        Schema::dropIfExists($table);
        Schema::create($table, function (Blueprint $t) use ($def) {
            foreach ($def['columns'] as $col => $type) {
                match ($type) {
                    'int' => $t->integer($col)->nullable(),
                    'big' => $t->bigInteger($col)->nullable(),
                    'double' => $t->double($col)->nullable(),
                    default => $t->string($col, 255)->nullable(),
                };
            }
            foreach (($def['indexes'] ?? []) as $cols) {
                $t->index($cols);
            }
        });

        $handle = fopen($csv, 'r');
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return 0;
        }

        // Map each wanted column to its position in the CSV header (by name).
        $idx = [];
        foreach (array_keys($def['columns']) as $col) {
            $pos = array_search($col, $header, true);
            $idx[$col] = $pos === false ? null : $pos;
        }

        $batch = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $record = [];
            foreach ($def['columns'] as $col => $type) {
                $v = ($idx[$col] !== null && isset($row[$idx[$col]])) ? $row[$idx[$col]] : null;
                if ($v === '' || $v === 'None' || $v === 'NULL') {
                    $v = null;
                }
                $record[$col] = $v;
            }
            $batch[] = $record;
            $count++;

            if (count($batch) >= 1000) {
                DB::table($table)->insert($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) {
            DB::table($table)->insert($batch);
        }
        fclose($handle);

        return $count;
    }
}
