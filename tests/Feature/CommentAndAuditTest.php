<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployee(?User $manager = null): User
    {
        return User::factory()->create([
            'role' => 'employee',
            'manager_id' => $manager?->id,
        ]);
    }

    private function createRequest(User $employee): ServiceRequest
    {
        return ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => 'Submitted',
        ]);
    }

    public function test_employee_can_comment_on_own_request(): void
    {
        $employee = $this->createEmployee();
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($employee)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => 'I need this urgently.']
            );

        $response->assertStatus(201)
            ->assertJsonPath('data.comment', 'I need this urgently.')
            ->assertJsonPath('data.user.id', $employee->id);

        $this->assertDatabaseHas('comments', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $employee->id,
            'comment' => 'I need this urgently.',
        ]);
    }

    public function test_employee_cannot_comment_on_another_employees_request(): void
    {
        $employee = $this->createEmployee();
        $anotherEmployee = $this->createEmployee();

        $serviceRequest = $this->createRequest($anotherEmployee);

        $response = $this->actingAs($employee)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => 'Unauthorized comment.']
            );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('comments', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $employee->id,
        ]);
    }

    public function test_manager_can_comment_on_direct_reports_request(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $employee = $this->createEmployee($manager);
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => 'Please provide more details.']
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $manager->id,
        ]);
    }

    public function test_manager_cannot_comment_on_unrelated_request(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $anotherManager = User::factory()->create([
            'role' => 'manager',
        ]);

        $employee = $this->createEmployee($anotherManager);
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => 'Unauthorized comment.']
            );

        $response->assertStatus(403);
    }

    public function test_operations_can_comment_on_any_request(): void
    {
        $operations = User::factory()->create([
            'role' => 'operations',
        ]);

        $employee = $this->createEmployee();
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($operations)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => 'Work has been scheduled.']
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $operations->id,
        ]);
    }

    public function test_comment_is_required(): void
    {
        $employee = $this->createEmployee();
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($employee)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/comments',
                ['comment' => '']
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_submission_creates_status_history(): void
    {
        $employee = $this->createEmployee();

        $serviceRequest = ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Network Access',
            'description' => 'I need network access.',
            'status' => 'Draft',
        ]);

        $response = $this->actingAs($employee)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/submit'
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Submitted');

        $this->assertDatabaseHas('status_histories', [
            'service_request_id' => $serviceRequest->id,
            'actor_id' => $employee->id,
            'old_status' => 'Draft',
            'new_status' => 'Submitted',
        ]);

        $history = StatusHistory::where(
            'service_request_id',
            $serviceRequest->id
        )->first();

        $this->assertNotNull($history->created_at);
    }

    public function test_manager_decision_creates_audit_history(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $employee = $this->createEmployee($manager);
        $serviceRequest = $this->createRequest($employee);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/service-requests/' . $serviceRequest->id . '/decision',
                [
                    'decision' => 'Approved',
                    'comment' => 'Approved by manager.',
                ]
            );

        $response->assertStatus(200);

        $history = StatusHistory::where(
            'service_request_id',
            $serviceRequest->id
        )->firstOrFail();

        $this->assertSame($manager->id, $history->actor_id);
        $this->assertSame('Submitted', $history->old_status);
        $this->assertSame('Approved', $history->new_status);
        $this->assertSame('Approved by manager.', $history->comment);
        $this->assertNotNull($history->created_at);
    }
}
