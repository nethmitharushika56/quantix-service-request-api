<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployee(): User
    {
        return User::factory()->create([
            'role' => 'employee',
        ]);
    }

    private function createRequest(
        User $employee,
        string $status = 'Draft'
    ): ServiceRequest {
        return ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => $status,
        ]);
    }

    public function test_employee_can_view_own_requests(): void
    {
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($employee)
            ->getJson('/api/service-requests');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Laptop Repair',
            ]);
    }

    public function test_employee_cannot_view_another_employees_request(): void
    {
        $employee = $this->createEmployee();
        $anotherEmployee = $this->createEmployee();

        $serviceRequest = $this->createRequest($anotherEmployee);

        $response = $this->actingAs($employee)
            ->getJson('/api/service-requests/' . $serviceRequest->id);

        $response->assertStatus(403);
    }

    public function test_employee_can_create_service_request(): void
    {
        $employee = $this->createEmployee();

        $response = $this->actingAs($employee)
            ->postJson('/api/service-requests', [
                'title' => 'New Laptop',
                'description' => 'I need a new laptop.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('service_requests', [
            'employee_id' => $employee->id,
            'title' => 'New Laptop',
            'status' => 'Draft',
        ]);
    }

    public function test_employee_can_update_own_draft_request(): void
    {
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest($employee, 'Draft');

        $response = $this->actingAs($employee)
            ->putJson('/api/service-requests/' . $serviceRequest->id, [
                'title' => 'Updated Laptop Repair',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'title' => 'Updated Laptop Repair',
        ]);
    }

    public function test_employee_can_update_own_returned_request(): void
    {
        $employee = $this->createEmployee();

        $serviceRequest = $this->createRequest($employee, 'Returned');

        $response = $this->actingAs($employee)
            ->putJson('/api/service-requests/' . $serviceRequest->id, [
                'description' => 'Updated description.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'description' => 'Updated description.',
        ]);
    }

    public function test_employee_cannot_update_request_in_other_statuses(): void
    {
        $employee = $this->createEmployee();

        $statuses = [
            'Submitted',
            'Approved',
            'Rejected',
            'In Progress',
            'Completed',
        ];

        foreach ($statuses as $status) {
            $serviceRequest = $this->createRequest($employee, $status);

            $response = $this->actingAs($employee)
                ->putJson('/api/service-requests/' . $serviceRequest->id, [
                    'title' => 'Unauthorized Update',
                ]);

            $response->assertStatus(422);

            $this->assertDatabaseHas('service_requests', [
                'id' => $serviceRequest->id,
                'title' => 'Laptop Repair',
                'status' => $status,
            ]);
        }
    }

    public function test_employee_cannot_delete_another_employees_request(): void
    {
        $employee = $this->createEmployee();
        $anotherEmployee = $this->createEmployee();

        $serviceRequest = $this->createRequest($anotherEmployee);

        $response = $this->actingAs($employee)
            ->deleteJson('/api/service-requests/' . $serviceRequest->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
        ]);
    }
}
