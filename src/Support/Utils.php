<?php

namespace Wsmallnews\Profile\Support;

/**
 * profile 包工具（用户附属资料域：无 scopeable——归属随 owner User/Member，与 user/member 包同构）
 */
class Utils
{
    /**
     * @return array<string, mixed>
     */
    public static function getConfig(?string $name = null, mixed $default = null): mixed
    {
        return config('sn-profile' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * 站点支持的收货国家；null = 全球
     *
     * @return list<string>|null
     */
    public static function getSupportedCountries(): ?array
    {
        $countries = static::getConfig('supported_countries');

        return is_array($countries) ? array_values($countries) : null;
    }

    /**
     * 是否纯国内站（仅支持中国，表单不出国家选择器）
     */
    public static function isChinaOnly(): bool
    {
        $countries = static::getSupportedCountries();

        return $countries === ['CN'];
    }

    public static function getDefaultCountry(): string
    {
        return (string) static::getConfig('default_country', 'CN');
    }

    /**
     * 中国区划级联深度（3 或 4）
     */
    public static function getDivisionLevel(): int
    {
        $level = (int) static::getConfig('china_division_level', 4);

        return in_array($level, [3, 4], true) ? $level : 4;
    }

    /**
     * @return list<string>
     */
    public static function getPostalRequiredCountries(): array
    {
        return (array) static::getConfig('postal_required_countries', []);
    }

    /**
     * 面板注册配置
     *
     * @return array<string, mixed>
     */
    public static function getPanelRegister(?string $type = null): mixed
    {
        return static::getConfig('panel_register' . ($type ? '.' . $type : ''), []);
    }

    public static function getModel(string $name, bool $shouldException = true): ?string
    {
        $model = static::getConfig("models.{$name}");

        if (! $model && $shouldException) {
            throw new \RuntimeException("Profile model [{$name}] is not configured.");
        }

        return $model;
    }

    public static function getAddressModel(): string
    {
        return static::getModel('address');
    }

    public static function getRegionModel(): string
    {
        return static::getModel('region');
    }
}
