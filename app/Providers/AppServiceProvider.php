<?php

namespace App\Providers;

use App\Models\BacklogItem;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use App\Models\Sprint;
use App\Policies\BacklogItemPolicy;
use App\Policies\DefinitionOfDonePolicy;
use App\Policies\SprintPolicy;
use App\Policies\ProjectPolicy;
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
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(BacklogItem::class, BacklogItemPolicy::class);
        Gate::policy(DefinitionOfDone::class, DefinitionOfDonePolicy::class);
        Gate::policy(Sprint::class, SprintPolicy::class);
    }
}