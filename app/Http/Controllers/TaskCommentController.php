<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Task $task, Request $request)
    {
        $request->validate([
            'content' => 'required|string|min:1|max:5000',
        ]);

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return response()->json([
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user'       => [
                'id'    => $comment->user->id,
                'name'  => $comment->user->name,
                'email' => $comment->user->email,
            ],
            'created_at' => $comment->created_at->locale('es')->diffForHumans(),
            'created_at_full' => $comment->created_at->locale('es')->format('d/m/Y H:i'),
        ]);
    }

    public function update(TaskComment $comment, Request $request)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string|min:1|max:5000',
        ]);

        $comment->update(['content' => $request->content]);

        return response()->json([
            'id'      => $comment->id,
            'content' => $comment->content,
            'updated' => true,
        ]);
    }

    public function destroy(TaskComment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['deleted' => true]);
    }

    public function getTaskComments(Task $task)
    {
        $comments = $task->comments()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'content'        => $c->content,
                'user'           => [
                    'id'    => $c->user->id,
                    'name'  => $c->user->name,
                    'email' => $c->user->email,
                ],
                'created_at'     => $c->created_at->locale('es')->diffForHumans(),
                'created_at_full' => $c->created_at->locale('es')->format('d/m/Y H:i'),
                'is_owner'       => $c->user_id === auth()->id(),
            ]);

        return response()->json($comments);
    }
}
