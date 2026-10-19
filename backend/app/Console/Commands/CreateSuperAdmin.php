<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    protected $signature = 'nexio:admin:create
        {email : Super Admin email address}
        {--name= : User display name}
        {--password= : Password; if omitted, it is requested securely}';

    protected $description = 'Create or promote a Nexio platform Super Admin';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email noto‘g‘ri.');
            return self::INVALID;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password'));
        if (strlen($password) < 8) {
            $this->error('Password kamida 8 ta belgidan iborat bo‘lishi kerak.');
            return self::INVALID;
        }

        $user = User::where('email', $email)->first();
        $created = !$user;
        if (!$user) {
            $user = new User();
            $user->id = 'usr_' . bin2hex(random_bytes(8));
            $user->email = $email;
        }

        $user->name = (string) ($this->option('name') ?: ($user->name ?? 'Nexio Super Admin'));
        $user->password = $password;
        $user->is_platform_admin = 1;
        $user->platform_role = 'platform_superadmin';
        $user->avatar_color = $user->avatar_color ?: '#2563EB';
        $user->save();

        $this->info(($created ? 'Yangi Super Admin yaratildi: ' : 'User Super Admin qilindi: ') . $email);
        return self::SUCCESS;
    }
}
