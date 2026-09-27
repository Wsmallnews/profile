<?php

namespace Wsmallnews\Profile\Filament\Address\Forms\Fields;

use Closure;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\CanBeDisabled;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Profile\Services\RegionService;
use Wsmallnews\Profile\Support\Utils;

/**
 * 收货地址区划级联字段（微信 / 淘宝 / 京东 同款「输入框 + 弹层逐级 Tab」形态）
 *
 * state 结构：['country' => 'CN', 'chain' => [['code' => '440000', 'name' => '广东省'], ...]]
 *
 * - 中国：sn_profile_regions 四级数据（深度受 china_division_level / ->depth() 约束）
 * - 国际：commerceguys/addressing 包内数据（动态深度，无下级即停）
 * - 单国站点自动隐藏国家选择器；写库用 RegionCascade::flattenToColumns() 拍平列
 */
class RegionCascade extends Field
{
    use CanBeDisabled;
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;

    protected string $view = 'sn-profile::filament.address.forms.fields.region-cascade';

    protected ?int $depth = null;

    protected ?array $countries = null;

    protected ?bool $withCountry = null;

    protected function setUp(): void
    {
        $this->afterStateHydrated(function (RegionCascade $component, ?array $state) {
            // 非 null 的 state 来自 formatStateUsing（关联桥接场景，如订单地址编辑），原样保留
            if ($state !== null) {
                $component->state($state);

                return;
            }

            $record = $component->getRecord();

            if ($record) {
                $component->state(static::hydrateFromColumns($record));

                return;
            }

            $component->state([
                'country' => $component->getDefaultCountry(),
                'chain' => [],
            ]);
        });

        // 选到的最后一级必须是叶子（无下级区划），防止中途截断
        $this->rule(static function (RegionCascade $component) {
            return function (string $attribute, mixed $value, Closure $fail) {
                if (! is_array($value) || ($value['chain'] ?? []) === []) {
                    return; // 空值交由 required 处理
                }

                $divisions = app(RegionService::class)->divisions(
                    countryCode: $value['country'] ?? null,
                    parents: collect($value['chain'])->pluck('code')->all(),
                );

                if ($divisions !== []) {
                    $fail(__('sn-profile::profile.errors.region_incomplete'));
                }
            };
        });
    }

    public function depth(?int $depth): static
    {
        $this->depth = $depth;

        return $this;
    }

    public function countries(?array $countries): static
    {
        $this->countries = $countries;

        return $this;
    }

    public function withCountry(?bool $withCountry = true): static
    {
        $this->withCountry = $withCountry;

        return $this;
    }

    /**
     * 供表单验证闭包取当前国家
     */
    public function getCountryOfState(?array $state = null): string
    {
        $state ??= $this->getState();

        return strtoupper((string) ($state['country'] ?? $this->getDefaultCountry()));
    }

    /**
     * @return array<string, string>
     */
    public function getCountries(): array
    {
        $countries = $this->countries ?? Utils::getSupportedCountries();

        if ($countries === null) {
            return app(RegionService::class)->countries();
        }

        $all = app(RegionService::class)->countries();

        return collect($countries)
            ->filter(fn (string $code) => isset($all[$code]))
            ->mapWithKeys(fn (string $code) => [$code => $all[$code]])
            ->all();
    }

    /**
     * 是否展示国家选择器（显式配置优先；缺省 = 支持国家数 > 1）
     */
    public function isWithCountry(): bool
    {
        return $this->withCountry ?? count($this->getCountries()) > 1;
    }

    public function getDefaultCountry(): string
    {
        $countries = array_keys($this->getCountries());

        return in_array(Utils::getDefaultCountry(), $countries, true)
            ? Utils::getDefaultCountry()
            : ($countries[0] ?? 'CN');
    }

    /**
     * 级联层级标签（CN：省/市/区县/乡镇街道；国际：州省/城市/区县）
     *
     * @return list<string>
     */
    public function getLevelLabels(?string $countryCode = null): array
    {
        $countryCode = strtoupper($countryCode ?: $this->getDefaultCountry());

        if ($countryCode === 'CN') {
            $depth = $this->depth ?? Utils::getDivisionLevel();

            return collect([
                __('sn-profile::profile.region.level_1'),
                __('sn-profile::profile.region.level_2'),
                __('sn-profile::profile.region.level_3'),
                __('sn-profile::profile.region.level_4'),
            ])->slice(0, $depth)->values()->all();
        }

        return [
            __('sn-profile::profile.region.intl_level_1'),
            __('sn-profile::profile.region.intl_level_2'),
            __('sn-profile::profile.region.intl_level_3'),
        ];
    }

    /**
     * 默认国家的一级区划（随表单渲染注入，面板首开零请求）
     *
     * @return list<array{code: string, name: string, has_children: bool}>
     */
    public function getInitialDivisions(): array
    {
        return app(RegionService::class)->divisions($this->getDefaultCountry());
    }

    /**
     * 模型列 → 字段 state
     *
     * @return array{country: string, chain: list<array{code: string, name: string}>}
     */
    public static function hydrateFromColumns(Model $record): array
    {
        $chain = [];

        foreach (static::columnPairs() as [$codeColumn, $nameColumn]) {
            if (filled($record->{$codeColumn})) {
                $chain[] = [
                    'code' => (string) $record->{$codeColumn},
                    'name' => (string) $record->{$nameColumn},
                ];
            }
        }

        return [
            'country' => (string) ($record->country_code ?? 'CN'),
            'chain' => $chain,
        ];
    }

    /**
     * 字段 state → 模型列（拍平）
     *
     * @param  array{country?: string, chain?: list<array{code: string, name: string}>}|null  $state
     * @return array<string, mixed>
     */
    public static function flattenToColumns(?array $state): array
    {
        $chain = collect($state['chain'] ?? []);

        $columns = ['country_code' => strtoupper((string) ($state['country'] ?? 'CN'))];

        foreach (array_values(static::columnPairs()) as $index => [$codeColumn, $nameColumn]) {
            $node = $chain->get($index);

            $columns[$codeColumn] = $node['code'] ?? null;
            $columns[$nameColumn] = $node['name'] ?? null;
        }

        return $columns;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    protected static function columnPairs(): array
    {
        return [
            ['administrative_area', 'administrative_area_name'],
            ['locality', 'locality_name'],
            ['dependent_locality', 'dependent_locality_name'],
            ['township', 'township_name'],
        ];
    }
}
