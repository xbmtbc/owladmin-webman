<?php

namespace plugin\owladmin\app\support\Cores;

use plugin\owladmin\app\Admin;
use plugin\owladmin\app\support\Apis\{DataCreateApi};
use plugin\owladmin\app\support\Apis\DataListApi;
use plugin\owladmin\app\support\Apis\DataDetailApi;
use plugin\owladmin\app\support\Apis\DataUpdateApi;
use plugin\owladmin\app\support\Apis\DataDeleteApi;

// todo 导入api模板
class Api
{
    public static function boot(): void
    {
        appw('admin.context')->set('apis', [
            DataListApi::class,
            DataCreateApi::class,
            DataDetailApi::class,
            DataDeleteApi::class,
            DataUpdateApi::class,
        ]);

        if (!is_dir(self::path())) {
            return;
        }

        collect(scandir(app_path('/ApiTemplates')))
            ->filter(fn($file) => !in_array($file, ['.', '..']) && str_ends_with($file, '.php'))
            ->each(function ($file) {
                $class = 'App\\ApiTemplates\\' . str_replace('.php', '', $file);
                try {
                    if (class_exists($class)) {
                        Admin::context()->add('apis', $class);
                    }
                } catch (\Throwable $e) {
                }
            });
    }

    public static function path($file = ''): string
    {
        return app_path('/ApiTemplates') . ($file ? '/' . ltrim($file, '/') : '');
    }
}
