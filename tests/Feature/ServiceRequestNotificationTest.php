<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServiceRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_request_notifies_manager(): void
    {
        Notification::fake();

        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $serviceRequest = ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => 'Draft',
        ]);

        $this->actingAs($employee)
            ->postJson(
                "/api/service-requests/{$serviceRequest->id}/submit"
            )
            ->assertStatus(200);

        Notification::assertSentTo(
            $manager,
            ServiceRequestNotification::class,
            function ($notification) use ($serviceRequest) {
                return $notification->requestId === $serviceRequest->id
                    && $notification->event === 'submitted';
            }
        );
    }

    public function test_manager_decision_notifies_employee(): void
    {
        Notification::fake();

        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $serviceRequest = ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => 'Submitted',
        ]);

        $this->actingAs($manager)
            ->postJson(
                "/api/service-requests/{$serviceRequest->id}/decision",
                [
                    'decision' => 'Approved',
                    'comment' => 'Approved.',
                ]
            )
            ->assertStatus(200);

        Notification::assertSentTo(
            $employee,
            ServiceRequestNotification::class,
            function ($notification) use ($serviceRequest) {
                return $notification->requestId === $serviceRequest->id
                    && $notification->event === 'approved';
            }
        );
    }

    public function test_assignment_notifies_assigned_operations_user(): void
    {
        Notification::fake();

        $operations = User::factory()->create([
            'role' => 'operations',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
        ]);

        $serviceRequest = ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not working.',
            'status' => 'Approved',
        ]);

        $this->actingAs($operations)
            ->postJson(
                "/api/service-requests/{$serviceRequest->id}/assign",
                [
                    'assigned_to' => $operations->id,
                ]
            )
            ->assertStatus(200);

        Notification::assertSentTo(
            $operations,
            ServiceRequestNotification::class,
            function ($notification) use ($serviceRequest) {
                return $notification->requestId === $serviceRequest->id
                    && $notification->event === 'assigned';
            }
        );
    }
}
