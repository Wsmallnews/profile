<?php

namespace Wsmallnews\Profile\Livewire\Components;

use Wsmallnews\Support\Livewire\Base as SupportBase;

/**
 * profile 包前台组件基类
 *
 * 内嵌组件：owner / scopeable 等数据上下文一律经 props 注入，不读模块默认 scope。
 */
class Base extends SupportBase {}
