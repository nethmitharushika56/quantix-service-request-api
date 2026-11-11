<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        $isEmployee = $serviceRequest->employee_id === $user->id;

        $isManager = $serviceRequest->employee?->manager_id === $user->id;

        $isOperations = $user->role === 'operations';

        if (!$isEmployee && !$isManager && !$isOperations) {
            return response()->json([
                'message' => 'You are not allowed to comment on this request.',
            ], 403);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $comment = Comment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $comment->load('user'),
        ], 201);
    }
}