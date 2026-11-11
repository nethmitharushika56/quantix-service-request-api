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

        $returnedRequest = ServiceRequest::where('status', 'Returned')
            ->first();

        $approvedRequest = ServiceRequest::where('status', 'Approved')
            ->first();

        $inProgressRequest = ServiceRequest::where('status', 'In Progress')
            ->first();

        if ($returnedRequest) {
            Comment::create([
                'service_request_id' => $returnedRequest->id,
                'user_id' => $manager->id,
                'comment' => 'Please provide additional information.',
            ]);
        }

        if ($approvedRequest) {
            Comment::create([
                'service_request_id' => $approvedRequest->id,
                'user_id' => $manager->id,
                'comment' => 'Approved. Please proceed.',
            ]);
        }

        if ($inProgressRequest) {
            Comment::create([
                'service_request_id' => $inProgressRequest->id,
                'user_id' => $operations->id,
                'comment' => 'Work has started.',
            ]);
        }
    }
}
