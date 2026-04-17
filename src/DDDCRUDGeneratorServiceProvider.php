<?php

namespace Raham\DDDCRUDGen;

use Illuminate\Console\Application as Artisan;
use Illuminate\Support\ServiceProvider;
use Raham\DDDCRUDGen\MakeDDDCRUD;

class DDDCRUDGeneratorServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $helpers = __DIR__ . '/helpers.php';

    if (file_exists($helpers)) {
      require_once $helpers;
    }
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    if ($this->app->runningInConsole()) {
      $this->commands([
        MakeDDDCRUD::class,
      ]);
    }

    $this->publishes([
      __DIR__ . '/../stubs' => base_path('stubs/dddcrudgen'),
    ], 'ddd-stubs');

    $this->publishes([
      __DIR__ . '/../config/ddd.php' => config_path('ddd.php'),
    ], 'ddd-config');

    $this->mergeConfigFrom(
      __DIR__ . '/../config/ddd.php',
      'ddd'
    );

  }
}
