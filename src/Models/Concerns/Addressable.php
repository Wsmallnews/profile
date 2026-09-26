<?php

namespace Wsmallnews\Profile\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Wsmallnews\Profile\Models\Address;
use Wsmallnews\Profile\Support\Utils;

/**
 * 地址簿所有者侧关联：User / Member 等 owner 模型 use 本 trait
 *
 * User（跨租户共享）：addresses 团队无关；Member（租户内）：地址行冗余 team_id 隔离。
 */
trait Addressable
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Utils::getAddressModel(), 'owner')->ordered();
    }

    /**
     * 默认地址（无显式默认时取最新一条）
     */
    public function defaultAddress(): ?Address
    {
        return $this->addresses()->first();
    }
}
