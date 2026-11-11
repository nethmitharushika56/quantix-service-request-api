<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function createManager(): User
    {
        return User::factory()->create([
            'role' => 'manager',
        ]);
    }

    private function createEmployee(User $manager): User
    {
        return User::factory()->create([
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);
    }

    private function createRequest(
        User $employee,
        string $status = 'Submitted'
    ): ServiceRequest {
        return ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => $status,
        ]);
    }

    public function test_manager_can_view_direct_reports_requests(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->getJson('/api/service-requests');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $serviceRequest->id,
                'title' => 'Laptop Repair',
            ]);
    }

    public function test_manager_cannot_view_unrelated_employees_request(): void
    {
        $manager = $this->createManager();
        $anotherManager = $this->createManager();

        $employee = $this->createEmployee($anotherManager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->getJson('/api/service-requests/' . $serviceRequest->id);

        $response->assertStatus(403);
    }

    public function test_manager_can_approve_submitted_request(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Approved',
                    'comment' => 'Request approved.',
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('status_histories', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $manager->id,
            'old_status' => 'Submitted',
            'new_status' => 'Approved',
            'comment' => 'Request approved.',
        ]);
    }

    public function test_manager_can_return_submitted_request(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Returned',
                    'comment' => 'Please provide more details.',
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Returned');
    }

    public function test_manager_can_reject_submitted_request(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Rejected',
                    'comment' => 'Request does not meet requirements.',
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Rejected');
    }

    public function test_manager_cannot_review_draft_request(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee, 'Draft');

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Approved',
                    'comment' => 'Approved.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'Draft',
        ]);
    }

    public function test_manager_cannot_review_another_managers_request(): void
    {
        $manager = $this->createManager();
        $anotherManager = $this->createManager();

        $employee = $this->createEmployee($anotherManager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Approved',
                    'comment' => 'Approved.',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'Submitted',
        ]);
    }

    public function test_manager_decision_requires_valid_input(): void
    {
        $manager = $this->createManager();
        $employee = $this->createEmployee($manager);

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Pending',
                ]
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decision', 'comment']);
    }
}
