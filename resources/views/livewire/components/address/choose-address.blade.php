{{-- 自含容器：卡片网格用容器断点（@3xl=768px 对齐原视口 md），窄槽自动降为单列 --}}
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
        <div class="flex flex-col items-center justify-center gap-2 py-10">
            <p class="sn-content-text">{{ __('sn-profile::profile.address.empty_title') }}</p>
            <p class="sn-descript-text">{{ __('sn-profile::profile.address.empty_description') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 @3xl:grid-cols-2">
            @foreach ($addresses as $address)
                <div
                    class="w-full flex flex-col border rounded-lg sn-padded cursor-pointer transition"
                    @class([
                        'ring-1 ring-gray-950/10 hover:ring-2 hover:ring-primary-600 dark:ring-white/20' => $selectedId !== $address->id,
                        'ring-2 ring-primary-600' => $selectedId === $address->id,
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

                    <div class="flex items-center justify-between text-base sn-content-text">
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
