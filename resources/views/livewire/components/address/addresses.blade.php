@php
    use Filament\Support\Icons\Heroicon;
@endphp

{{-- 自含容器：卡片网格用容器断点（@2xl=672/@5xl=1024），窄槽自动降为单列 --}}
<div class="w-full @container">
    <div @class([
        'w-full flex flex-col sn-gap',
        $contained ? 'sn-container sn-padded' : '',
    ])>
    <div class="flex items-center justify-between gap-4">
        <h3 class="sn-h3-text">
            {{ __('sn-profile::profile.address.my_addresses') }}
        </h3>

        <div class="flex items-center gap-4">
            {{ $this->createAction }}
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
                <div class="w-full flex flex-col sn-padded sn-container sn-hover">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($address->is_default)
                            <span class="sn-badge sn-badge-primary sn-badge-sm">
                                {{ __('sn-profile::profile.address.default') }}
                            </span>
                        @endif
                        @if ($address->label)
                            <span class="sn-badge sn-badge-outline sn-badge-sm">{{ $address->label }}</span>
                        @endif
                        <span class="text-sm sn-gray-text">{{ $address->region_label }}</span>
                    </div>

                    <div class="text-base my-1 sn-content-text">{{ $address->address_line1 }}</div>

                    <div class="flex items-center text-base sn-content-text">
                        <span class="mr-2.5">{{ $address->consignee }}</span>
                        <span>{{ $address->phone }}</span>
                    </div>

                    {{-- mt-auto：卡片高度不齐（标签换行）时操作行钉在底部，三按钮右对齐 --}}
                    <div class="flex justify-end items-center gap-2.5 mt-auto pt-2.5">
                        {{ ($this->setDefaultAction)(['id' => $address->id]) }}
                        {{ ($this->editAction)(['id' => $address->id]) }}
                        {{ ($this->deleteAction)(['id' => $address->id]) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals />
    </div>
</div>
