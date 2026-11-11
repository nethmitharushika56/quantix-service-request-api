<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Add a comment to a service request.
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        // Employee can comment on their own requests.
        $isEmployee = $user->role === 'employee'
            && $serviceRequest->employee_id === $user->id;

        // Manager can comment on direct reports' requests.
        $isManager = $user->role === 'manager'
            && $serviceRequest->employee?->manager_id === $user->id;

        // Operations can comment on all requests.
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
            'data' => $comment->load('user:id,name,email'),
        ], 201);
    }
}
