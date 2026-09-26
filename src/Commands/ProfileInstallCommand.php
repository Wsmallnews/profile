<?php

namespace Wsmallnews\Profile\Commands;

use Illuminate\Support\Facades\Artisan;
use Wsmallnews\Support\Commands\PackageInstallCommand;

class ProfileInstallCommand extends PackageInstallCommand
{
    protected string $packageName = 'sn-profile';

    protected function afterPublish(): void
    {
        // 站点支持中国时自动导入四级区划数据
        $supported = (array) config('sn-profile.supported_countries', []);

        if (in_array('CN', $supported, true) || $supported === []) {
            Artisan::call('profile:import-regions', ['--force' => true]);
        }
    }
}
