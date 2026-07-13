<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // AdminLTE Menu Listener removed (migrated to Tabler layout)
        Paginator::useBootstrapFive();

        if (\Illuminate\Support\Facades\Schema::hasTable('floors')) {
            $globalFloors = \Illuminate\Support\Facades\Cache::remember('global_floors_list', 300, function () {
                return \App\Models\Floor::with(['wings.rooms' => function($q) {
                    $q->withCount(['beds as occupied_beds_count' => function($bq) {
                        $bq->where('status', 'terisi');
                    }])->orderBy('name', 'asc');
                }, 'wings' => function($q) {
                    $q->orderBy('name', 'asc');
                }])->get()->sortBy(function($floor) {
                    if (is_numeric($floor->name)) {
                        return (int)$floor->name;
                    }
                    return 1000 + ord($floor->name[0] ?? '');
                });
            });

            view()->share('globalFloors', $globalFloors);
        } else {
            $fallbackFloors = collect(['3', '5', '6', '10', '11', '12', '13', '14'])->map(function($fl) {
                return (object)['name' => $fl];
            });
            view()->share('globalFloors', $fallbackFloors);
        }
    }
}