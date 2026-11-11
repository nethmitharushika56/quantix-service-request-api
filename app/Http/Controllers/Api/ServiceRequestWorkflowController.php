<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use Illuminate\Http\Request;

class ServiceRequestWorkflowController extends Controller
{
    public function submit(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        // Only the employee who owns the request can submit it.
        if (
            $user->role !== 'employee' ||
            $serviceRequest->employee_id !== $user->id
        ) {
            return response()->json([
                'message' => 'You are not allowed to submit this request.',
            ], 403);
        }

        // Only Draft and Returned requests can be submitted.
        if (!in_array($serviceRequest->status, ['Draft', 'Returned'])) {
            return response()->json([
                'message' => 'Only Draft or Returned requests can be submitted.',
            ], 422);
        }

        $oldStatus = $serviceRequest->status;

        $serviceRequest->update([
            'status' => 'Submitted',
        ]);

        // Record the status transition.
        StatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => 'Submitted',
            'comment' => 'Request submitted by employee.',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Service request submitted successfully.',
            'data' => $serviceRequest->fresh(),
        ], 200);
    }

    public function decide(Request $request, ServiceRequest $serviceRequest)
{
    $user = $request->user();

    if ($user->role !== 'manager') {
        return response()->json([
            'message' => 'Only managers can review requests.',
        ], 403);
    }

    // Check whether the employee reports directly to this manager.
    if (
        $serviceRequest->employee->manager_id !== $user->id
    ) {
        return response()->json([
            'message' => 'You can only review requests from your direct reports.',
        ], 403);
    }

    if ($serviceRequest->status !== 'Submitted') {
        return response()->json([
            'message' => 'Only submitted requests can be reviewed.',
        ], 422);
    }

    $validated = $request->validate([
        'decision' => [
            'required',
            'in:Approved,Returned,Rejected',
        ],
        'comment' => [
            'required',
            'string',
            'max:1000',
        ],
    ]);

    $oldStatus = $serviceRequest->status;
    $newStatus = $validated['decision'];

    $serviceRequest->update([
        'status' => $newStatus,
    ]);

    StatusHistory::create([
        'service_request_id' => $serviceRequest->id,
        'actor_id' => $user->id,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'comment' => $validated['comment'],
        'created_at' => now(),
    ]);

    return response()->json([
        'message' => 'Manager decision recorded successfully.',
        'data' => $serviceRequest->fresh(),
    ]);
}

public function assign(Request $request, ServiceRequest $serviceRequest)
{
    $user = $request->user();

    if ($user->role !== 'operations') {
        return response()->json([
            'message' => 'Only operations users can assign requests.',
        ], 403);
    }

    if ($serviceRequest->status !== 'Approved') {
        return response()->json([
            'message' => 'Only approved requests can be assigned.',
        ], 422);
    }

    $validated = $request->validate([
        'assigned_to' => ['required', 'integer', 'exists:users,id'],
    ]);

    $assignee = \App\Models\User::findOrFail(
        $validated['assigned_to']
    );

    if ($assignee->role !== 'operations') {
        return response()->json([
            'message' => 'Requests can only be assigned to operations users.',
        ], 422);
    }

    $serviceRequest->update([
        'assigned_to' => $assignee->id,
    ]);

    return response()->json([
        'message' => 'Service request assigned successfully.',
        'data' => $serviceRequest->fresh('assignee'),
    ]);
}

public function start(Request $request, ServiceRequest $serviceRequest)
{
    $user = $request->user();

    if ($user->role !== 'operations') {
        return response()->json([
            'message' => 'Only operations users can start requests.',
        ], 403);
    }

    if ($serviceRequest->assigned_to !== $user->id) {
        return response()->json([
            'message' => 'You can only start requests assigned to you.',
        ], 403);
    }

    if ($serviceRequest->status !== 'Approved') {
        return response()->json([
            'message' => 'Only approved requests can be started.',
        ], 422);
    }

    $oldStatus = $serviceRequest->status;

    $serviceRequest->update([
        'status' => 'In Progress',
    ]);

    StatusHistory::create([
        'service_request_id' => $serviceRequest->id,
        'actor_id' => $user->id,
        'old_status' => $oldStatus,
        'new_status' => 'In Progress',
        'comment' => 'Work started by operations.',
        'created_at' => now(),
    ]);

    return response()->json([
        'message' => 'Request marked as In Progress.',
        'data' => $serviceRequest->fresh(),
    ]);
}

public function complete(Request $request, ServiceRequest $serviceRequest)
{
    $user = $request->user();

    if ($user->role !== 'operations') {
        return response()->json([
            'message' => 'Only operations users can complete requests.',
        ], 403);
    }

    if ($serviceRequest->assigned_to !== $user->id) {
        return response()->json([
            'message' => 'You can only complete requests assigned to you.',
        ], 403);
    }

    if ($serviceRequest->status !== 'In Progress') {
        return response()->json([
            'message' => 'Only In Progress requests can be completed.',
        ], 422);
    }

    $oldStatus = $serviceRequest->status;

    $serviceRequest->update([
        'status' => 'Completed',
    ]);

    StatusHistory::create([
        'service_request_id' => $serviceRequest->id,
        'actor_id' => $user->id,
        'old_status' => $oldStatus,
        'new_status' => 'Completed',
        'comment' => 'Work completed by operations.',
        'created_at' => now(),
    ]);

    return response()->json([
        'message' => 'Request marked as Completed.',
        'data' => $serviceRequest->fresh(),
    ]);
}
}