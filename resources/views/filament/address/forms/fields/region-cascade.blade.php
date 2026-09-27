@php
    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $withCountry = $isWithCountry();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="w-full"
        x-data="snProfileRegionCascade({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            config: @js([
                'url' => route('sn-profile::regions'),
                'withCountry' => $withCountry,
                'countries' => $getCountries(),
                'defaultCountry' => $getDefaultCountry(),
                'countryLabel' => __('sn-profile::profile.region.country.label'),
                'levelLabels' => $getLevelLabels(),
                'placeholder' => __('sn-profile::profile.region.placeholder'),
                'noChildrenHint' => __('sn-profile::profile.region.no_children'),
                'initialDivisions' => $getInitialDivisions(),
                'disabled' => $isDisabled,
            ])
        })"
        x-on:keydown.escape.window="open = false"
    >
        @if ($withCountry)
            <select
                id="{{ $id }}-country"
                x-model="state.country"
                x-on:change="changeCountry($event.target.value)"
                {{ $isDisabled ? 'disabled' : '' }}
                class="mb-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-600 focus:ring-2 focus:ring-primary-600/30 disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white"
                aria-label="{{ __('sn-profile::profile.region.country.label') }}"
            >
                @foreach ($getCountries() as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        @endif

        <div class="relative w-full">
            <button
                type="button"
                id="{{ $id }}"
                x-on:click="toggle()"
                {{ $isDisabled ? 'disabled' : '' }}
                class="flex w-full cursor-pointer items-center justify-between gap-x-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-start text-sm text-gray-950 shadow-sm outline-none transition enabled:hover:border-gray-400 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/30 disabled:cursor-default disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white dark:enabled:hover:border-white/20"
            >
                <span
                    class="block truncate"
                    x-bind:class="displayText() === '' ? 'text-gray-400 dark:text-gray-500' : ''"
                    x-text="displayText() || placeholderText()"
                ></span>
                <span class="pointer-events-none shrink-0 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 transition-transform" x-bind:class="open && 'rotate-180'">
                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </span>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.opacity.duration.75ms
                x-on:mousedown.outside="open = false"
                class="absolute inset-x-0 top-full z-20 mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-900"
            >
                {{-- Tab 行：已选级显示名称（可回改），当前级显示“请选择xx” --}}
                <div class="flex overflow-x-auto border-b border-gray-200 text-sm dark:border-white/10" role="tablist">
                    <template x-for="(node, i) in chain" :key="node.code">
                        <button
                            type="button"
                            role="tab"
                            x-text="node.name"
                            x-on:click="reselect(i)"
                            class="whitespace-nowrap border-b-2 px-3 py-2.5 transition"
                            x-bind:class="active === i
                                ? 'border-primary-600 font-medium text-primary-600 dark:text-primary-400'
                                : 'border-transparent text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white'"
                        ></button>
                    </template>
                    <template x-if="!completed">
                        <button
                            type="button"
                            role="tab"
                            x-text="'{{ __('sn-profile::profile.region.placeholder') }}' + levelLabel(chain.length)"
                            class="whitespace-nowrap border-b-2 border-primary-600 px-3 py-2.5 font-medium text-primary-600 dark:text-primary-400"
                        ></button>
                    </template>
                </div>

                {{-- 选项列表 --}}
                <div class="max-h-60 overflow-y-auto">
                    <template x-if="loading">
                        <div class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">…</div>
                    </template>
                    <template x-if="!loading && options.length === 0">
                        <div class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                            {{ __('sn-profile::profile.region.no_children') }}
                        </div>
                    </template>
                    <template x-for="option in options" :key="option.code">
                        <button
                            type="button"
                            x-on:click="pick(option)"
                            class="flex w-full items-center justify-between px-3 py-2 text-start text-sm transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5"
                            x-bind:class="chain[active] && chain[active].code === option.code ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-200'"
                        >
                            <span x-text="option.name"></span>
                            <svg x-show="chain[active] && chain[active].code === option.code" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 shrink-0">
                                <path fill-rule="evenodd" d="M16.704 4.28a.75.75 0 0 1 .016 1.06l-7.25 7.5a.75.75 0 0 1-1.065.016L3.22 9.72a.75.75 0 0 1 1.06-1.06l3.124 3.124 6.25-6.46a.75.75 0 0 1 1.06-.016Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- 无下级区划提示（如直筒子市 / 数据到底） --}}
        <p class="mt-1.5 text-xs text-warning-600 dark:text-warning-400" x-show="hint" x-text="hint"></p>
    </div>
</x-dynamic-component>

@once
    <script>
        function snProfileRegionCascade({ state, config }) {
            return {
                state: state,
                config: config,
                open: false,
                active: 0,
                options: [],
                loading: false,
                hint: '',
                completed: false,
                cache: {},

                init() {
                    if (this.state === null || this.state === undefined) {
                        this.state = { country: this.config.defaultCountry, chain: [] };
                    }
                    if (!Array.isArray(this.state.chain)) {
                        this.state.chain = [];
                    }
                },

                get chain() {
                    return Array.isArray(this.state.chain) ? this.state.chain : [];
                },

                displayText() {
                    return this.chain.map((node) => node.name).join('　');
                },

                placeholderText() {
                    return this.config.placeholder + this.config.levelLabels.join(' / ');
                },

                levelLabel(index) {
                    return this.config.levelLabels[index] || ('区划 ' + (index + 1));
                },

                toggle() {
                    this.open = !this.open;

                    if (this.open) {
                        this.activate(this.chain.length);
                    }
                },

                activate(index) {
                    this.active = index;
                    this.hint = '';
                    this.load(index);
                },

                async load(index) {
                    const parents = this.chain.slice(0, index).map((node) => node.code);
                    const key = this.state.country + '|' + parents.join(',');

                    if (this.cache[key]) {
                        this.options = this.cache[key];

                        return;
                    }

                    this.loading = true;

                    try {
                        const params = new URLSearchParams({ country: this.state.country });

                        if (parents.length > 0) {
                            params.set('parents', parents.join(','));
                        }

                        const response = await fetch(this.config.url + '?' + params.toString());
                        const json = await response.json();

                        this.cache[key] = json.data || [];
                        this.options = this.cache[key];
                    } finally {
                        this.loading = false;
                    }
                },

                pick(option) {
                    this.state.chain = this.chain.slice(0, this.active).concat([
                        { code: option.code, name: option.name },
                    ]);

                    if (option.has_children) {
                        this.completed = false;
                        this.activate(this.active + 1);

                        return;
                    }

                    this.completed = true;

                    if (this.active + 1 < this.config.levelLabels.length) {
                        this.hint = this.config.noChildrenHint;
                    }

                    this.open = false;
                },

                reselect(index) {
                    this.state.chain = this.chain.slice(0, index);
                    this.completed = false;
                    this.activate(index);
                },

                changeCountry(code) {
                    this.state.country = code;
                    this.state.chain = [];
                    this.completed = false;
                    this.options = [];
                    this.cache = {};
                    this.hint = '';
                    this.open = true;
                    this.activate(0);
                },
            };
        }
    </script>
@endonce
