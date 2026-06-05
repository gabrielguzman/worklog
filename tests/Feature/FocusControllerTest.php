<?php

namespace Tests\Feature;

use App\Models\FocusSession;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FocusControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->task = Task::factory()->create(['user_id' => $this->user->id]);
    }

    // ── INDEX TESTS ──────────────────────────────────────────────
    public function test_focus_index_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $response->assertOk();
        $this->assertArrayHasKey('pendingTasks', $response);
        $this->assertArrayHasKey('recentSessions', $response);
        $this->assertArrayHasKey('todayMinutes', $response);
        $this->assertArrayHasKey('weekSessions', $response);
    }

    public function test_focus_index_shows_pending_and_in_progress_tasks(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'in_progress']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);

        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $pendingTasks = $response['pendingTasks'];
        $this->assertCount(2, $pendingTasks);
    }

    public function test_focus_index_shows_recent_sessions(): void
    {
        FocusSession::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $this->assertCount(5, $response['recentSessions']);
    }

    public function test_focus_index_calculates_today_minutes(): void
    {
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'duration_minutes' => 30,
            'status' => 'completed',
            'started_at' => now(),
        ]);
        FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'duration_minutes' => 25,
            'status' => 'completed',
            'started_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $this->assertEquals(55, $response['todayMinutes']);
    }

    public function test_focus_index_counts_week_sessions(): void
    {
        FocusSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'started_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $this->assertEquals(3, $response['weekSessions']);
    }

    // ── START SESSION TESTS ──────────────────────────────────────
    public function test_focus_session_can_be_started(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/focus/start', [
                'task_id' => $this->task->id,
                'duration_minutes' => 25,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('focus_sessions', [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'duration_minutes' => 25,
            'status' => 'running',
        ]);
    }

    public function test_focus_session_can_be_started_without_task(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/focus/start', [
                'duration_minutes' => 30,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('focus_sessions', [
            'user_id' => $this->user->id,
            'task_id' => null,
            'duration_minutes' => 30,
            'status' => 'running',
        ]);
    }

    public function test_focus_session_requires_valid_duration(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/focus/start', [
                'task_id' => $this->task->id,
                'duration_minutes' => 0, // invalid
            ]);

        $response->assertSessionHasErrors('duration_minutes');
    }

    public function test_focus_session_duration_cannot_exceed_120(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post('/focus/start', [
                'task_id' => $this->task->id,
                'duration_minutes' => 121, // exceeds max
            ]);

        $response->assertSessionHasErrors('duration_minutes');
    }

    public function test_focus_session_start_sets_running_status(): void
    {
        $this
            ->actingAs($this->user)
            ->post('/focus/start', [
                'task_id' => $this->task->id,
                'duration_minutes' => 25,
            ]);

        $session = FocusSession::latest()->first();
        $this->assertEquals('running', $session->status);
        $this->assertNotNull($session->started_at);
    }

    // ── COMPLETE SESSION TESTS ───────────────────────────────────
    public function test_focus_session_can_be_completed(): void
    {
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/complete", [
                'notes' => 'Session notes',
            ]);

        $response->assertOk();
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertEquals('Session notes', $session->notes);
        $this->assertNotNull($session->ended_at);
    }

    public function test_completing_session_updates_task_status(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $task->id,
            'status' => 'running',
        ]);

        $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/complete", []);

        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    public function test_completing_session_can_create_entry(): void
    {
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/complete", [
                'create_entry' => true,
                'entry_title' => 'Completed focus work',
                'notes' => 'Did some work',
            ]);

        $this->assertDatabaseHas('entries', [
            'user_id' => $this->user->id,
            'title' => 'Completed focus work',
            'content' => 'Did some work',
        ]);
    }

    public function test_completing_session_entry_inherits_task_project(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $task->id,
            'status' => 'running',
        ]);

        $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/complete", [
                'create_entry' => true,
                'entry_title' => 'Work entry',
            ]);

        $entry = $this->user->entries()->latest()->first();
        $this->assertEquals($task->project_id, $entry->project_id);
    }

    public function test_user_cannot_complete_other_users_session(): void
    {
        $otherUser = User::factory()->create();
        $session = FocusSession::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'running',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/complete", []);

        $response->assertForbidden();
    }

    // ── CANCEL SESSION TESTS ─────────────────────────────────────
    public function test_focus_session_can_be_cancelled(): void
    {
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/cancel", []);

        $response->assertOk();
        $session->refresh();
        $this->assertEquals('cancelled', $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_user_cannot_cancel_other_users_session(): void
    {
        $otherUser = User::factory()->create();
        $session = FocusSession::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'running',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/cancel", []);

        $response->assertForbidden();
    }

    public function test_cancelled_session_does_not_create_entry(): void
    {
        $session = FocusSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $this
            ->actingAs($this->user)
            ->patch("/focus/{$session->id}/cancel", []);

        $entriesBefore = $this->user->entries()->count();
        $this->assertEquals(0, $entriesBefore);
    }

    // ── AUTHORIZATION TESTS ──────────────────────────────────────
    public function test_user_can_only_see_their_pending_tasks_in_focus(): void
    {
        $otherUser = User::factory()->create();
        Task::factory()->create(['user_id' => $otherUser->id, 'status' => 'pending']);

        $response = $this
            ->actingAs($this->user)
            ->get('/focus');

        $this->assertCount(0, $response['pendingTasks']);
    }

    public function test_focus_requires_authentication(): void
    {
        $response = $this->get('/focus');

        $response->assertRedirect('/login');
    }

    public function test_start_session_requires_authentication(): void
    {
        $response = $this->post('/focus/start', [
            'duration_minutes' => 25,
        ]);

        $response->assertRedirect('/login');
    }
}
