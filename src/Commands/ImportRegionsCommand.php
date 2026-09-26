<?php

namespace Wsmallnews\Profile\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Profile\Support\Utils;

/**
 * 导入中国四级行政区划数据（AreaCity，国家统计局口径）
 *
 * 数据文件：包内 data/ok_data_level3-4.csv（含全部四级）
 * 更新方式：到 https://github.com/xiangyuecn/AreaCity-JsSpider-StatsGov/releases
 *           下载新版 ok_data_level3-4.csv.7z 解压后 --path= 指定导入
 */
class ImportRegionsCommand extends Command
{
    protected $signature = 'profile:import-regions
                            {--path= : CSV 文件路径（默认导入包内 data/ 附带版本）}
                            {--version= : 数据版本号（默认读包内 data/VERSION）}
                            {--force : 表非空时强制重建导入}';

    protected $description = 'Import China administrative divisions (AreaCity 4-level data) into sn_profile_regions';

    public function handle(): int
    {
        /** @var string $regionModel */
        $regionModel = Utils::getRegionModel();
        $table = (new $regionModel)->getTable();

        $path = $this->option('path') ?: dirname(__DIR__, 2) . '/data/ok_data_level3-4.csv';

        if (! is_file($path)) {
            $this->components->error("CSV file not found: {$path}");

            return self::FAILURE;
        }

        $existing = DB::table($table)->count();

        if ($existing > 0 && ! $this->option('force')) {
            $this->components->warn("{$table} already has {$existing} rows. Use --force to rebuild.");

            return self::SUCCESS;
        }

        $rows = $this->parseCsv($path);

        if ($rows === []) {
            $this->components->error('No valid rows parsed from CSV.');

            return self::FAILURE;
        }

        $imported = DB::transaction(function () use ($table, $rows) {
            // 表只服务选择不承载历史（历史在地址快照），重建最干净
            DB::table($table)->truncate();

            $chunks = array_chunk($rows, 500);

            foreach ($chunks as $chunk) {
                DB::table($table)->insert($chunk);
            }

            return count($rows);
        });

        $version = $this->option('version')
            ?: (is_file(dirname($path) . '/VERSION') ? trim((string) file_get_contents(dirname($path) . '/VERSION')) : '')
            ?: (string) now()->format('Ymd');

        Cache::forever('sn-profile.regions.version', $version);

        $this->components->info("Imported {$imported} regions (version: {$version}).");

        return self::SUCCESS;
    }

    /**
     * 解析 AreaCity CSV（utf-8 BOM，文本限定符 "）
     *
     * @return list<array<string, mixed>>
     */
    protected function parseCsv(string $path): array
    {
        $contents = (string) file_get_contents($path);

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',', '"', '\\');

        if (! is_array($header) || ! in_array('id', $header, true)) {
            fclose($handle);

            return [];
        }

        $rows = [];

        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (count($line) < 8) {
                continue;
            }

            [$id, $pid, $deep, $name, $pinyinPrefix, $pinyin, $extId, $extName] = array_pad(array_map('trim', $line), 8, '');

            if ($id === '' || ! ctype_digit($id)) {
                continue;
            }

            $rows[] = [
                'id' => (int) $id,
                'parent_id' => (int) $pid,
                'level' => (int) $deep + 1,
                'name' => mb_substr($name, 0, 64),
                'full_name' => $extName !== '' ? mb_substr($extName, 0, 128) : null,
                'ext_id' => $extId !== '' ? $extId : null,
                'pinyin_prefix' => $pinyinPrefix !== '' ? mb_substr($pinyinPrefix, 0, 8) : null,
                'pinyin' => $pinyin !== '' ? mb_substr($pinyin, 0, 128) : null,
            ];
        }

        fclose($handle);

        return $rows;
    }
}
