<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest as ServiceRequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    /**
     * List service requests with search, filters, sorting and pagination.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => [
                'sometimes',
                'in:Draft,Submitted,Returned,Approved,Rejected,In Progress,Completed',
            ],
            'employee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'sort_by' => ['sometimes', 'in:created_at,title,status'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $query = ServiceRequestModel::query()
            ->with([
                'employee:id,name,email',
                'assignee:id,name,email',
            ]);

        // Role-based access control.
        if ($user->role === 'employee') {
            $query->where('employee_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('manager_id', $user->id);
            });
        }

        // Search by title or description.
        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters.
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if (isset($validated['assigned_to'])) {
            $query->where('assigned_to', $validated['assigned_to']);
        }

        // Sorting.
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        $query->orderBy($sortBy, $sortDirection);

        // Pagination.
        $perPage = $validated['per_page'] ?? 10;

        return response()->json([
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * Create a service request.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'employee') {
            return response()->json([
                'message' => 'Only employees can create service requests.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $serviceRequest = $request->user()
            ->serviceRequests()
            ->create($validated);

        return response()->json([
            'message' => 'Service request created successfully.',
            'data' => $serviceRequest,
        ], 201);
    }

    /**
     * View a single service request.
     */
    public function show(Request $request, ServiceRequestModel $serviceRequest)
    {
        if (!$this->canView($request->user(), $serviceRequest)) {
            return response()->json([
                'message' => 'You are not allowed to view this request.',
            ], 403);
        }

        $serviceRequest->load([
            'employee:id,name,email',
            'assignee:id,name,email',
            'comments.user:id,name,email',
            'statusHistories.actor:id,name,email',
        ]);

        return response()->json([
            'data' => $serviceRequest,
        ]);
    }

    /**
     * Update a Draft or Returned request.
     */
    public function update(Request $request, ServiceRequestModel $serviceRequest)
    {
        $user = $request->user();

        if (
            $user->role !== 'employee' ||
            $serviceRequest->employee_id !== $user->id
        ) {
            return response()->json([
                'message' => 'You are not allowed to update this request.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
        ]);

        if (empty($validated)) {
            return response()->json([
                'message' => 'At least one field must be provided.',
            ], 422);
        }

        $updatedRequest = DB::transaction(function () use (
            $serviceRequest,
            $user,
            $validated
        ) {
            $lockedRequest = ServiceRequestModel::whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedRequest->employee_id !== $user->id ||
                !in_array($lockedRequest->status, ['Draft', 'Returned'])
            ) {
                abort(422, 'Only your Draft or Returned requests can be edited.');
            }

            $lockedRequest->update($validated);

            return $lockedRequest->fresh();
        });

        return response()->json([
            'message' => 'Service request updated successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Delete a Draft request.
     */
    public function destroy(Request $request, ServiceRequestModel $serviceRequest)
    {
        $user = $request->user();

        if (
            $user->role !== 'employee' ||
            $serviceRequest->employee_id !== $user->id
        ) {
            return response()->json([
                'message' => 'You are not allowed to delete this request.',
            ], 403);
        }

        if ($serviceRequest->status !== 'Draft') {
            return response()->json([
                'message' => 'Only Draft requests can be deleted.',
            ], 422);
        }

        $serviceRequest->delete();

        return response()->json([
            'message' => 'Service request deleted successfully.',
        ]);
    }

    /**
     * Check whether the current user can view a request.
     */
    private function canView($user, ServiceRequestModel $serviceRequest): bool
    {
        if ($user->role === 'operations') {
            return true;
        }

        if ($user->role === 'employee') {
            return $serviceRequest->employee_id === $user->id;
        }

        if ($user->role === 'manager') {
            return $serviceRequest->employee?->manager_id === $user->id;
        }

        return false;
    }
}
