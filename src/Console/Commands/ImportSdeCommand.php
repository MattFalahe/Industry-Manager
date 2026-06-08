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
 * Imports the industry + planetary recipe data Industry Manager needs from
 * CCP's OFFICIAL JSONL SDE (blueprints.jsonl + planetSchematics.jsonl),
 * flattening CCP's nested structure into the flat tables the plugin queries.
 *
 * Source priority (CCP, never Fuzzwork — Fuzzwork lags patches):
 *   1. Re-use the files SeAT already extracted under storage/sde/ when it
 *      seeded the CCP SDE (zero download, always matches SeAT's SDE version).
 *   2. Otherwise download CCP's `eve-online-static-data-latest-jsonl.zip` and
 *      extract just those two files.
 *
 * Re-runnable: each target table is dropped + recreated, so this refreshes the
 * recipe data after an EVE patch. Independent of SeAT core's `eve:update:sde`.
 */
class ImportSdeCommand extends Command
{
    protected $signature = 'industry-manager:import-sde {--download : force downloading CCP\'s SDE zip even if local files exist} {--keep-files : keep downloaded/extracted files}';

    protected $description = 'Import EVE industry & planetary recipes from CCP\'s official JSONL SDE (blueprints.jsonl + planetSchematics.jsonl). Independent of SeAT core SDE.';

    private const CCP_ZIP_URL = 'https://developers.eveonline.com/static-data/tranquility/eve-online-static-data-latest-jsonl.zip';

    /**
     * CCP activity name -> EVE activityID (the value the rest of the plugin
     * filters on). research_material = ME (4), research_time = TE (3).
     */
    private const ACTIVITY_MAP = [
        'manufacturing' => 1,
        'research_time' => 3,
        'research_material' => 4,
        'copying' => 5,
        'invention' => 8,
        'reaction' => 11,
    ];

    private string $work;

    public function handle(): int
    {
        $this->work = storage_path('sde/industry-manager/');
        if (! File::exists($this->work)) {
            File::makeDirectory($this->work, 0755, true);
        }

        [$blueprintsFile, $schematicsFile, $downloaded] = $this->locateFiles();

        if (! $blueprintsFile && ! $schematicsFile) {
            $this->error('Could not find or download CCP SDE files (blueprints.jsonl / planetSchematics.jsonl).');
            $this->line('Run SeAT\'s SDE update first (so the files exist under storage/sde/), or re-run with --download.');

            return self::FAILURE;
        }

        $ok = true;

        if ($blueprintsFile) {
            $this->info('Importing industry recipes from ' . basename($blueprintsFile) . ' …');
            try {
                $this->importBlueprints($blueprintsFile);
            } catch (\Throwable $e) {
                $this->error('  blueprints import failed: ' . $e->getMessage());
                $ok = false;
            }
        } else {
            $this->warn('blueprints.jsonl not found — industry pages will stay empty.');
        }

        if ($schematicsFile) {
            $this->info('Importing planetary schematics from ' . basename($schematicsFile) . ' …');
            try {
                $this->importSchematics($schematicsFile);
            } catch (\Throwable $e) {
                $this->error('  planetSchematics import failed: ' . $e->getMessage());
                $ok = false;
            }
        } else {
            $this->warn('planetSchematics.jsonl not found — PI pages will stay empty.');
        }

        if ($downloaded && ! $this->option('keep-files')) {
            @File::deleteDirectory($this->work);
        }

        IndustryData::flush();

        // Stamp a fresh recipe version so cached recipes (keyed by
        // IndustryData::recipeVersion) are invalidated on this re-import.
        try {
            \Seat\Services\Settings\Seat::set('industry_manager_sde_version', (string) time());
        } catch (\Throwable $e) {
            // non-fatal
        }

        $this->line('');
        if (! $ok) {
            $this->warn('Completed with errors — re-run to retry.');

            return self::FAILURE;
        }

        $this->info('Industry Manager recipe data imported from CCP SDE.');

        return self::SUCCESS;
    }

    /**
     * @return array{0:?string,1:?string,2:bool}  [blueprintsPath, schematicsPath, downloaded]
     */
    private function locateFiles(): array
    {
        if (! $this->option('download')) {
            $bp = $this->findUnderStorage('blueprints.jsonl');
            $ps = $this->findUnderStorage('planetSchematics.jsonl');
            if ($bp || $ps) {
                $this->info('Using SDE files already on disk:');
                if ($bp) {
                    $this->line('  ' . $bp);
                }
                if ($ps) {
                    $this->line('  ' . $ps);
                }

                return [$bp, $ps, false];
            }
            $this->line('No local SDE files found; downloading CCP SDE…');
        }

        return $this->downloadAndExtract();
    }

    /**
     * Recursively find the newest file with the given basename under
     * storage/sde/ (case-insensitive). SeAT extracts CCP JSONL files there.
     */
    private function findUnderStorage(string $basename): ?string
    {
        $root = storage_path('sde');
        if (! File::isDirectory($root)) {
            return null;
        }

        $best = null;
        $bestMtime = -1;
        foreach (File::allFiles($root) as $file) {
            if (strcasecmp($file->getFilename(), $basename) === 0) {
                $m = $file->getMTime();
                if ($m > $bestMtime) {
                    $bestMtime = $m;
                    $best = $file->getPathname();
                }
            }
        }

        return $best;
    }

    /**
     * @return array{0:?string,1:?string,2:bool}
     */
    private function downloadAndExtract(): array
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->error('The PHP zip extension is required to extract CCP\'s SDE but is not loaded.');

            return [null, null, false];
        }

        $zipPath = $this->work . 'ccp-sde.zip';
        $this->line('Downloading ' . self::CCP_ZIP_URL);

        try {
            $client = new Client(['timeout' => 1200, 'connect_timeout' => 30]);
            $fh = fopen($zipPath, 'w');
            $res = $client->request('GET', self::CCP_ZIP_URL, [
                'sink' => $fh,
                'headers' => ['User-Agent' => 'IndustryManager-SeAT-plugin'],
            ]);
            fclose($fh);
            if ($res->getStatusCode() !== 200) {
                $this->error('  download failed: HTTP ' . $res->getStatusCode());

                return [null, null, true];
            }
        } catch (\Throwable $e) {
            $this->error('  download error: ' . $e->getMessage());

            return [null, null, true];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('  could not open downloaded zip.');

            return [null, null, true];
        }

        $wanted = ['blueprints.jsonl', 'planetSchematics.jsonl'];
        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->statIndex($i)['name'];
            foreach ($wanted as $w) {
                if (strcasecmp(basename($name), $w) === 0) {
                    $zip->extractTo($this->work, $name);
                    $found[$w] = $this->work . $name;
                }
            }
        }
        $zip->close();
        @unlink($zipPath);

        return [$found['blueprints.jsonl'] ?? null, $found['planetSchematics.jsonl'] ?? null, true];
    }

    // ----------------------------------------------------------------------
    // Blueprints -> industryActivity / Materials / Products / Skills / Probabilities
    // ----------------------------------------------------------------------

    private function importBlueprints(string $path): void
    {
        $this->createTable(IndustryData::TABLE_ACTIVITY, ['typeID' => 'int', 'activityID' => 'int', 'time' => 'big'], [['typeID', 'activityID']]);
        $this->createTable(IndustryData::TABLE_MATERIALS, ['typeID' => 'int', 'activityID' => 'int', 'materialTypeID' => 'int', 'quantity' => 'big'], [['typeID', 'activityID'], ['materialTypeID']]);
        $this->createTable(IndustryData::TABLE_PRODUCTS, ['typeID' => 'int', 'activityID' => 'int', 'productTypeID' => 'int', 'quantity' => 'big'], [['typeID', 'activityID'], ['productTypeID']]);
        $this->createTable(IndustryData::TABLE_SKILLS, ['typeID' => 'int', 'activityID' => 'int', 'skillID' => 'int', 'level' => 'int'], [['typeID', 'activityID']]);
        $this->createTable(IndustryData::TABLE_PROBABILITIES, ['typeID' => 'int', 'activityID' => 'int', 'productTypeID' => 'int', 'probability' => 'double'], [['typeID', 'activityID']]);

        $buf = [
            IndustryData::TABLE_ACTIVITY => [],
            IndustryData::TABLE_MATERIALS => [],
            IndustryData::TABLE_PRODUCTS => [],
            IndustryData::TABLE_SKILLS => [],
            IndustryData::TABLE_PROBABILITIES => [],
        ];
        $flush = function (bool $force = false) use (&$buf) {
            foreach ($buf as $table => $rows) {
                if (count($rows) >= 1000 || ($force && $rows)) {
                    DB::table($table)->insert($rows);
                    $buf[$table] = [];
                }
            }
        };

        $handle = fopen($path, 'r');
        $count = 0;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $bp = json_decode($line, true);
            if (! is_array($bp)) {
                continue;
            }
            $bpId = (int) ($bp['_key'] ?? $bp['blueprintTypeID'] ?? 0);
            $activities = $bp['activities'] ?? [];
            if (! $bpId || ! is_array($activities)) {
                continue;
            }

            foreach ($activities as $actName => $act) {
                $activityId = self::ACTIVITY_MAP[$actName] ?? null;
                if ($activityId === null || ! is_array($act)) {
                    continue;
                }

                // time: CCP blueprint activity time is in seconds (matches the
                // long-established blueprints.yaml convention used by every
                // industry tool). Stored as-is.
                $buf[IndustryData::TABLE_ACTIVITY][] = [
                    'typeID' => $bpId, 'activityID' => $activityId, 'time' => (int) ($act['time'] ?? 0),
                ];

                foreach (($act['materials'] ?? []) as $m) {
                    $buf[IndustryData::TABLE_MATERIALS][] = [
                        'typeID' => $bpId, 'activityID' => $activityId,
                        'materialTypeID' => (int) ($m['typeID'] ?? 0), 'quantity' => (int) ($m['quantity'] ?? 0),
                    ];
                }
                foreach (($act['products'] ?? []) as $p) {
                    $pid = (int) ($p['typeID'] ?? 0);
                    $buf[IndustryData::TABLE_PRODUCTS][] = [
                        'typeID' => $bpId, 'activityID' => $activityId,
                        'productTypeID' => $pid, 'quantity' => (int) ($p['quantity'] ?? 1),
                    ];
                    if (isset($p['probability']) && $p['probability'] !== null) {
                        $buf[IndustryData::TABLE_PROBABILITIES][] = [
                            'typeID' => $bpId, 'activityID' => $activityId,
                            'productTypeID' => $pid, 'probability' => (float) $p['probability'],
                        ];
                    }
                }
                foreach (($act['skills'] ?? []) as $s) {
                    $buf[IndustryData::TABLE_SKILLS][] = [
                        'typeID' => $bpId, 'activityID' => $activityId,
                        'skillID' => (int) ($s['typeID'] ?? 0), 'level' => (int) ($s['level'] ?? 0),
                    ];
                }
            }

            $count++;
            $flush();
        }
        $flush(true);
        fclose($handle);

        $this->line("  parsed {$count} blueprints");
    }

    // ----------------------------------------------------------------------
    // planetSchematics -> planetSchematics / planetSchematicsTypeMap
    // ----------------------------------------------------------------------

    private function importSchematics(string $path): void
    {
        $this->createTable(IndustryData::TABLE_PI_SCHEMATICS, ['schematicID' => 'int', 'schematicName' => 'string', 'cycleTime' => 'int'], [['schematicID']]);
        $this->createTable(IndustryData::TABLE_PI_TYPEMAP, ['schematicID' => 'int', 'typeID' => 'int', 'quantity' => 'big', 'isInput' => 'int'], [['schematicID'], ['typeID'], ['isInput']]);

        $schBuf = [];
        $mapBuf = [];
        $handle = fopen($path, 'r');
        $count = 0;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $s = json_decode($line, true);
            if (! is_array($s)) {
                continue;
            }
            $sid = (int) ($s['_key'] ?? 0);
            if (! $sid) {
                continue;
            }

            $name = $s['name']['en'] ?? ($s['schematicName'] ?? ('Schematic #' . $sid));
            $schBuf[] = [
                'schematicID' => $sid,
                'schematicName' => mb_substr((string) $name, 0, 255),
                'cycleTime' => (int) ($s['cycleTime'] ?? 0),
            ];

            foreach (($s['types'] ?? []) as $t) {
                // CCP "types" is an array of {_key, isInput, quantity}.
                $typeId = (int) ($t['_key'] ?? $t['typeID'] ?? 0);
                if (! $typeId) {
                    continue;
                }
                $mapBuf[] = [
                    'schematicID' => $sid,
                    'typeID' => $typeId,
                    'quantity' => (int) ($t['quantity'] ?? 0),
                    'isInput' => ! empty($t['isInput']) ? 1 : 0,
                ];
            }

            if (count($schBuf) >= 500) {
                DB::table(IndustryData::TABLE_PI_SCHEMATICS)->insert($schBuf);
                $schBuf = [];
            }
            if (count($mapBuf) >= 500) {
                DB::table(IndustryData::TABLE_PI_TYPEMAP)->insert($mapBuf);
                $mapBuf = [];
            }
            $count++;
        }
        if ($schBuf) {
            DB::table(IndustryData::TABLE_PI_SCHEMATICS)->insert($schBuf);
        }
        if ($mapBuf) {
            DB::table(IndustryData::TABLE_PI_TYPEMAP)->insert($mapBuf);
        }
        fclose($handle);

        $this->line("  parsed {$count} schematics");
    }

    // ----------------------------------------------------------------------

    private function createTable(string $name, array $columns, array $indexes): void
    {
        Schema::dropIfExists($name);
        Schema::create($name, function (Blueprint $t) use ($columns, $indexes) {
            foreach ($columns as $col => $type) {
                match ($type) {
                    'int' => $t->integer($col)->nullable(),
                    'big' => $t->bigInteger($col)->nullable(),
                    'double' => $t->double($col)->nullable(),
                    default => $t->string($col, 255)->nullable(),
                };
            }
            foreach ($indexes as $cols) {
                $t->index($cols);
            }
        });
    }
}
