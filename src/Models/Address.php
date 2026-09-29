<?php

namespace Wsmallnews\Profile\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 用户收货地址（地址簿条目）
 *
 * owner 为多态：User（跨租户共享，team_id 为空）或 Member（租户内，team_id 冗余）。
 * 区划字段 = code + 名称快照成对，名称快照保证历史展示不随区划数据更新漂移。
 */
class Address extends Model
{
    protected $table = 'sn_profile_addresses';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'used_num' => 'integer',
        'longitude' => 'float',
        'latitude' => 'float',
        'options' => 'array',
    ];

    /**
     * 设为默认地址（同一 owner 范围内唯一）
     */
    public function setDefault(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('owner_type', $this->owner_type)
                ->where('owner_id', $this->owner_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }

    /**
     * 区划展示链（广东省　深圳市　南山区　南头街道）
     */
    protected function regionLabel(): Attribute
    {
        return Attribute::get(fn () => collect([
            $this->administrative_area_name,
            $this->locality_name,
            $this->dependent_locality_name,
            $this->township_name,
        ])->filter()->implode('　'));
    }

    /**
     * 完整地址（区划链 + 详细地址）
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn () => collect([$this->region_label, $this->address_line1])->filter()->implode(' '));
    }

    /**
     * 默认优先 + 新建优先
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_default')->orderByDesc('id');
    }

    /**
     * 地址所有者（User / Member）
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 租户（NULL = 全局地址，如跨租户共享的用户地址）
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(SupportUtils::getTenantModel());
    }
}
