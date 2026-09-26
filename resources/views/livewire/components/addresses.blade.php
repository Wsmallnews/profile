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
        <div class="flex flex-col items-center justify-center gap-2 py-10">
            <p class="sn-content-text">{{ __('sn-profile::profile.address.empty_title') }}</p>
            <p class="sn-descript-text">{{ __('sn-profile::profile.address.empty_description') }}</p>
        </div>
    @else
        <x-filament::grid
            :default="$this->getColumns('default')"
            :sm="$this->getColumns('sm')"
            :md="$this->getColumns('md')"
            :lg="$this->getColumns('lg')"
            :xl="$this->getColumns('xl')"
            :two-xl="$this->getColumns('2xl')"
            class="sn-gap"
        >
            @foreach ($addresses as $address)
                <div class="w-full flex flex-col border rounded-lg sn-padded ring-1 ring-gray-950/10 hover:ring-2 hover:ring-primary-600 transition dark:ring-white/20">
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

                    <div class="flex justify-end items-center gap-2.5 mt-2">
                        {{ ($this->setDefaultAction)(['id' => $address->id]) }}
                        {{ ($this->editAction)(['id' => $address->id]) }}
                        {{ ($this->deleteAction)(['id' => $address->id]) }}
                    </div>
                </div>
            @endforeach
        </x-filament::grid>
    @endif
</div>

<x-filament-actions::modals />
