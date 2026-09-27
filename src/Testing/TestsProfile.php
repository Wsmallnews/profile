<?php

namespace Wsmallnews\Profile\Testing;

use Illuminate\Support\Facades\DB;
use Wsmallnews\Profile\Support\Utils;

/**
 * 测试辅助：区划样例数据（与 AreaCity 真实区划码一致）
 */
trait TestsProfile
{
    /**
     * 种入覆盖关键场景的中国区划样例：
     * 常规四级链（广东-深圳-南山-南头街道）、直辖市（北京-市辖区-东城区）、
     * 直筒子市（东莞市-东莞补齐行-东城街道）
     */
    protected function seedRegions(): void
    {
        $rows = [
            ['id' => 44, 'parent_id' => 0, 'level' => 1, 'name' => '广东', 'full_name' => '广东省', 'ext_id' => '440000000000'],
            ['id' => 11, 'parent_id' => 0, 'level' => 1, 'name' => '北京', 'full_name' => '北京市', 'ext_id' => '110000000000'],
            ['id' => 4403, 'parent_id' => 44, 'level' => 2, 'name' => '深圳', 'full_name' => '深圳市', 'ext_id' => '440300000000'],
            ['id' => 4419, 'parent_id' => 44, 'level' => 2, 'name' => '东莞', 'full_name' => '东莞市', 'ext_id' => '441900000000'],
            ['id' => 1101, 'parent_id' => 11, 'level' => 2, 'name' => '市辖区', 'full_name' => '北京市', 'ext_id' => '110100000000'],
            ['id' => 440305, 'parent_id' => 4403, 'level' => 3, 'name' => '南山', 'full_name' => '南山区', 'ext_id' => '440305000000'],
            ['id' => 441900, 'parent_id' => 4419, 'level' => 3, 'name' => '东莞', 'full_name' => '东莞市', 'ext_id' => '441900000000'],
            ['id' => 110101, 'parent_id' => 1101, 'level' => 3, 'name' => '东城', 'full_name' => '东城区', 'ext_id' => '110101000000'],
            ['id' => 440305001, 'parent_id' => 440305, 'level' => 4, 'name' => '南头', 'full_name' => '南头街道', 'ext_id' => '440305001000'],
            ['id' => 441900003, 'parent_id' => 441900, 'level' => 4, 'name' => '东城', 'full_name' => '东城街道', 'ext_id' => '441900003000'],
        ];

        $table = (new (Utils::getRegionModel()))->getTable();

        foreach ($rows as $row) {
            DB::table($table)->insert(array_merge([
                'pinyin_prefix' => null,
                'pinyin' => null,
            ], $row));
        }
    }
}
