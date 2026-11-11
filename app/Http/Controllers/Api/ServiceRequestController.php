<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest as ServiceRequestModel;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();

    $query = ServiceRequestModel::with(['employee', 'assignee']);

    // Role-based access
    if ($user->role === 'employee') {
        $query->where('employee_id', $user->id);
    } elseif ($user->role === 'manager') {
        $query->whereHas('employee', function ($employeeQuery) use ($user) {
            $employeeQuery->where('manager_id', $user->id);
        });
    }

    // Search by title or description
    if ($request->filled('search')) {
        $search = $request->input('search');

        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Filter by status
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    // Filter by employee
    if ($request->filled('employee_id')) {
        $query->where('employee_id', $request->input('employee_id'));
    }

    // Filter by assigned operations user
    if ($request->filled('assigned_to')) {
        $query->where('assigned_to', $request->input('assigned_to'));
    }

    // Sorting
    $sortBy = $request->input('sort_by', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    $allowedSortColumns = ['created_at', 'title', 'status'];

    if (!in_array($sortBy, $allowedSortColumns)) {
        return response()->json([
            'message' => 'Invalid sorting column.',
        ], 422);
    }

    if (!in_array($sortDirection, ['asc', 'desc'])) {
        return response()->json([
            'message' => 'Invalid sorting direction.',
        ], 422);
    }

    $query->orderBy($sortBy, $sortDirection);

    // Pagination
    $perPage = $request->input('per_page', 10);

    if (!filter_var($perPage, FILTER_VALIDATE_INT) || $perPage < 1 || $perPage > 100) {
        return response()->json([
            'message' => 'per_page must be between 1 and 100.',
        ], 422);
    }

    return response()->json([
        'data' => $query->paginate($perPage),
    ]);
}

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

    public function show(Request $request, ServiceRequestModel $serviceRequest)
    {
        if (!$this->canView($request->user(), $serviceRequest)) {
            return response()->json([
                'message' => 'You are not allowed to view this request.',
            ], 403);
        }

        return response()->json([
            'data' => $serviceRequest->load([
                'employee',
                'assignee',
                'comments.user',
                'statusHistories.actor',
            ]),
        ]);
    }

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

        if (!in_array($serviceRequest->status, ['Draft', 'Returned'])) {
            return response()->json([
                'message' => 'Only Draft or Returned requests can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
        ]);

        $serviceRequest->update($validated);

        return response()->json([
            'message' => 'Service request updated successfully.',
            'data' => $serviceRequest->fresh(),
        ]);
    }

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
