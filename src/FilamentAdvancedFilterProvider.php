<?php

namespace Webbingbrasil\FilamentAdvancedFilter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAdvancedFilterProvider extends PackageServiceProvider
{
    public static string $name = 'filament-advancedfilter';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }
}
