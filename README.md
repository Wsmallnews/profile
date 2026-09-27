# Wsmallnews Profile

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/profile.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/profile)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/profile/tests.yml?branch=v1&label=tests&style=flat-square)](https://github.com/wsmallnews/profile/actions?query=workflow%3Atests+branch%3Av1)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/profile.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/profile)

Wsmallnews 生态的用户附属资料扩展包：收货地址簿（后续发票抬头等用户附属资料同样在此包按 `Filament/<Feature>/`、`Livewire/Components/<Feature>/` 分域扩展）。

## 特性

- **地址簿**：owner 多态挂 User（跨租户共享，team_id 为空）或 Member（租户内隔离）；同一 owner 默认地址唯一；四级区划 code + 名称快照成对落列，历史展示不随区划数据更新漂移
- **区划双 Provider**：中国走 `sn_profile_regions`（AreaCity 国家统计局口径四级离线数据，42,826 行），国际走 commerceguys/addressing（CLDR + Google 地址数据，256 国中文名）；按国别自动路由
- **RegionCascade 表单字段**：微信 / 淘宝 / 京东 同款「输入框 + 弹层逐级 Tab」级联；懒加载 API、选到叶子校验、直筒子市（东莞等）自动停级、邮编按国家规则必填
- **站点形态配置**：`supported_countries = ['CN']` 纯国内（不出国家选择器）/ 多国限定 / null 全球；`china_division_level` 3 或 4 级级联深度
- **组件化接入**：个人中心地址管理（增删改查 + 设默认）与下单选择（默认地址自动选中 + `sn-profile-address:selected` 事件通知宿主）两个 Livewire 组件，ChooseAddress 继承 Addresses 复用 CRUD 骨架
- **订单快照集成**：order 包下单时按 owner 校验归属后复制快照至 `sn_order_addresses`（source_address_id 溯源），后台订单编辑页可直接修改快照
- **数据更新**：`profile:import-regions --path= --force` 重建导入（带进度条）；到 [AreaCity Releases](https://github.com/xiangyuecn/AreaCity-JsSpider-StatsGov/releases) 下载新版 CSV 即可

## 安装

```bash
composer require wsmallnews/profile
```

```bash
php artisan sn-profile:install
```

安装命令发布配置/迁移并自动导入中国四级区划数据（站点不支持 CN 时跳过）。

## 使用

个人中心（shop 页面壳内嵌）：

```blade
<livewire:sn-profile::components.address.addresses :owner="$user" />
```

下单选择（事件通知宿主组件）：

```blade
<livewire:sn-profile::components.address.choose-address
    :owner="$user"
    :manage-url="route('shop.profile.addresses')"
/>
```

表单字段（地址 / 任意需要区划的场景）：

```php
use Wsmallnews\Profile\Filament\Address\Forms\Fields\RegionCascade;

RegionCascade::make('region')
    ->required()
    ->columnSpanFull();
```

## 配置

| 键 | 说明 |
| --- | --- |
| `supported_countries` | `['CN']` 纯国内；数组 = 限定多国；`null` = 全球 |
| `default_country` | 表单默认选中的国家 |
| `china_division_level` | 中国区划级联深度：`4`（省市区乡，微信形态）或 `3`（省市区，京东 PC 形态） |
| `postal_required_countries` | 邮编必填国家清单 |
| `models` | 可替换模型（address / region） |

## 测试

```bash
composer test
```

## Changelog

请查看 [CHANGELOG](CHANGELOG.md) 了解更多信息。

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
