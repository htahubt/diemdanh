<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetSensitiveProfileAccess extends Command
{
    protected $signature = 'app:sensitive-profile-access {email} {action : grant or revoke}';

    protected $description = 'Grant or revoke access to sensitive lecturer profile data';

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['grant', 'revoke'], true)) {
            $this->error('Action phải là grant hoặc revoke.');

            return self::FAILURE;
        }

        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();

        if (! $user) {
            $this->error('Không tìm thấy tài khoản.');

            return self::FAILURE;
        }

        if ($user->role === 'admin' && $action === 'revoke') {
            $this->error('Hãy chuyển tài khoản khỏi role admin trước khi thu hồi quyền.');

            return self::FAILURE;
        }

        $user->forceFill(['can_view_sensitive_profiles' => $action === 'grant'])->save();
        $this->info("Đã {$action} quyền xem hồ sơ nhạy cảm cho {$user->email}.");

        return self::SUCCESS;
    }
}