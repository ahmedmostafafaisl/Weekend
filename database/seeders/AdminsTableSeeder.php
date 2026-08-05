<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminsTableSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'المدير العام',
                'email' => 'superadmin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(10),
                'role' => 'super_admin',
            ],
            [
                'name' => 'المدير الأول',
                'email' => 'admin1@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(10),
                'role' => 'admin',
            ],
            [
                'name' => 'المدير الثاني',
                'email' => 'admin2@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(10),
                'role' => 'admin',
            ],
        ];

        foreach ($admins as $item) {
            $admin = Admin::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'email_verified_at' => $item['email_verified_at'],
                    'password' => $item['password'],
                    'remember_token' => $item['remember_token'],
                ]
            );

            if (! empty($item['role'])) {
                $admin->syncRoles([$item['role']]);
            }
        }
    }
}
