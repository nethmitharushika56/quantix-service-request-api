<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceRequest;
use Illuminate\Database\Seeder;

class ServiceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $john = User::where('email', 'john@quantix.test')->firstOrFail();
        $emma = User::where('email', 'emma@quantix.test')->firstOrFail();
        $operations = User::where('email', 'oliver@quantix.test')->firstOrFail();

        $requests = [
            [
                'employee_id' => $john->id,
                'title' => 'Laptop Repair',
                'description' => 'Laptop keyboard is not functioning.',
                'status' => 'Draft',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $john->id,
                'title' => 'Software Installation',
                'description' => 'Requesting installation of approved software.',
                'status' => 'Submitted',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $emma->id,
                'title' => 'Network Access',
                'description' => 'Requesting access to the internal network.',
                'status' => 'Returned',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $emma->id,
                'title' => 'Monitor Replacement',
                'description' => 'Existing monitor has display issues.',
                'status' => 'Approved',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $john->id,
                'title' => 'Email Configuration',
                'description' => 'Requesting corporate email configuration.',
                'status' => 'Rejected',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $emma->id,
                'title' => 'Printer Access',
                'description' => 'Requesting access to the office printer.',
                'status' => 'In Progress',
                'assigned_to' => $operations->id,
            ],
            [
                'employee_id' => $john->id,
                'title' => 'VPN Access',
                'description' => 'Requesting remote VPN access.',
                'status' => 'Completed',
                'assigned_to' => $operations->id,
            ],
            [
                'employee_id' => $emma->id,
                'title' => 'Keyboard Replacement',
                'description' => 'Requesting a replacement keyboard.',
                'status' => 'Submitted',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $john->id,
                'title' => 'Development Environment',
                'description' => 'Requesting development environment setup.',
                'status' => 'Approved',
                'assigned_to' => null,
            ],
            [
                'employee_id' => $emma->id,
                'title' => 'Office Chair Repair',
                'description' => 'Office chair needs maintenance.',
                'status' => 'Draft',
                'assigned_to' => null,
            ],
        ];

        foreach ($requests as $request) {
            ServiceRequest::updateOrCreate(
                [
                    'employee_id' => $request['employee_id'],
                    'title' => $request['title'],
                ],
                $request
            );
        }
    }
}
