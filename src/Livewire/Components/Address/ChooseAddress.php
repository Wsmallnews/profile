<?php

namespace Wsmallnews\Profile\Livewire\Components\Address;

use Livewire\Attributes\Locked;
use Wsmallnews\Profile\Models\Address;

/**
 * 下单收货地址选择（默认地址自动选中 + 新增/编辑 + 事件通知宿主）
 *
 * <livewire:sn-profile::components.address.choose-address :owner="$member" :manage-url="..." />
 *
 * 继承 Addresses 复用 CRUD 骨架，选择后 dispatch：sn-profile-address:selected（addressId）。
 */
class ChooseAddress extends Addresses
{
    /**
     * “管理收货地址”页面链接（调用方注入，如个人中心地址页）
     */
    public ?string $manageUrl = null;

    #[Locked]
    public ?int $selectedId = null;

    public function mount(): void
    {
        // 未显式指定时自动选中默认地址（默认优先 + 新建优先）；
        // owner / manageUrl / contained 等公共属性已由 Livewire 自动注入
        $this->selectedId ??= $this->owner->addresses()->value('id');
    }

    public function choose(int $id): void
    {
        $address = $this->owner->addresses()->whereKey($id)->first();

        if (! $address) {
            return;
        }

        $this->selectedId = $address->id;

        $this->dispatch('sn-profile-address:selected', addressId: $address->id);
    }

    public function render()
    {
        return view('sn-profile::livewire.components.address.choose-address', $this->getViewData());
    }

    protected function addressCreateLabel(): string
    {
        return __('sn-profile::profile.address.use_new_address');
    }

    protected function addressCreated(Address $address): void
    {
        // 新增后直接选中并通知宿主
        $this->selectedId = $address->id;

        $this->dispatch('sn-profile-address:selected', addressId: $address->id);
    }
}
