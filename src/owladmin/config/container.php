<?php
// return new Webman\Container;

use support\Container;
use Illuminate\Filesystem\Filesystem;
use plugin\owladmin\app\extend\Manager;
use plugin\owladmin\app\support\Cores\Menu;
use plugin\owladmin\app\support\Cores\Asset;
use plugin\owladmin\app\support\Cores\Module;
use plugin\owladmin\app\support\Apis\DataListApi;
use plugin\owladmin\app\support\Apis\DataCreateApi;
use plugin\owladmin\app\support\Apis\DataDetailApi;
use plugin\owladmin\app\support\Apis\DataDeleteApi;
use plugin\owladmin\app\support\Apis\DataUpdateApi;
use plugin\owladmin\app\service\AdminSettingService;

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions([
    'apis'          => [
        DataListApi::class,
        DataCreateApi::class,
        DataDetailApi::class,
        DataDeleteApi::class,
        DataUpdateApi::class,
    ],
    'files'         => new Filesystem,
    'admin.menu'    => new Menu,
    'admin.asset'   => new Asset,
    'admin.setting' => AdminSettingService::make(),
    'admin.module'  => new Module,
    'admin.extend'  => fn() => Container::instance('owladmin')->make(Manager::class), // 拓展
]);
$builder->useAutowiring(true);
$builder->useAnnotations(true);
return $builder->build();