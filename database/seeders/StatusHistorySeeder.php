<?php

namespace Database\Seeders;

use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class StatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        $operations = User::where('role', 'operations')->firstOrFail();

        foreach (ServiceRequest::with('employee.manager')->get() as $request) {
            $employee = $request->employee;
            $manager = $employee->manager;

            $transitions = [
                [
                    'old' => null,
                    'new' => 'Draft',
                    'actor' => $employee->id,
                ],
            ];

            if ($request->status !== 'Draft') {
                $transitions[] = [
                    'old' => 'Draft',
                    'new' => 'Submitted',
                    'actor' => $employee->id,
                ];
            }

            if (in_array($request->status, [
                'Returned', 'Approved', 'Rejected', 'In Progress', 'Completed'
            ])) {
                $managerStatus = in_array($request->status, [
                    'Returned', 'Rejected'
                ]) ? $request->status : 'Approved';

                $transitions[] = [
                    'old' => 'Submitted',
                    'new' => $managerStatus,
                    'actor' => $manager->id,
                ];
            }

            if (in_array($request->status, ['In Progress', 'Completed'])) {
                $transitions[] = [
                    'old' => 'Approved',
                    'new' => 'In Progress',
                    'actor' => $operations->id,
                ];
            }

            if ($request->status === 'Completed') {
                $transitions[] = [
                    'old' => 'In Progress',
                    'new' => 'Completed',
                    'actor' => $operations->id,
                ];
            }

            foreach ($transitions as $index => $transition) {
                StatusHistory::firstOrCreate(
                    [
                        'service_request_id' => $request->id,
                        'actor_id' => $transition['actor'],
                        'old_status' => $transition['old'],
                        'new_status' => $transition['new'],
                    ],
                    [
                        'comment' => 'Sample status history',
                        'created_at' => now()->subDays(count($transitions) - $index),
                    ]
                );
            }
        }
    }
}