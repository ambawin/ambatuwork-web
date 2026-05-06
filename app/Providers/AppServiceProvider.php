<?php

namespace App\Providers;

use App\Models\BacklogItem;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use App\Policies\BacklogItemPolicy;
use App\Policies\DefinitionOfDonePolicy;
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
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(BacklogItem::class, BacklogItemPolicy::class);
        Gate::policy(DefinitionOfDone::class, DefinitionOfDonePolicy::class);
    }
}