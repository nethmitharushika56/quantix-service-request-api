<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;
use App\Notifications\ServiceRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestWorkflowController extends Controller
{
    /**
     * Employee submits a Draft or Returned request.
     * Notify the employee's manager.
     */
    public function submit(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if (
            $user->role !== 'employee' ||
            $serviceRequest->employee_id !== $user->id
        ) {
            return response()->json([
                'message' => 'You are not allowed to submit this request.',
            ], 403);
        }

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $user
        ) {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedRequest->status, ['Draft', 'Returned'])) {
                abort(422, 'Only Draft or Returned requests can be submitted.');
            }

            $oldStatus = $lockedRequest->status;

            $lockedRequest->update([
                'status' => 'Submitted',
            ]);

            StatusHistory::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => 'Submitted',
                'comment' => 'Request submitted by employee.',
                'created_at' => now(),
            ]);

            return $lockedRequest->fresh();
        });

        // Notify the employee's manager.
        $manager = $user->manager;

        if ($manager) {
            $manager->notify(new ServiceRequestNotification(
                $updatedRequest->id,
                $updatedRequest->title,
                'submitted',
                'A new service request has been submitted for your review.'
            ));
        }

        return response()->json([
            'message' => 'Service request submitted successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Manager approves, returns or rejects a request.
     * Notify the employee of the decision.
     */
    public function decide(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->role !== 'manager') {
            return response()->json([
                'message' => 'Only managers can review requests.',
            ], 403);
        }

        if ($serviceRequest->employee?->manager_id !== $user->id) {
            return response()->json([
                'message' => 'You can only review requests from your direct reports.',
            ], 403);
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

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $user,
            $validated
        ) {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'Submitted') {
                abort(422, 'Only submitted requests can be reviewed.');
            }

            $oldStatus = $lockedRequest->status;
            $newStatus = $validated['decision'];

            $lockedRequest->update([
                'status' => $newStatus,
            ]);

            StatusHistory::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $validated['comment'],
                'created_at' => now(),
            ]);

            return $lockedRequest->fresh();
        });

        // Notify the employee.
        $serviceRequest->employee->notify(
            new ServiceRequestNotification(
                $updatedRequest->id,
                $updatedRequest->title,
                strtolower($validated['decision']),
                'Your service request has been '
                    . strtolower($validated['decision']) . '.'
            )
        );

        return response()->json([
            'message' => 'Manager decision recorded successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Operations assigns an approved request
     * to an Operations user.
     */
    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->role !== 'operations') {
            return response()->json([
                'message' => 'Only operations users can assign requests.',
            ], 403);
        }

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $assignee = User::findOrFail($validated['assigned_to']);

        if ($assignee->role !== 'operations') {
            return response()->json([
                'message' => 'Requests can only be assigned to operations users.',
            ], 422);
        }

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $assignee,
            $user
        ) {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'Approved') {
                abort(422, 'Only approved requests can be assigned.');
            }

            $previousAssignee = $lockedRequest->assigned_to;

            $lockedRequest->update([
                'assigned_to' => $assignee->id,
            ]);

            StatusHistory::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $user->id,
                'old_status' => $lockedRequest->status,
                'new_status' => $lockedRequest->status,
                'comment' => 'Assignment changed from user ID '
                    . ($previousAssignee ?? 'unassigned')
                    . ' to operations user ID '
                    . $assignee->id . '.',
                'created_at' => now(),
            ]);

            return $lockedRequest->fresh('assignee');
        });

        // Notify the assigned Operations user.
        $assignee->notify(new ServiceRequestNotification(
            $updatedRequest->id,
            $updatedRequest->title,
            'assigned',
            'A service request has been assigned to you.'
        ));

        return response()->json([
            'message' => 'Service request assigned successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Assigned Operations user starts the work.
     */
    public function start(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->role !== 'operations') {
            return response()->json([
                'message' => 'Only operations users can start requests.',
            ], 403);
        }

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $user
        ) {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->assigned_to !== $user->id) {
                abort(403, 'You can only start requests assigned to you.');
            }

            if ($lockedRequest->status !== 'Approved') {
                abort(422, 'Only approved requests can be started.');
            }

            $oldStatus = $lockedRequest->status;

            $lockedRequest->update([
                'status' => 'In Progress',
            ]);

            StatusHistory::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => 'In Progress',
                'comment' => 'Work started by operations.',
                'created_at' => now(),
            ]);

            return $lockedRequest->fresh();
        });

        return response()->json([
            'message' => 'Request marked as In Progress.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Assigned Operations user completes the work.
     */
    public function complete(Request $request, ServiceRequest $serviceRequest)
    {
        $user = $request->user();

        if ($user->role !== 'operations') {
            return response()->json([
                'message' => 'Only operations users can complete requests.',
            ], 403);
        }

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $user
        ) {
            $lockedRequest = ServiceRequest::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->assigned_to !== $user->id) {
                abort(403, 'You can only complete requests assigned to you.');
            }

            if ($lockedRequest->status !== 'In Progress') {
                abort(422, 'Only In Progress requests can be completed.');
            }

            $oldStatus = $lockedRequest->status;

            $lockedRequest->update([
                'status' => 'Completed',
            ]);

            StatusHistory::create([
                'service_request_id' => $lockedRequest->id,
                'actor_id' => $user->id,
                'old_status' => $oldStatus,
                'new_status' => 'Completed',
                'comment' => 'Work completed by operations.',
                'created_at' => now(),
            ]);

            return $lockedRequest->fresh();
        });

        return response()->json([
            'message' => 'Request marked as Completed.',
            'data' => $updatedRequest,
        ]);
    }
}
