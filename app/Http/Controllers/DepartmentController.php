<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageTraining(), 403);

        return view('work.departments.index', [
            'departments' => Department::withCount(['lecturers', 'courses'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:160', 'unique:departments,name'],
        ]);

        Department::create([
            'code' => Str::upper($validated['code']),
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        return back()->with('status', 'Đã thêm khoa / đơn vị.');
    }

    public function edit(Request $request, Department $department): View
    {
        abort_unless($request->user()->canManageTraining(), 403);

        return view('work.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('departments', 'code')->ignore($department->id)],
            'name' => ['required', 'string', 'max:160', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);
        $newName = trim($validated['name']);
        $oldName = $department->name;

        DB::transaction(function () use ($department, $validated, $newName, $oldName, $request): void {
            $department->update([
                'code' => Str::upper($validated['code']),
                'name' => $newName,
            ]);

            if ($oldName !== $newName) {
                $department->lecturers()->lockForUpdate()->get()->each(function ($lecturer) use ($oldName, $newName, $request): void {
                    $lecturer->update(['department' => $newName]);
                    $lecturer->history()->create([
                        'actor_id' => $request->user()->id,
                        'action' => 'department_renamed',
                        'changes' => ['department' => ['from' => $oldName, 'to' => $newName]],
                        'note' => 'Tên đơn vị được cập nhật từ danh mục dùng chung.',
                    ]);
                });
            }
        });

        return redirect()->route('departments.index')->with('status', 'Đã cập nhật khoa / đơn vị.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);

        if ($department->lecturers()->exists() || $department->courses()->exists() || $department->learners()->exists()) {
            abort(409, 'Không thể xóa khoa / đơn vị đang được hồ sơ đào tạo tham chiếu.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('status', 'Đã xóa khoa / đơn vị.');
    }

    public function toggle(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->canManageTraining(), 403);

        if ($department->is_active && ($department->lecturers()->exists() || $department->courses()->exists() || $department->learners()->exists())) {
            return back()->withErrors(['department' => 'Không thể ngừng khoa / đơn vị đang được hồ sơ giảng viên tham chiếu.']);
        }

        $department->update(['is_active' => ! $department->is_active]);

        return back()->with('status', 'Đã cập nhật trạng thái khoa / đơn vị.');
    }
}
