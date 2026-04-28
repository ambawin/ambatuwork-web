<?php

namespace App\Providers;

use App\Models\Sprint;
use App\Models\Task;
use App\Models\Team;
use App\Policies\SprintPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

    }
}