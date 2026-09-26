<?php

namespace Wsmallnews\Profile\Filament\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Wsmallnews\Profile\Filament\Forms\Fields\RegionCascade;
use Wsmallnews\Profile\Support\Utils;

/**
 * 收货地址表单 schema（新增 / 编辑共用）
 *
 * 数据流：RegionCascade 的 state（region 键）在保存前经 AddressForm::mutate() 拍平为模型列。
 */
class AddressForm
{
    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('consignee')
                        ->label(__('sn-profile::profile.address.consignee'))
                        ->placeholder(__('sn-profile::profile.address.consignee_placeholder'))
                        ->maxLength(64)
                        ->required(),

                    TextInput::make('phone')
                        ->label(__('sn-profile::profile.address.phone'))
                        ->placeholder(__('sn-profile::profile.address.phone_placeholder'))
                        ->maxLength(32)
                        ->required()
                        ->rule(fn (Get $get) => static::countryOf($get) === 'CN'
                            ? 'regex:/^1[3-9]\d{9}$/'
                            : 'nullable'),

                    RegionCascade::make('region')
                        ->label(__('sn-profile::profile.address.region'))
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('address_line1')
                        ->label(__('sn-profile::profile.address.address_line1'))
                        ->placeholder(__('sn-profile::profile.address.address_line1_placeholder'))
                        ->maxLength(255)
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('address_line2')
                        ->label(__('sn-profile::profile.address.address_line2'))
                        ->placeholder(__('sn-profile::profile.address.address_line2_placeholder'))
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('postal_code')
                        ->label(__('sn-profile::profile.address.postal_code'))
                        ->placeholder(__('sn-profile::profile.address.postal_code_placeholder'))
                        ->maxLength(20)
                        ->required(fn (Get $get) => in_array(static::countryOf($get), Utils::getPostalRequiredCountries(), true)),

                    TextInput::make('label')
                        ->label(__('sn-profile::profile.address.label'))
                        ->placeholder(__('sn-profile::profile.address.label_placeholder'))
                        ->maxLength(32),

                    Toggle::make('is_default')
                        ->label(__('sn-profile::profile.address.is_default'))
                        ->inline(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * 表单 data（含 region 键）→ 模型列（region 拍平为四级 code+name 列）
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutate(array $data): array
    {
        $region = $data['region'] ?? null;

        unset($data['region']);

        if (! is_array($region)) {
            return $data;
        }

        return array_merge($data, RegionCascade::flattenToColumns($region));
    }

    /**
     * 从表单 state 取当前国家码（region 为兄弟字段）
     */
    protected static function countryOf(Get $get): string
    {
        $region = $get('region');

        return strtoupper((string) (($region['country'] ?? null) ?: Utils::getDefaultCountry()));
    }
}
