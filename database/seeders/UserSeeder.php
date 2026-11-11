<?php

namespace Database\Seeders;;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a manager
        $manager = User::updateOrCreate(
    ['email' => 'sarah@quantix.test'],
    [
        'name' => 'Sarah Manager',
        'password' => 'Password123!',
        'role' => 'manager',
        'manager_id' => null,
    ]
);

        // Create two employees reporting to Sarah
        User::create([
            'name' => 'John Employee',
            'email' => 'john@quantix.test',
            'password' => 'Password123!',
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        User::create([
            'name' => 'Emma Employee',
            'email' => 'emma@quantix.test',
            'password' => 'Password123!',
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        // Create an operations user
        User::create([
            'name' => 'Oliver Operations',
            'email' => 'oliver@quantix.test',
            'password' => 'Password123!',
            'role' => 'operations',
            'manager_id' => null,
        ]);
    }
}
