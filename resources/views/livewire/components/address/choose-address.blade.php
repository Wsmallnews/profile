@php
    use Filament\Support\Icons\Heroicon;
@endphp

{{-- 自含容器：卡片网格用容器断点（@2xl=672/@5xl=1024），窄槽自动降为单列 --}}
<div class="w-full @container">
    <div @class([
        'w-full flex flex-col sn-gap',
        $contained ? 'sn-container sn-padded' : '',
    ])>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h3 class="sn-h3-text">
            {{ __('sn-profile::profile.address.choose_title') }}
        </h3>

        <div class="flex items-center gap-4">
            {{ $this->createAction }}

            @if ($manageUrl)
                <a href="{{ $manageUrl }}" class="sn-link text-sm">
                    {{ __('sn-profile::profile.address.manage_address') }}
                </a>
            @endif
        </div>
    </div>

    @if ($addresses->isEmpty())
        {{-- 列表卡内空态：contained=false 不再套卡片（sn-empty 统一版式） --}}
        <x-sn-support::empty
            :heading="__('sn-profile::profile.address.empty_title')"
            :description="__('sn-profile::profile.address.empty_description')"
            :icon="Heroicon::OutlinedMapPin"
            :contained="false"
        />
    @else
        <div class="grid grid-cols-1 gap-4 @2xl:grid-cols-2 @5xl:grid-cols-3">
            @foreach ($addresses as $address)
                <div
                    class="w-full flex flex-col sn-padded cursor-pointer sn-container sn-hover"
                    @class([
                        // important 后缀覆盖 sn-container 的 ring-1 基线（选中态 2px 主题环）
                        'ring-2! ring-primary-600!' => $selectedId === $address->id,
                    ])
                    wire:click="choose({{ $address->id }})"
                    wire:key="sn-profile-choose-address-{{ $address->id }}"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($selectedId === $address->id)
                            <span class="sn-badge sn-badge-primary sn-badge-sm">
                                {{ __('sn-profile::profile.address.selected') }}
                            </span>
                        @endif
                        @if ($address->is_default)
                            <span class="sn-badge sn-badge-outline sn-badge-sm">
                                {{ __('sn-profile::profile.address.default') }}
                            </span>
                        @endif
                        <span class="text-sm sn-gray-text">{{ $address->region_label }}</span>
                    </div>

                    <div class="text-base my-1 sn-content-text">{{ $address->address_line1 }}</div>

                    <div class="flex items-center justify-between text-base sn-content-text mt-auto pt-1">
                        <span class="flex items-center">
                            <span class="mr-2.5">{{ $address->consignee }}</span>
                            <span>{{ $address->phone }}</span>
                        </span>

                        {{ ($this->editAction)(['id' => $address->id]) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals />
    </div>
</div>
