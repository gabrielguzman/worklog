<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    // ── SUBTASK PROGRESS TESTS ───────────────────────────────────
    public function test_subtask_progress_returns_zero_when_no_subtasks(): void
    {
        $task = Task::factory()->create();

        $progress = $task->subtaskProgress();

        $this->assertEquals(0, $progress['done']);
        $this->assertEquals(0, $progress['total']);
    }

    public function test_subtask_progress_counts_correctly(): void
    {
        $task = Task::factory()->create();
        Task::factory()->count(3)->create(['parent_task_id' => $task->id, 'status' => 'pending']);
        Task::factory()->count(2)->create(['parent_task_id' => $task->id, 'status' => 'done']);

        $progress = $task->subtaskProgress();

        $this->assertEquals(2, $progress['done']);
        $this->assertEquals(5, $progress['total']);
    }

    public function test_subtask_progress_only_counts_immediate_children(): void
    {
        $grandparent = Task::factory()->create();
        $parent = Task::factory()->create(['parent_task_id' => $grandparent->id]);
        Task::factory()->count(3)->create(['parent_task_id' => $parent->id]);

        $progress = $grandparent->subtaskProgress();

        // Should only count direct children (1), not grandchildren
        $this->assertEquals(0, $progress['done']);
        $this->assertEquals(1, $progress['total']);
    }

    // ── RECURRENCE TYPE TESTS ────────────────────────────────────
    public function test_is_recurrent_returns_false_for_none(): void
    {
        $task = Task::factory()->create(['recurrence_type' => 'none']);

        $this->assertFalse($task->isRecurrent());
    }

    public function test_is_recurrent_returns_true_for_daily(): void
    {
        $task = Task::factory()->create(['recurrence_type' => 'daily']);

        $this->assertTrue($task->isRecurrent());
    }

    public function test_is_recurrent_returns_true_for_weekly(): void
    {
        $task = Task::factory()->create(['recurrence_type' => 'weekly']);

        $this->assertTrue($task->isRecurrent());
    }

    public function test_is_recurrent_returns_true_for_monthly(): void
    {
        $task = Task::factory()->create(['recurrence_type' => 'monthly']);

        $this->assertTrue($task->isRecurrent());
    }

    // ── NEXT DUE DATE TESTS ──────────────────────────────────────
    public function test_next_due_date_returns_null_for_non_recurrent(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'none',
            'due_date' => now(),
        ]);

        $this->assertNull($task->nextDueDate());
    }

    public function test_next_due_date_returns_null_without_due_date(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'due_date' => null,
        ]);

        $this->assertNull($task->nextDueDate());
    }

    public function test_next_due_date_adds_days_for_daily_recurrence(): void
    {
        $dueDate = now();
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'recurrence_interval' => 2,
            'due_date' => $dueDate,
        ]);

        $nextDate = $task->nextDueDate();

        $this->assertEquals($dueDate->copy()->addDays(2)->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    public function test_next_due_date_adds_weeks_for_weekly_recurrence(): void
    {
        $dueDate = now();
        $task = Task::factory()->create([
            'recurrence_type' => 'weekly',
            'recurrence_interval' => 3,
            'due_date' => $dueDate,
        ]);

        $nextDate = $task->nextDueDate();

        $this->assertEquals($dueDate->copy()->addWeeks(3)->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    public function test_next_due_date_adds_months_for_monthly_recurrence(): void
    {
        $dueDate = now();
        $task = Task::factory()->create([
            'recurrence_type' => 'monthly',
            'recurrence_interval' => 1,
            'due_date' => $dueDate,
        ]);

        $nextDate = $task->nextDueDate();

        $this->assertEquals($dueDate->copy()->addMonths(1)->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    // ── SPAWN NEXT RECURRENCE TESTS ──────────────────────────────
    public function test_spawn_next_recurrence_returns_null_for_non_recurrent(): void
    {
        $task = Task::factory()->create(['recurrence_type' => 'none']);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNull($nextTask);
    }

    public function test_spawn_next_recurrence_creates_new_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
            'due_date' => now(),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNotNull($nextTask);
        $this->assertNotEquals($task->id, $nextTask->id);
        $this->assertEquals($user->id, $nextTask->user_id);
    }

    public function test_spawn_next_recurrence_sets_pending_status(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'due_date' => now(),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertEquals('pending', $nextTask->status);
    }

    public function test_spawn_next_recurrence_clears_completed_at(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'due_date' => now(),
            'completed_at' => now(),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNull($nextTask->completed_at);
    }

    public function test_spawn_next_recurrence_updates_due_date(): void
    {
        $originalDueDate = now();
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
            'due_date' => $originalDueDate,
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNotEquals(
            $originalDueDate->format('Y-m-d'),
            $nextTask->due_date->format('Y-m-d')
        );
        $this->assertEquals(
            $originalDueDate->copy()->addDays(1)->format('Y-m-d'),
            $nextTask->due_date->format('Y-m-d')
        );
    }

    public function test_spawn_next_recurrence_resets_sort_order(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'due_date' => now(),
            'sort_order' => 5,
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertEquals(0, $nextTask->sort_order);
    }

    public function test_spawn_next_recurrence_syncs_tags(): void
    {
        $user = User::factory()->create();
        $tag1 = \App\Models\Tag::factory()->create(['user_id' => $user->id]);
        $tag2 = \App\Models\Tag::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'recurrence_type' => 'weekly',
            'due_date' => now(),
        ]);
        $task->tags()->attach([$tag1->id, $tag2->id]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertCount(2, $nextTask->tags);
        $this->assertTrue($nextTask->tags->contains($tag1));
        $this->assertTrue($nextTask->tags->contains($tag2));
    }

    public function test_spawn_next_recurrence_respects_recurrence_ends_at(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
            'due_date' => now()->addDays(10),
            'recurrence_ends_at' => now()->addDays(5),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNull($nextTask);
    }

    public function test_spawn_next_recurrence_before_recurrence_ends_at(): void
    {
        $task = Task::factory()->create([
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
            'due_date' => now(),
            'recurrence_ends_at' => now()->addDays(10),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertNotNull($nextTask);
    }

    public function test_spawn_next_recurrence_copies_all_fields(): void
    {
        $task = Task::factory()->create([
            'title' => 'Daily standup',
            'description' => 'Brief team sync',
            'priority' => 'high',
            'project_id' => 5,
            'recurrence_type' => 'daily',
            'due_date' => now(),
        ]);

        $nextTask = $task->spawnNextRecurrence();

        $this->assertEquals('Daily standup', $nextTask->title);
        $this->assertEquals('Brief team sync', $nextTask->description);
        $this->assertEquals('high', $nextTask->priority);
        $this->assertEquals(5, $nextTask->project_id);
    }

    // ── MARK DONE TESTS ──────────────────────────────────────────
    public function test_mark_done_sets_status_and_completed_at(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $task->markDone();

        $this->assertEquals('done', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_mark_done_updates_database(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $task->markDone();
        $task->refresh();

        $this->assertEquals('done', $task->status);
        $this->assertNotNull($task->completed_at);
    }
}
