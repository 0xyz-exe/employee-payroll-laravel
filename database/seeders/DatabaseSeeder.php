<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['Operations', 'Finance'] as $name) {
            $department = Department::firstOrCreate(['name' => $name]);
            $department->positions()->firstOrCreate(['title' => 'Staff']);
            $department->positions()->firstOrCreate(['title' => 'Supervisor']);
        }
    }
}
