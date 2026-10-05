<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LocalAdminPasswordController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureLocalRequest($request);
        $admin = User::where('role', 'admin')->orderBy('id')->firstOrFail();
        $nonce = Str::random(64);

        $request->session()->put([
            'local_admin_password_nonce' => $nonce,
            'local_admin_password_expires' => now()->addMinutes(10)->timestamp,
        ]);

        return view('auth.local-admin-password', compact('admin', 'nonce'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureLocalRequest($request);

        $nonce = $request->session()->get('local_admin_password_nonce');
        abort_unless(
            is_string($nonce)
                && hash_equals($nonce, (string) $request->input('nonce'))
                && $request->session()->get('local_admin_password_expires', 0) >= now()->timestamp,
            419,
        );

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $admin = User::where('role', 'admin')->orderBy('id')->firstOrFail();
        $admin->forceFill(['password' => Hash::driver('bcrypt')->make($validated['password'])])->save();
        $request->session()->forget(['local_admin_password_nonce', 'local_admin_password_expires']);

        return redirect()->route('login')->with('status', 'Đã đặt lại mật khẩu quản trị. Hãy đăng nhập bằng mật khẩu mới.');
    }

    private function ensureLocalRequest(Request $request): void
    {
        abort_unless(
            app()->environment('local', 'testing') && in_array($request->ip(), ['127.0.0.1', '::1'], true),
            404,
        );
    }
}