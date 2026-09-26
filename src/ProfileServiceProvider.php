<?php

namespace Wsmallnews\Profile;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wsmallnews\Profile\Commands\ImportRegionsCommand;
use Wsmallnews\Profile\Commands\ProfileInstallCommand;
use Wsmallnews\Profile\Services\RegionService;
use Wsmallnews\Profile\Support\Utils;
use Wsmallnews\Support\Features\Modules\Module;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

class ProfileServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sn-profile';

    public static string $viewNamespace = 'sn-profile';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasTranslations()
            ->hasViews(static::$viewNamespace)
            ->hasRoutes($this->getRoutes());
    }

    public function packageRegistered(): void
    {
        ModuleRegistry::register(new Module(
            id: static::$name,
            namespace: 'Wsmallnews\Profile',
            plugin: ProfilePlugin::class,
        ));

        // 区划服务（容器单例，app('sn-profile') / Profile facade）
        $this->app->singleton('sn-profile', fn ($app) => $app->make(RegionService::class));

        // 前台组件命名空间：<livewire:sn-profile::components.xxx>
        Livewire::addNamespace(namespace: 'sn-profile', classNamespace: 'Wsmallnews\Profile\Livewire');
    }

    public function packageBooted(): void
    {
        // 注册模型别名
        Relation::enforceMorphMap([
            'sn_profile_address' => Utils::getAddressModel(),
        ]);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/profile/{$file->getFilename()}"),
                ], 'profile-stubs');
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'wsmallnews/profile';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            ProfileInstallCommand::class,
            ImportRegionsCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [
            'web',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_sn_profile_regions_table',
            'create_sn_profile_addresses_table',
        ];
    }
}
