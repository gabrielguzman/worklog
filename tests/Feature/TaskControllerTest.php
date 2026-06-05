<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->tag = Tag::factory()->create(['user_id' => $this->user->id]);
    }

    // ── INDEX TESTS ──────────────────────────────────────────────
    public function test_task_index_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/tasks');

        $response->assertOk();
    }

    public function test_task_index_shows_pending_and_in_progress_tasks_by_default(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'in_progress']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks');

        $tasks = $response['tasks']->items();
        $this->assertCount(2, $tasks);
    }

    public function test_task_index_can_filter_by_status(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks?status=done');

        $tasks = $response['tasks']->items();
        $this->assertCount(1, $tasks);
        $this->assertEquals('done', $tasks[0]['status']);
    }

    public function test_task_index_can_search_by_title(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Implementar feature']);
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Fix bug']);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks?search=feature');

        $tasks = $response['tasks']->items();
        $this->assertCount(1, $tasks);
        $this->assertStringContainsString('feature', $tasks[0]['title']);
    }

    public function test_task_index_can_filter_by_priority(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'urgent']);
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'low']);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks?priority=urgent');

        $tasks = $response['tasks']->items();
        $this->assertCount(1, $tasks);
        $this->assertEquals('urgent', $tasks[0]['priority']);
    }

    public function test_task_index_shows_overdue_tasks(): void
    {
        $overdue = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => now()->subDays(1),
            'status' => 'pending'
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks?overdue=1');

        $tasks = $response['tasks']->items();
        $this->assertCount(1, $tasks);
        $this->assertTrue($tasks[0]['is_overdue']);
    }

    // ── CREATE TESTS ─────────────────────────────────────────────
    public function test_task_create_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/tasks/create');

        $response->assertOk();
    }

    public function test_task_can_be_created(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/tasks', [
                'title' => 'Nueva tarea',
                'description' => 'Descripción de la tarea',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'project_id' => $this->project->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Nueva tarea',
            'priority' => 'high',
        ]);
    }

    public function test_task_requires_valid_data(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/tasks', [
                'title' => '', // required
                'priority' => 'invalid', // invalid priority
                'status' => 'pending',
            ]);

        $response->assertSessionHasErrors(['title', 'priority']);
    }

    public function test_task_can_have_tags(): void
    {
        $this
            ->actingAs($this->user)
            ->post('/tasks', [
                'title' => 'Tarea con tags',
                'priority' => 'medium',
                'status' => 'pending',
                'tags' => [$this->tag->id],
            ]);

        $task = Task::where('title', 'Tarea con tags')->first();
        $this->assertTrue($task->tags->contains($this->tag));
    }

    public function test_task_cannot_have_nested_subtasks(): void
    {
        $parent = Task::factory()->create(['user_id' => $this->user->id]);
        $child = Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_task_id' => $parent->id
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post('/tasks', [
                'title' => 'Subtarea de subtarea',
                'priority' => 'medium',
                'status' => 'pending',
                'parent_task_id' => $child->id,
            ]);

        $response->assertStatus(422);
    }

    // ── SHOW TESTS ───────────────────────────────────────────────
    public function test_task_show_page_is_displayed(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this
            ->actingAs($this->user)
            ->get("/tasks/{$task->id}");

        $response->assertOk();
        $this->assertEquals($task->title, $response['task']['title']);
    }

    public function test_user_cannot_see_other_users_task(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($this->user)
            ->get("/tasks/{$task->id}");

        $response->assertForbidden();
    }

    public function test_task_show_includes_subtasks(): void
    {
        $parent = Task::factory()->create(['user_id' => $this->user->id]);
        $child = Task::factory()->create(['user_id' => $this->user->id, 'parent_task_id' => $parent->id]);

        $response = $this
            ->actingAs($this->user)
            ->get("/tasks/{$parent->id}");

        $response->assertOk();
        $this->assertCount(1, $response['task']['subtasks']);
    }

    // ── UPDATE TESTS ─────────────────────────────────────────────
    public function test_task_can_be_updated(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}", [
                'title' => 'Título actualizado',
                'priority' => 'urgent',
                'status' => 'in_progress',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Título actualizado',
            'priority' => 'urgent',
        ]);
    }

    public function test_marking_task_done_sets_completed_at(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}", [
                'title' => $task->title,
                'priority' => $task->priority,
                'status' => 'done',
            ]);

        $task->refresh();
        $this->assertEquals('done', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_unmarking_task_done_clears_completed_at(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done', 'completed_at' => now()]);

        $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}", [
                'title' => $task->title,
                'priority' => $task->priority,
                'status' => 'pending',
            ]);

        $task->refresh();
        $this->assertNull($task->completed_at);
    }

    // ── DELETE TESTS ─────────────────────────────────────────────
    public function test_task_can_be_deleted(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $this
            ->actingAs($this->user)
            ->delete("/tasks/{$task->id}");

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_other_users_task(): void
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($this->user)
            ->delete("/tasks/{$task->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    // ── TOGGLE TESTS ─────────────────────────────────────────────
    public function test_task_can_be_toggled_to_done(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}/toggle");

        $task->refresh();
        $this->assertEquals('done', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_task_can_be_toggled_back_to_pending(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done', 'completed_at' => now()]);

        $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}/toggle");

        $task->refresh();
        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_toggling_recurrent_task_spawns_next_occurrence(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
            'due_date' => now(),
        ]);

        $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}/toggle");

        // Should have created a new task
        $newTask = Task::where('user_id', $this->user->id)
            ->where('recurrence_type', 'daily')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->assertNotNull($newTask);
        $this->assertNotEquals($task->id, $newTask->id);
        $this->assertTrue($newTask->due_date->isAfter($task->due_date));
    }

    // ── KANBAN TESTS ─────────────────────────────────────────────
    public function test_kanban_view_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/tasks/kanban');

        $response->assertOk();
        $this->assertArrayHasKey('pending', $response['columns']);
        $this->assertArrayHasKey('in_progress', $response['columns']);
        $this->assertArrayHasKey('done', $response['columns']);
    }

    public function test_kanban_tasks_are_organized_by_status(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'in_progress']);
        Task::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'done']);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks/kanban');

        $this->assertCount(1, $response['columns']['pending']);
        $this->assertCount(1, $response['columns']['in_progress']);
        $this->assertCount(2, $response['columns']['done']);
    }

    public function test_kanban_excludes_subtasks(): void
    {
        $parent = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $child = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending', 'parent_task_id' => $parent->id]);

        $response = $this
            ->actingAs($this->user)
            ->get('/tasks/kanban');

        // Should only have parent, not child
        $this->assertCount(1, $response['columns']['pending']);
        $this->assertEquals($parent->id, $response['columns']['pending'][0]['id']);
    }

    public function test_task_status_can_be_updated_via_kanban(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this
            ->actingAs($this->user)
            ->patch("/tasks/{$task->id}/status", ['status' => 'in_progress']);

        $response->assertOk();
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    // ── SUBTASK TESTS ────────────────────────────────────────────
    public function test_subtask_can_be_created(): void
    {
        $parent = Task::factory()->create(['user_id' => $this->user->id]);

        $this
            ->actingAs($this->user)
            ->post("/tasks/{$parent->id}/subtasks", [
                'title' => 'Subtarea',
                'priority' => 'medium',
            ]);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'parent_task_id' => $parent->id,
            'title' => 'Subtarea',
        ]);
    }

    public function test_subtask_inherits_project_from_parent(): void
    {
        $parent = Task::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id
        ]);

        $this
            ->actingAs($this->user)
            ->post("/tasks/{$parent->id}/subtasks", [
                'title' => 'Subtarea',
                'priority' => 'medium',
            ]);

        $subtask = Task::where('parent_task_id', $parent->id)->first();
        $this->assertEquals($this->project->id, $subtask->project_id);
    }

    public function test_subtask_progress_is_calculated(): void
    {
        $parent = Task::factory()->create(['user_id' => $this->user->id]);
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'parent_task_id' => $parent->id,
            'status' => 'pending'
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_task_id' => $parent->id,
            'status' => 'done',
            'completed_at' => now()
        ]);

        $progress = $parent->subtaskProgress();
        $this->assertEquals(1, $progress['done']);
        $this->assertEquals(4, $progress['total']);
    }

    // ── REORDER TESTS ────────────────────────────────────────────
    public function test_tasks_can_be_reordered(): void
    {
        $task1 = Task::factory()->create(['user_id' => $this->user->id, 'sort_order' => 1]);
        $task2 = Task::factory()->create(['user_id' => $this->user->id, 'sort_order' => 2]);

        $response = $this
            ->actingAs($this->user)
            ->post('/tasks/reorder', [
                'tasks' => [
                    ['id' => $task1->id, 'sort_order' => 2],
                    ['id' => $task2->id, 'sort_order' => 1],
                ]
            ]);

        $response->assertOk();
        $task1->refresh();
        $task2->refresh();
        $this->assertEquals(2, $task1->sort_order);
        $this->assertEquals(1, $task2->sort_order);
    }
}
