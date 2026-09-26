<?php

namespace Wsmallnews\Profile\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 中国行政区划（省/市/区县/乡镇街道四级）
 *
 * 静态导入数据（AreaCity，国家统计局口径），无时间戳，只读消费。
 */
class Region extends Model
{
    protected $table = 'sn_profile_regions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'parent_id' => 'integer',
        'level' => 'integer',
    ];

    /**
     * 展示名（优先全称）
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->name;
    }

    public function parent(): HasMany
    {
        return $this->hasMany(static::class, 'id', 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('id');
    }

    public function scopeLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }
}
