<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Worker;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['name' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'roles' => 'Admin',
            ]
        );

        User::firstOrCreate(
            ['name' => 'Admin'],
            [
                'password' => Hash::make('123456'),
                'roles' => 'Admin',
            ]
        );

        if (Worker::count() === 0) {
            Worker::create(['name' => 'Mecánico General 1', 'totalDia' => 0]);
            Worker::create(['name' => 'Mecánico General 2', 'totalDia' => 0]);
            Worker::create(['name' => 'Técnico Eléctrico', 'totalDia' => 0]);
        }

        if (Category::count() === 0) {
            Category::create(['name' => 'Repuestos']);
            Category::create(['name' => 'Lubricantes']);
            Category::create(['name' => 'Eléctricos']);
        }
    }
}
