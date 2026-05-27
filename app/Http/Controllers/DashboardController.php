<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Fetch owned projects with relevant metadata
        $ownedProjects = $user->ownedProjects()
            ->with(['members', 'activeSprint'])
            ->latest()
            ->get();

        // Fetch joined projects with relevant metadata
        $joinedProjects = $user->projects()
            ->with(['owner', 'activeSprint'])
            ->latest()
            ->get();

        // Fetch any pending project invitations matching the user's email
        $pendingInvitations = ProjectInvitation::query()
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->with(['project', 'invitedBy'])
            ->latest()
            ->get();

        // Calculate dashboard stats
        $totalProjectsCount = $ownedProjects->count() + $joinedProjects->count();
        
        $activeSprintsCount = 0;
        foreach ($ownedProjects as $project) {
            if ($project->activeSprint) {
                $activeSprintsCount++;
            }
        }
        foreach ($joinedProjects as $project) {
            if ($project->activeSprint) {
                $activeSprintsCount++;
            }
        }

        return view('dashboard', [
            'user' => $user,
            'ownedProjects' => $ownedProjects,
            'joinedProjects' => $joinedProjects,
            'pendingInvitations' => $pendingInvitations,
            'totalProjectsCount' => $totalProjectsCount,
            'activeSprintsCount' => $activeSprintsCount,
        ]);
    }
}
