<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user_and_preserve_time_entry_history(): void
    {
        [$admin, $user, $client, $project, $activity] = $this->resources();
        $entry = TimeEntry::factory()->for($user)->for($project)->for($activity)->create(['created_by' => $user->id]);

        $this->actingAs($admin)->delete(route('users.destroy', $user))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame($user->name, $entry->fresh()->user->name);
    }

    public function test_admin_can_delete_client_and_its_projects_while_preserving_history(): void
    {
        [$admin, $user, $client, $project, $activity] = $this->resources();
        $entry = TimeEntry::factory()->for($user)->for($project)->for($activity)->create(['created_by' => $user->id]);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSame($client->name, $entry->fresh()->project->client->name);
    }

    public function test_admin_can_delete_project_with_completed_entries(): void
    {
        [$admin, $user, $client, $project, $activity] = $this->resources();
        $entry = TimeEntry::factory()->for($user)->for($project)->for($activity)->create(['created_by' => $user->id]);

        $this->actingAs($admin)->delete(route('projects.destroy', $project))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSame($project->name, $entry->fresh()->project->name);
    }

    public function test_admin_can_delete_activity_with_completed_entries(): void
    {
        [$admin, $user, $client, $project, $activity] = $this->resources();
        $entry = TimeEntry::factory()->for($user)->for($project)->for($activity)->create(['created_by' => $user->id]);

        $this->actingAs($admin)->delete(route('activities.destroy', $activity))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
        $this->assertSame($activity->name, $entry->fresh()->activity->name);
    }

    public function test_resource_with_running_timer_cannot_be_deleted(): void
    {
        [$admin, $user, $client, $project, $activity] = $this->resources();
        TimeEntry::create([
            'user_id' => $user->id, 'project_id' => $project->id, 'activity_id' => $activity->id,
            'started_at' => now(), 'duration_seconds' => 0, 'entry_type' => 'timer',
            'status' => 'running', 'created_by' => $user->id,
        ]);

        $this->actingAs($admin)->from(route('projects.index'))->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'))->assertSessionHasErrors('project');
        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_collaborator_cannot_delete_administrative_resources(): void
    {
        [, $user, $client] = $this->resources();

        $this->actingAs($user)->delete(route('clients.destroy', $client))->assertForbidden();
        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }

    private function resources(): array
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create(['status' => 'active']);
        $activity = Activity::factory()->create();

        return [$admin, $user, $client, $project, $activity];
    }
}
