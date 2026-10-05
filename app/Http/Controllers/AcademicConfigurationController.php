<?php

namespace App\Http\Controllers;

use App\Models\AcademicOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicConfigurationController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        return view('work.configuration.index', [
            'academicRanks' => AcademicOption::where('type', 'rank')->withCount('rankLecturers')->orderBy('name')->get(),
            'academicDegrees' => AcademicOption::where('type', 'degree')->withCount('degreeLecturers')->orderBy('name')->get(),
        ]);
    }

    public function storeAcademicOption(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'type' => ['required', Rule::in(['rank', 'degree'])],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('academic_options', 'name')->where('type', $request->input('type')),
            ],
        ]);

        AcademicOption::create([
            'type' => $validated['type'],
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        return back()->with('status', $validated['type'] === 'rank' ? 'Đã thêm học hàm.' : 'Đã thêm học vị.');
    }

    public function toggleAcademicOption(AcademicOption $academicOption): RedirectResponse
    {
        $this->authorizeAdmin();

        $hasReferences = $academicOption->type === 'rank'
            ? $academicOption->rankLecturers()->exists()
            : $academicOption->degreeLecturers()->exists();

        if ($academicOption->is_active && $hasReferences) {
            return back()->withErrors(['academic_option' => 'Không thể ngừng lựa chọn đang được hồ sơ giảng viên tham chiếu.']);
        }

        $academicOption->update(['is_active' => ! $academicOption->is_active]);

        return back()->with('status', 'Đã cập nhật trạng thái lựa chọn.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }
}