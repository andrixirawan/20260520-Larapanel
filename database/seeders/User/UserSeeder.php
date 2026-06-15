<?php

namespace Database\Seeders\User;

use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const PASSWORD = '!Password12345';

    /**
     * @var array<int, array{public_id: string, role: string, name: string, email: string}>
     */
    public const USERS = [
        [
            'public_id' => '01KV5XNXB1BM51JZM7X6STX8CF',
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
        ],
        [
            'public_id' => '01KV5XNXB2D1DYKXRG5DMQ9V1N',
            'role' => AccessControl::ROLE_ADMINISTRATOR,
            'name' => 'Administrator',
            'email' => 'administrator@gmail.com',
        ],
        [
            'public_id' => '01KV5XNXB2D1DYKXRG5DMQ9V1P',
            'role' => AccessControl::ROLE_CASHIER,
            'name' => 'Cashier',
            'email' => 'cashier@gmail.com',
        ],
        [
            'public_id' => '01KV5XNXB2D1DYKXRG5DMQ9V1Q',
            'role' => AccessControl::ROLE_SUBSCRIBER,
            'name' => 'Subscriber',
            'email' => 'subscriber@gmail.com',
        ],
    ];

    public function run(): void
    {
        foreach (self::USERS as $seededUser) {
            $user = User::query()->firstOrNew([
                'email' => $seededUser['email'],
            ]);

            $user->forceFill([
                'public_id' => $seededUser['public_id'],
                'name' => $seededUser['name'],
                'email_verified_at' => now(),
                'password' => Hash::make(self::PASSWORD),
            ])->save();

            $user->syncRoles([$seededUser['role']]);
        }
    }
}
