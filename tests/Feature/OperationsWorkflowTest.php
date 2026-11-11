<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createOperationsUser(): User
    {
        return User::factory()->create([
            'role' => 'operations',
        ]);
    }

    private function createEmployee(): User
    {
        return User::factory()->create([
            'role' => 'employee',
        ]);
    }

    private function createRequest(
        User $employee,
        string $status = 'Approved',
        ?int $assignedTo = null
    ): ServiceRequest {
        return ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => $status,
            'assigned_to' => $assignedTo,
        ]);
    }

    public function test_operations_can_assign_approved_request(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/assign',
                [
                    'assigned_to' => $operations->id,
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_to', $operations->id);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'assigned_to' => $operations->id,
            'status' => 'Approved',
        ]);
    }

    public function test_operations_cannot_assign_request_to_employee(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/assign',
                [
                    'assigned_to' => $employee->id,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'assigned_to' => null,
        ]);
    }

    public function test_operations_cannot_assign_unapproved_request(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'Submitted'
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/assign',
                [
                    'assigned_to' => $operations->id,
                ]
            );

        $response->assertStatus(422);
    }

    public function test_only_assigned_operations_user_can_start_work(): void
    {
        $assignedOperations = $this->createOperationsUser();
        $anotherOperations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'Approved',
            $assignedOperations->id
        );

        $response = $this->actingAs($anotherOperations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/start'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'Approved',
        ]);
    }

    public function test_assigned_operations_user_can_start_work(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'Approved',
            $operations->id
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/start'
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'In Progress');

        $this->assertDatabaseHas('status_histories', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $operations->id,
            'old_status' => 'Approved',
            'new_status' => 'In Progress',
        ]);
    }

    public function test_assigned_operations_user_can_complete_work(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'In Progress',
            $operations->id
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/complete'
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Completed');

        $this->assertDatabaseHas('status_histories', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $operations->id,
            'old_status' => 'In Progress',
            'new_status' => 'Completed',
        ]);
    }

    public function test_operations_cannot_complete_an_unassigned_request(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'In Progress'
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/complete'
            );

        $response->assertStatus(403);
    }

    public function test_completed_request_cannot_be_completed_again(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'Completed',
            $operations->id
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/complete'
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'Completed',
        ]);
    }

    public function test_rejected_request_cannot_be_started(): void
    {
        $operations = $this->createOperationsUser();
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest(
            $employee,
            'Rejected',
            $operations->id
        );

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/start'
            );

        $response->assertStatus(422);
    }

    public function test_non_operations_user_cannot_assign_requests(): void
    {
        $employee = $this->createEmployee();
        $anotherEmployee = $this->createEmployee();

        $serviceRequest = $this->createRequest($anotherEmployee);

        $response = $this->actingAs($employee)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/assign',
                [
                    'assigned_to' => $employee->id,
                ]
            );

        $response->assertStatus(403);
    }
}
