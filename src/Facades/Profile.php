<?php

namespace Wsmallnews\Profile\Facades;

use Illuminate\Support\Facades\Facade;
use Wsmallnews\Profile\Services\RegionService;

/**
 * @method static array<string, string> countries()                                   支持的国家列表（code => 本地化名称）
 * @method static bool isCountrySupported(string $countryCode)                        国家是否在支持范围
 * @method static array<int, array{code: string, name: string, has_children: bool}> divisions(?string $countryCode = null, string|int|null $parent = null) 下一级区划列表
 * @method static int divisionDepth(string $countryCode)                              国家区划级联深度（UI 预分配 tab 用）
 * @method static string locale()
 *
 * @see \Wsmallnews\Profile\Services\RegionService
 */
class Profile extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sn-profile';
    }
}
