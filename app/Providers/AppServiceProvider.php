<?php

namespace App\Providers;

use App\Models\BacklogItem;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\DailyCheckin;
use App\Models\Impediment;
use App\Models\SprintReview;
use App\Models\Retrospective;
use App\Models\PeerReviewCycle;
use App\Policies\BacklogItemPolicy;
use App\Policies\DefinitionOfDonePolicy;
use App\Policies\SprintPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\DailyCheckinPolicy;
use App\Policies\ImpedimentPolicy;
use App\Policies\SprintReviewPolicy;
use App\Policies\RetrospectivePolicy;
use App\Policies\PeerReviewPolicy;
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
        Gate::policy(DailyCheckin::class, DailyCheckinPolicy::class);
        Gate::policy(Impediment::class, ImpedimentPolicy::class);
        Gate::policy(SprintReview::class, SprintReviewPolicy::class);
        Gate::policy(Retrospective::class, RetrospectivePolicy::class);
        Gate::policy(PeerReviewCycle::class, PeerReviewPolicy::class);

        // Automatically feed $joinedProjects (all accessible projects) and $activeProject to layouts.dashboard
        \Illuminate\Support\Facades\View::composer('layouts.dashboard', function ($view) {
            $allProjects = collect();
            $activeProject = null;

            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                $allProjects = \App\Models\Project::visibleTo($user)
                    ->with(['owner', 'activeSprint'])
                    ->latest()
                    ->get();

                $activeProjectId = request()->query('project_id') ?: session('active_project_id');

                if ($activeProjectId) {
                    $activeProject = $allProjects->firstWhere('id', $activeProjectId);
                }

                // If no active project found or selected, default to the latest visible one
                if (!$activeProject && !$allProjects->isEmpty()) {
                    $activeProject = $allProjects->first();
                }

                if ($activeProject) {
                    session(['active_project_id' => $activeProject->id]);
                }
            }

            $view->with([
                'joinedProjects' => $allProjects,
                'activeProject' => $activeProject,
            ]);
        });

        \Illuminate\Support\Facades\Blade::component('layouts.app', 'app-layout');
    }
}