<?php

namespace Tests\Feature;

use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SprintBoardLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_and_close_sprint_via_livewire(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProject($owner);
        $backlogItem = $this->createBacklogItem($project, $owner, 'Sample Story', 5);

        // Create planned sprint
        $sprint = $project->sprints()->create([
            'name' => 'Sprint 1',
            'sprint_goal' => 'Get things done',
            'status' => 'planned',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-14',
            'created_by_user_id' => $owner->id,
        ]);

        // Add backlog item to sprint
        $sprint->items()->attach($backlogItem->id, [
            'committed_points' => $backlogItem->estimate_points,
            'added_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        // 1. Owner starts the sprint
        Livewire::actingAs($owner)
            ->withQueryParams(['project_id' => $project->id])
            ->test('sprint-board')
            ->set('selectedSprintId', $sprint->id)
            ->call('startSprint', $sprint->id)
            ->assertHasNoErrors();

        $this->assertEquals('active', $sprint->fresh()->status);

        // 2. Owner closes the sprint
        Livewire::actingAs($owner)
            ->withQueryParams(['project_id' => $project->id])
            ->test('sprint-board')
            ->set('selectedSprintId', $sprint->id)
            ->call('closeSprint', $sprint->id)
            ->assertHasNoErrors();

        $this->assertEquals('closed', $sprint->fresh()->status);
    }

    public function test_non_owner_cannot_start_or_close_sprint_via_livewire(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProject($owner);
        
        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $backlogItem = $this->createBacklogItem($project, $owner, 'Sample Story', 5);

        // Create planned sprint
        $sprint = $project->sprints()->create([
            'name' => 'Sprint 1',
            'sprint_goal' => 'Get things done',
            'status' => 'planned',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-14',
            'created_by_user_id' => $owner->id,
        ]);

        $sprint->items()->attach($backlogItem->id, [
            'committed_points' => $backlogItem->estimate_points,
            'added_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        // Member trying to start the sprint should be rejected
        Livewire::actingAs($member)
            ->withQueryParams(['project_id' => $project->id])
            ->test('sprint-board')
            ->set('selectedSprintId', $sprint->id)
            ->call('startSprint', $sprint->id);

        $this->assertEquals('planned', $sprint->fresh()->status);
    }

    private function createProject(User $owner): Project
    {
        $project = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Sprint Test Project',
            'product_goal' => 'Goal',
            'default_sprint_length_days' => 14,
            'status' => 'active',
        ]);

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $project;
    }

    private function createBacklogItem(Project $project, User $user, string $title, int $points): BacklogItem
    {
        return $project->backlogItems()->create([
            'title' => $title,
            'type' => 'story',
            'status' => 'backlog',
            'priority_rank' => 1.0,
            'estimate_points' => $points,
            'created_by_user_id' => $user->id,
        ]);
    }
}
