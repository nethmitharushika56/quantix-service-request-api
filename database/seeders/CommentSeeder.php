<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $operations = User::where('role', 'operations')->firstOrFail();

        $returnedRequest = ServiceRequest::where('status', 'Returned')->first();

        if ($returnedRequest) {
            Comment::firstOrCreate(
                [
                    'service_request_id' => $returnedRequest->id,
                    'user_id' => $manager->id,
                    'comment' => 'Please provide more information.',
                ]
            );
        }

        $approvedRequest = ServiceRequest::where('status', 'Approved')->first();

        if ($approvedRequest) {
            Comment::firstOrCreate(
                [
                    'service_request_id' => $approvedRequest->id,
                    'user_id' => $manager->id,
                    'comment' => 'Your request has been approved.',
                ]
            );
        }

        $progressRequest = ServiceRequest::where('status', 'In Progress')->first();

        if ($progressRequest) {
            Comment::firstOrCreate(
                [
                    'service_request_id' => $progressRequest->id,
                    'user_id' => $operations->id,
                    'comment' => 'Work has started on this request.',
                ]
            );
        }
    }
}