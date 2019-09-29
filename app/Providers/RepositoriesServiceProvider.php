<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Test;
use App\Models\Calendar;
use App\Repositories\Contracts\CalendarRepository;
use App\Repositories\EloquentCalendarRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\TestRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\EloquentTestRepository;

use App\Models\Org;
use App\Repositories\Contracts\OrgRepository;
use App\Repositories\EloquentOrgRepository;

class RepositoriesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepository::class, function () {
            return new EloquentUserRepository(new User());
        });
        $this->app->bind(TestRepository::class, function () {
            return new EloquentTestRepository(new Test());
        });
        $this->app->bind(OrgRepository::class, function () {
            return new EloquentOrgRepository(new Org());
        });
        $this->app->bind(CalendarRepository::class, function() {
            return new EloquentCalendarRepository(new Calendar());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            UserRepository::class,
            TestRepository::class,
            Org::class,
            CalendarRepository::class,
        ];
    }
}
