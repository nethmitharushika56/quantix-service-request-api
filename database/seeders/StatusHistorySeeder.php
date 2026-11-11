<?php

namespace Database\Seeders;

use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use Illuminate\Database\Seeder;

class StatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        $manager = \App\Models\User::where('role', 'manager')
            ->firstOrFail();

        $operations = \App\Models\User::where('role', 'operations')
            ->firstOrFail();

        $requests = ServiceRequest::all();

        foreach ($requests as $request) {
            $employee = $request->employee;

            $history = [
                [
                    'actor_id' => $employee->id,
                    'old_status' => null,
                    'new_status' => 'Draft',
                    'comment' => 'Request created.',
                ],
            ];

            if ($request->status !== 'Draft') {
                $history[] = [
                    'actor_id' => $employee->id,
                    'old_status' => 'Draft',
                    'new_status' => 'Submitted',
                    'comment' => 'Request submitted for approval.',
                ];
            }

            if (in_array($request->status, [
                'Returned', 'Approved', 'Rejected',
                'In Progress', 'Completed',
            ])) {
                $history[] = [
                    'actor_id' => $manager->id,
                    'old_status' => 'Submitted',
                    'new_status' => $request->status === 'Returned'
                        ? 'Returned'
                        : ($request->status === 'Rejected'
                            ? 'Rejected'
                            : 'Approved'),
                    'comment' => 'Manager reviewed the request.',
                ];
            }

            if (in_array($request->status, [
                'In Progress', 'Completed',
            ])) {
                $history[] = [
                    'actor_id' => $operations->id,
                    'old_status' => 'Approved',
                    'new_status' => 'In Progress',
                    'comment' => 'Operations started processing.',
                ];
            }

            if ($request->status === 'Completed') {
                $history[] = [
                    'actor_id' => $operations->id,
                    'old_status' => 'In Progress',
                    'new_status' => 'Completed',
                    'comment' => 'Request successfully completed.',
                ];
            }

            foreach ($history as $index => $entry) {
                StatusHistory::create([
                    'service_request_id' => $request->id,
                    ...$entry,
                    'created_at' => now()->subDays(
                        count($history) - $index
                    ),
                ]);
            }
        }
    }
}
