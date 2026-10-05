<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AcademicConfigurationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\CourseSectionController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentCorrectionReviewController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentReportController;
use App\Http\Controllers\AttendanceAlertController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LocalAdminPasswordController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/local-admin-password', [LocalAdminPasswordController::class, 'create'])->middleware('throttle:5,1')->name('admin.password.edit');
    Route::post('/local-admin-password', [LocalAdminPasswordController::class, 'update'])->middleware('throttle:5,1')->name('admin.password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/student/attendance', [StudentPortalController::class, 'index'])->name('student.attendance.index');
    Route::post('/student/attendance-records/{attendanceRecord}/corrections', [StudentPortalController::class, 'requestCorrection'])->name('student.attendance.corrections.store');
    Route::get('/work/correction-requests', [StudentCorrectionReviewController::class, 'index'])->name('correction-requests.index');
    Route::post('/work/correction-requests/{correctionRequest}/review', [StudentCorrectionReviewController::class, 'review'])->name('correction-requests.review');
    Route::get('/work/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/work/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/work/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/work/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('/work/lecturers', [LecturerController::class, 'index'])->name('lecturers.index');
    Route::get('/work/lecturers/create', [LecturerController::class, 'create'])->name('lecturers.create');
    Route::post('/work/lecturers', [LecturerController::class, 'store'])->name('lecturers.store');
    Route::get('/work/lecturers/export', [LecturerController::class, 'export'])->name('lecturers.export');
    Route::get('/work/lecturers/{lecturer}/documents/{document}', [LecturerController::class, 'downloadDocument'])->name('lecturers.documents.download');
    Route::get('/work/lecturers/{lecturer}', [LecturerController::class, 'show'])->name('lecturers.show');
    Route::get('/work/lecturers/{lecturer}/edit', [LecturerController::class, 'edit'])->name('lecturers.edit');
    Route::put('/work/lecturers/{lecturer}', [LecturerController::class, 'update'])->name('lecturers.update');
    Route::post('/work/lecturers/{lecturer}/review', [LecturerController::class, 'review'])->name('lecturers.review');
    Route::delete('/work/lecturers/{lecturer}', [LecturerController::class, 'destroy'])->name('lecturers.destroy');

    Route::get('/work/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/work/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/work/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/work/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/work/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::patch('/work/departments/{department}', [DepartmentController::class, 'toggle'])->name('departments.toggle');

    Route::get('/work/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/work/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/work/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/work/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::patch('/work/courses/{course}', [CourseController::class, 'toggle'])->name('courses.toggle');
    Route::delete('/work/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::get('/work/classes', [CourseSectionController::class, 'index'])->name('classes.index');
    Route::post('/work/classes', [CourseSectionController::class, 'store'])->name('classes.store');
    Route::get('/work/classes/{courseSection}/sessions', [AttendanceSessionController::class, 'index'])->name('classes.sessions.index');
    Route::post('/work/classes/{courseSection}/sessions', [AttendanceSessionController::class, 'store'])->name('classes.sessions.store');
    Route::get('/work/classes/{courseSection}/assessments', [AssessmentController::class, 'index'])->name('classes.assessments.index');
    Route::post('/work/classes/{courseSection}/assessments', [AssessmentController::class, 'store'])->name('classes.assessments.store');
    Route::get('/work/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::patch('/work/assessments/{assessment}/results', [AssessmentController::class, 'updateResults'])->name('assessments.results.update');
    Route::post('/work/assessment-results/{assessmentResult}/corrections', [AssessmentController::class, 'requestCorrection'])->name('assessment-results.corrections.store');
    Route::post('/work/assessment-correction-requests/{correctionRequest}/review', [StudentCorrectionReviewController::class, 'reviewAssessment'])->name('assessment-correction-requests.review');
    Route::get('/work/assessment-reports', [AssessmentReportController::class, 'index'])->name('assessment-reports.index');
    Route::get('/work/assessment-reports/export', [AssessmentReportController::class, 'export'])->name('assessment-reports.export');
    Route::get('/work/classes/{courseSection}/assessments', [AssessmentController::class, 'index'])->name('classes.assessments.index');
    Route::post('/work/classes/{courseSection}/assessments', [AssessmentController::class, 'store'])->name('classes.assessments.store');
    Route::get('/work/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::patch('/work/assessments/{assessment}/results', [AssessmentController::class, 'updateResults'])->name('assessments.results.update');
    Route::get('/work/attendance-sessions', [AttendanceSessionController::class, 'overview'])->name('attendance-sessions.index');
    Route::get('/work/classes/{courseSection}/learners', [CourseEnrollmentController::class, 'index'])->name('classes.learners.index');
    Route::post('/work/classes/{courseSection}/learners', [CourseEnrollmentController::class, 'store'])->name('classes.learners.store');
    Route::delete('/work/classes/{courseSection}/learners/{learner}', [CourseEnrollmentController::class, 'destroy'])->name('classes.learners.destroy');
    Route::get('/work/attendance-sessions/{attendanceSession}', [AttendanceSessionController::class, 'show'])->name('attendance-sessions.show');
    Route::post('/work/attendance-sessions/{attendanceSession}/mark-all-present', [AttendanceSessionController::class, 'markAllPresent'])->name('attendance-sessions.mark-all-present');
    Route::patch('/work/attendance-sessions/{attendanceSession}/records', [AttendanceSessionController::class, 'updateRecords'])->name('attendance-sessions.records.update');
    Route::post('/work/attendance-sessions/{attendanceSession}/confirm', [AttendanceSessionController::class, 'confirm'])->name('attendance-sessions.confirm');
    Route::get('/work/attendance-alerts', [AttendanceAlertController::class, 'index'])->name('attendance-alerts.index');
    Route::post('/work/attendance-alerts/refresh', [AttendanceAlertController::class, 'refresh'])->name('attendance-alerts.refresh');
    Route::patch('/work/attendance-alerts/{alert}', [AttendanceAlertController::class, 'update'])->name('attendance-alerts.update');
    Route::patch('/work/attendance-alert-settings/{setting}', [AttendanceAlertController::class, 'updateSetting'])->name('attendance-alert-settings.update');
    Route::get('/work/attendance-reports', [AttendanceReportController::class, 'index'])->name('attendance-reports.index');
    Route::get('/work/attendance-reports/export', [AttendanceReportController::class, 'export'])->name('attendance-reports.export');
    Route::get('/work/classes/{courseSection}/edit', [CourseSectionController::class, 'edit'])->name('classes.edit');
    Route::put('/work/classes/{courseSection}', [CourseSectionController::class, 'update'])->name('classes.update');
    Route::patch('/work/classes/{courseSection}', [CourseSectionController::class, 'toggle'])->name('classes.toggle');
    Route::delete('/work/classes/{courseSection}', [CourseSectionController::class, 'destroy'])->name('classes.destroy');

    Route::get('/work/learners/template', [LearnerController::class, 'downloadTemplate'])->name('learners.template');
    Route::get('/work/learners', [LearnerController::class, 'index'])->name('learners.index');
    Route::post('/work/learners/import', [LearnerController::class, 'import'])->name('learners.import');
    Route::post('/work/learners', [LearnerController::class, 'store'])->name('learners.store');
    Route::get('/work/learners/{learner}/edit', [LearnerController::class, 'edit'])->name('learners.edit');
    Route::put('/work/learners/{learner}', [LearnerController::class, 'update'])->name('learners.update');
    Route::delete('/work/learners/{learner}', [LearnerController::class, 'destroy'])->name('learners.destroy');

    Route::get('/work/configuration', [AcademicConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('/work/configuration/academic-options', [AcademicConfigurationController::class, 'storeAcademicOption'])->name('configuration.academic-options.store');
    Route::patch('/work/configuration/academic-options/{academicOption}', [AcademicConfigurationController::class, 'toggleAcademicOption'])->name('configuration.academic-options.toggle');

    Route::get('/work/{module}', function (string $module) {
        $item = collect(config('attendance.groups'))
            ->pluck('items')
            ->flatten(1)
            ->firstWhere('key', $module);

        abort_unless($item, 404);

        return view('work.index', compact('item'));
    })->name('work.show');
});
