<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:ensure-admin', function () {
    $admin = config('deployment.admin');

    if (blank($admin['email']) || blank($admin['password'])) {
        $this->warn('ADMIN_EMAIL e ADMIN_PASSWORD não configurados; administrador inicial não foi alterado.');

        return 0;
    }

    if (mb_strlen($admin['password']) < 12) {
        $this->error('ADMIN_PASSWORD deve possuir pelo menos 12 caracteres.');

        return 1;
    }

    $user = User::withTrashed()->firstOrNew(['email' => $admin['email']]);
    $user->fill([
        'name' => $admin['name'],
        'password' => $admin['password'],
        'role' => UserRole::Admin,
        'active' => true,
    ]);
    $user->email_verified_at = now();
    $user->save();

    if ($user->trashed()) {
        $user->restore();
    }

    $this->info('Administrador inicial garantido com sucesso.');

    return 0;
})->purpose('Cria ou atualiza o administrador inicial usando variáveis de ambiente');
