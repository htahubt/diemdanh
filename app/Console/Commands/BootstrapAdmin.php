<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapAdmin extends Command
{
    protected $signature = 'app:bootstrap-admin {email} {--name=} {--reset-password}';

    protected $description = 'Create or promote a user as the initial application administrator';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->error('Email không hợp lệ.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user || $this->option('reset-password')) {
            $password = $this->secret('Mật khẩu mới (tối thiểu 12 ký tự)');
            $confirmation = $this->secret('Nhập lại mật khẩu');

            if (! is_string($password) || strlen($password) < 12 || $password !== $confirmation) {
                $this->error('Mật khẩu không khớp hoặc ngắn hơn 12 ký tự.');

                return self::FAILURE;
            }

            $passwordHash = Hash::driver('bcrypt')->make($password);

            if ($user) {
                $user->password = $passwordHash;
                $user->save();
            } else {
                $name = $this->option('name') ?: $this->ask('Họ tên admin');
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $passwordHash,
                ]);
            }
        }

        $user->forceFill([
            'role' => 'admin',
            'can_view_sensitive_profiles' => true,
        ])->save();

        $this->info("Đã cấp quyền admin cho {$email}.");

        return self::SUCCESS;
    }
}