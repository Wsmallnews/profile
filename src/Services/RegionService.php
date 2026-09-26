<?php

namespace Wsmallnews\Profile\Services;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Wsmallnews\Profile\Models\Region;
use Wsmallnews\Profile\Support\Utils;

/**
 * 区划数据服务：按国别路由双 provider
 *
 * CN   → sn_profile_regions 表（AreaCity 四级数据，深度受 china_division_level 配置约束）
 * 其他 → commerceguys/addressing 包内离线数据（CLDR + Google 地址数据）
 *
 * 返回结构统一为 [{code, name, has_children}]，表单字段与 HTTP API 共用。
 */
class RegionService
{
    public function __construct(
        protected CountryRepository $countryRepository = new CountryRepository,
        protected SubdivisionRepository $subdivisionRepository = new SubdivisionRepository,
    ) {}

    /**
     * 支持的国家列表（code => 本地化名称），受 supported_countries 配置过滤
     *
     * @return array<string, string>
     */
    public function countries(): array
    {
        $list = $this->countryRepository->getList($this->locale());

        $supported = Utils::getSupportedCountries();

        if ($supported === null) {
            return $list;
        }

        // 保持配置声明顺序展示
        return collect($supported)
            ->filter(fn (string $code) => isset($list[$code]))
            ->mapWithKeys(fn (string $code) => [$code => $list[$code]])
            ->all();
    }

    public function isCountrySupported(string $countryCode): bool
    {
        $supported = Utils::getSupportedCountries();

        return $supported === null || in_array(strtoupper($countryCode), $supported, true);
    }

    /**
     * 下一级区划列表
     *
     * @param  list<string>  $parents  已选父级链（不含国家码；CN 取末位作 parent_id）
     * @return list<array{code: string, name: string, has_children: bool}>
     */
    public function divisions(?string $countryCode = null, array $parents = []): array
    {
        $countryCode = strtoupper($countryCode ?: Utils::getDefaultCountry());

        if (! $this->isCountrySupported($countryCode)) {
            return [];
        }

        return $countryCode === 'CN'
            ? $this->chinaDivisions($parents)
            : $this->intlDivisions($countryCode, $parents);
    }

    /**
     * 国家区划级联深度（CN 受配置约束；国际按库内元数据，最多三级）
     */
    public function divisionDepth(string $countryCode): int
    {
        return strtoupper($countryCode) === 'CN'
            ? Utils::getDivisionLevel()
            : 3;
    }

    /**
     * commerceguys 国家列表的 locale（app locale 下划线转连字符，失配时库内回落英文）
     */
    public function locale(): string
    {
        return str_replace('_', '-', app()->getLocale());
    }

    /**
     * 中国区划（sn_profile_regions 表）
     *
     * @param  list<string>  $parents
     * @return list<array{code: string, name: string, has_children: bool}>
     */
    protected function chinaDivisions(array $parents): array
    {
        $parentId = $parents === [] ? 0 : (int) end($parents);
        $maxLevel = Utils::getDivisionLevel();

        /** @var Region $regionModel */
        $regionModel = app(Utils::getRegionModel());

        $rows = $regionModel::query()
            ->where('parent_id', $parentId)
            ->orderBy('id')
            ->get(['id', 'name', 'full_name', 'level']);

        if ($rows->isEmpty()) {
            return [];
        }

        // 批量判断是否有下级（一条 whereIn 查询代替逐行 exists）
        $withChildren = $rows->every(fn ($row) => $row->level >= $maxLevel)
            ? collect()
            : $regionModel::query()
                ->whereIn('parent_id', $rows->pluck('id'))
                ->where('level', '<=', $maxLevel)
                ->distinct()
                ->pluck('parent_id');

        return $rows
            ->map(fn ($row) => [
                'code' => (string) $row->id,
                'name' => $row->full_name ?: $row->name,
                'has_children' => $row->level < $maxLevel && $withChildren->contains($row->id),
            ])
            ->values()
            ->all();
    }

    /**
     * 国际区划（commerceguys/addressing 包内数据）
     *
     * @param  list<string>  $parents
     * @return list<array{code: string, name: string, has_children: bool}>
     */
    protected function intlDivisions(string $countryCode, array $parents): array
    {
        $chain = array_merge([$countryCode], $parents);

        $subdivisions = $this->subdivisionRepository->getAll($chain);

        return collect($subdivisions)
            ->map(fn ($subdivision) => [
                'code' => $subdivision->getLocalCode() ?: $subdivision->getCode(),
                'name' => $subdivision->getLocalName() ?: $subdivision->getName(),
                'has_children' => $subdivision->hasChildren(),
            ])
            ->values()
            ->all();
    }
}
