<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('settings', function () {
            return new SettingsService();
        });
        // Register console command for auto-promotion
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AutoPromoteStudents::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('schoolName', app('settings')->get('school.name', config('app.name')));

        $defaultYear = app('settings')->get('school.academic_year', date('Y').'-'.(date('Y') + 1));
        $selectedYear = $defaultYear;
        if (! app()->runningInConsole() && request()?->hasSession()) {
            $selectedYear = request()->session()->get('academic_year', $defaultYear);
        }

        $years = [];
        $start = (int) date('Y') - 2;
        for ($i = 0; $i < 7; $i++) {
            $from = $start + $i;
            $to = $from + 1;
            $years[] = $from.'-'.$to;
        }

        View::share('selectedAcademicYear', $selectedYear);
        View::share('availableAcademicYears', $years);
    }
}
