<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultAdminEmail = env('DEFAULT_ADMIN_EMAIL', User::DEFAULT_ADMIN_EMAIL);
        $defaultAdminDocument = preg_replace('/\D/', '', (string) env('DEFAULT_ADMIN_DOCUMENT', User::DEFAULT_ADMIN_DOCUMENT));
        $defaultAdminPassword = env('DEFAULT_ADMIN_PASSWORD');

        if (app()->environment('production') && blank($defaultAdminPassword)) {
            throw new RuntimeException('A variável DEFAULT_ADMIN_PASSWORD é obrigatória em produção para criar o usuário padrão.');
        }

        if (blank($defaultAdminPassword)) {
            $defaultAdminPassword = '513514';
        }

        User::updateOrCreate(
            ['email' => $defaultAdminEmail],
            [
                'name' => 'Elizeu',
                'cpf' => $defaultAdminDocument,
                'password' => Hash::make($defaultAdminPassword),
                'email_verified_at' => now(),
            ]
        );
    }
}
