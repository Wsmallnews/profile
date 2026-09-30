@php
    use Filament\Support\Icons\Heroicon;

    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $withCountry = $isWithCountry();
    $placeholder = __('sn-profile::profile.region.placeholder') . implode(' / ', $getLevelLabels());

    // 当前 state 应展示的区划列表走独立 data 属性（morph 只更新属性值，不重建 Alpine 组件）；
    // 禁止放进 x-data：选项随链变化，x-data 属性字符串一变 Livewire 就会重建该组件，
    // open/active 等 UI 状态全部丢失（选一级面板就被关掉的 bug 即源于此）
    $divisions = json_encode(\Wsmallnews\Profile\Filament\Address\Forms\Fields\RegionCascade::divisionsForState($getState()));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="w-full"
        data-divisions="{{ $divisions }}"
        x-data="snProfileRegionCascade({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            config: @js([
                'statePath' => $statePath,
                'withCountry' => $withCountry,
                'countries' => $getCountries(),
                'defaultCountry' => $getDefaultCountry(),
                'levelLabels' => $getLevelLabels(),
                'placeholder' => __('sn-profile::profile.region.placeholder'),
                'noChildrenHint' => __('sn-profile::profile.region.no_children'),
                'disabled' => $isDisabled,
            ])
        })"
        x-on:keydown.escape.window="open = false"
        x-on:sn-profile-regions-updated.window="onRegionsUpdated($event)"
    >
        <div class="relative w-full">
            {{-- 触发器走 Filament 原生 input 组件：外观与焦点光环同其他表单字段完全一致；
                国际模式时国家选择融合为前缀（单框形态，共享一个光环）；
                下拉箭头经 wrapper 的 suffix-icon 渲染（input 组件本身不消费 suffixIcon 属性） --}}
            <x-filament::input.wrapper
                :disabled="$isDisabled"
                :suffix-icon="Heroicon::OutlinedChevronDown"
                :attributes="\Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())->class(['sn-profile-region-cascade'])"
            >
                <div class="flex items-center w-full">
                    @if ($withCountry)
                        <select
                            id="{{ $id }}-country"
                            x-model="state.country"
                            x-on:change="changeCountry($event.target.value)"
                            x-on:mousedown.stop
                            {{ $isDisabled ? 'disabled' : '' }}
                            aria-label="{{ __('sn-profile::profile.region.country.label') }}"
                            class="h-full shrink-0 border-0 border-e border-gray-200 bg-transparent ps-3 pe-2 text-sm text-gray-950 focus:outline-none focus:ring-0 dark:border-white/10 dark:text-white"
                        >
                            @foreach ($getCountries() as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif

                    <x-filament::input
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                                ->merge($getExtraAlpineAttributes(), escape: false)
                                ->merge([
                                    'disabled' => $isDisabled,
                                    'id' => $id,
                                    'placeholder' => $placeholder,
                                    'readonly' => true,
                                    'type' => 'text',
                                    'x-bind:value' => 'displayText()',
                                    'x-on:click' => 'toggle()',
                                ], escape: false)
                        "
                    />
                </div>
            </x-filament::input.wrapper>

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

                {{-- 选项列表（chain 变化经 Livewire 更新回推，期间显示加载态） --}}
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
                            class="flex w-full items-center justify-between px-3 py-2 text-start text-sm transition hover:bg-gray-50 dark:hover:bg-white/5"
                            x-bind:class="chain[active] && chain[active].code === option.code ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-200'"
                        >
                            <span x-text="option.name"></span>
                            <x-filament::icon
                                :icon="Heroicon::Check"
                                x-show="chain[active] && chain[active].code === option.code"
                                class="size-4 shrink-0"
                                aria-hidden="true"
                            />
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- 无下级区划提示（如直筒子市 / 数据到底） --}}
        <p class="mt-1.5 text-xs text-warning-600 dark:text-warning-400" x-show="hint" x-text="hint"></p>
    </div>
</x-dynamic-component>

{{-- @assets 而非 @once：字段常在 Filament Action 弹窗内经 AJAX 动态渲染，
     @once 的脚本在这种场景不会执行（Alpine 拿不到组件函数，点击无反应）；
     @assets 由 Livewire 资产注入机制保证动态渲染时也会执行（旧 DistrictSelect 同款）。

     选项数据流（无独立 HTTP 端点）：chain 是 live entangle，任何变化触发 Livewire 更新，
     服务端 afterStateUpdated 计算下一级选项并 dispatch sn-profile-regions-updated
     浏览器事件（payload 带 statePath 定位），Alpine 收事件后刷新 options。 --}}
@assets
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

                init() {
                    if (this.state === null || this.state === undefined) {
                        this.state = { country: this.config.defaultCountry, chain: [] };
                    }
                    if (!Array.isArray(this.state.chain)) {
                        this.state.chain = [];
                    }

                    // 初始选项来自 data-divisions 种子（空链 = 一级区划；已选链 = 下一级/同级列表）；
                    // 后续更新走 sn-profile-regions-updated 事件与 morph 刷新的 data 属性
                    this.options = this.readSeedDivisions();
                },

                readSeedDivisions() {
                    try {
                        return JSON.parse(this.$el.dataset.divisions || '[]') || [];
                    } catch (e) {
                        return [];
                    }
                },

                get chain() {
                    return Array.isArray(this.state.chain) ? this.state.chain : [];
                },

                displayText() {
                    return this.chain.map((node) => node.name).join('　');
                },

                levelLabel(index) {
                    return this.config.levelLabels[index] || ('区划 ' + (index + 1));
                },

                toggle() {
                    this.open = !this.open;

                    if (this.open) {
                        this.active = this.completed ? Math.max(0, this.chain.length - 1) : this.chain.length;

                        // 兜底：无选项且无进行中的请求时读 data 属性种子（事件丢失场景）
                        if (this.options.length === 0 && ! this.loading) {
                            this.options = this.readSeedDivisions();
                        }

                        this.loading = false;
                    }
                },

                pick(option) {
                    this.state.chain = this.chain.slice(0, this.active).concat([
                        { code: option.code, name: option.name },
                    ]);

                    if (option.has_children) {
                        this.completed = false;
                        this.active = this.active + 1;
                        this.loading = true;       // chain 已变，等 Livewire 回推下一级选项

                        return;
                    }

                    this.completed = true;

                    if (this.active + 1 < this.config.levelLabels.length) {
                        this.hint = this.config.noChildrenHint;
                    }

                    this.open = false;
                    this.loading = true;           // 回推同级列表供下次重开面板切换
                },

                reselect(index) {
                    this.state.chain = this.chain.slice(0, index);
                    this.completed = false;
                    this.active = index;
                    this.loading = true;
                },

                changeCountry(code) {
                    this.state.country = code;
                    this.state.chain = [];
                    this.completed = false;
                    this.options = [];
                    this.active = 0;
                    this.hint = '';
                    this.open = true;
                    this.loading = true;
                },

                onRegionsUpdated(event) {
                    // Livewire 服务端 dispatch 的参数到浏览器端可能包成 detail 数组
                    // （实测 detail = [{target, divisions}]），做形状归一兼容两种包法
                    const detail = Array.isArray(event.detail)
                        ? (event.detail[0] ?? {})
                        : (event.detail ?? {});

                    if ((detail.target ?? null) !== this.config.statePath) {
                        return;
                    }

                    this.options = detail.divisions || [];
                    this.loading = false;
                },
            };
        }
    </script>
@endassets
