<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:create-admin {email} {name}', function (): int {
    $email = (string) $this->argument('email');
    $name = (string) $this->argument('name');

    $validator = Validator::make(
        compact('email', 'name'),
        [
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
        ],
    );

    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return Command::FAILURE;
    }

    $password = $this->secret('Senha (mínimo de 12 caracteres)');
    $confirmation = $this->secret('Confirme a senha');

    if (! is_string($password) || strlen($password) < 12 || $password !== $confirmation) {
        $this->error('A senha deve ter ao menos 12 caracteres e as confirmações devem coincidir.');

        return Command::FAILURE;
    }

    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => User::ROLE_ADMIN,
    ]);

    $this->info("Administrador {$email} criado.");

    return Command::SUCCESS;
})->purpose('Create the initial administrator account');
