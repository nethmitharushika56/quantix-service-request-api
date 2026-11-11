<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update the manager
        $manager = User::updateOrCreate(
            ['email' => 'sarah@quantix.test'],
            [
                'name' => 'Sarah Manager',
                'password' => 'Password123!',
                'role' => 'manager',
                'manager_id' => null,
            ]
        );

        // Create or update employees reporting to the manager
        User::updateOrCreate(
            ['email' => 'john@quantix.test'],
            [
                'name' => 'John Employee',
                'password' => 'Password123!',
                'role' => 'employee',
                'manager_id' => $manager->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'emma@quantix.test'],
            [
                'name' => 'Emma Employee',
                'password' => 'Password123!',
                'role' => 'employee',
                'manager_id' => $manager->id,
            ]
        );

        // Create or update the operations user
        User::updateOrCreate(
            ['email' => 'oliver@quantix.test'],
            [
                'name' => 'Oliver Operations',
                'password' => 'Password123!',
                'role' => 'operations',
                'manager_id' => null,
            ]
        );
    }
}
