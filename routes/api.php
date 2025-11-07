<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\admins\IntakeController;
use App\Http\Controllers\Api\admins\AcademicYearController;
use App\Http\Controllers\Api\admins\FacilityController;
use App\Http\Controllers\Api\admins\RoomController;
use App\Http\Controllers\Api\admins\AuthController;
use App\Http\Controllers\Api\admins\StudentClassController;
use App\Http\Controllers\Api\admins\FacultyController;
use App\Http\Controllers\Api\admins\StudentController;
use App\Http\Controllers\Api\admins\SemesterController;
use App\Http\Controllers\Api\admins\DepartmentController;
use App\Http\Controllers\Api\admins\LecturerController;
use App\Http\Controllers\Api\admins\StudyPeriodController;
use App\Http\Controllers\Api\admins\SubjectController;
use App\Http\Controllers\Api\admins\MajorController;
use App\Http\Controllers\Api\admins\CourseController;
use App\Http\Controllers\Api\admins\ScheduleController;

// giảng viên
use App\Http\Controllers\Api\lecturers\LecturerAuthController;
use App\Http\Controllers\Api\lecturers\LecturerScheduleController;
use App\Http\Controllers\Api\lecturers\LecturerCourseController;
use App\Http\Controllers\Api\lecturers\LecturerSessionController;

// Sinh viên
use App\Http\Controllers\Api\students\StudentAuthController;
use App\Http\Controllers\Api\students\StudentScheduleController;
use App\Http\Controllers\Api\students\StudentCourseController;
use App\Http\Controllers\Api\students\StudentProfileController;


// admin
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::apiResource('intakes', IntakeController::class);
Route::apiResource('academic-years', AcademicYearController::class);
Route::apiResource('facilities', FacilityController::class);
Route::apiResource('rooms', RoomController::class);
Route::apiResource('student-classes', StudentClassController::class);
Route::apiResource('faculties', FacultyController::class);
Route::apiResource('students', StudentController::class);
Route::apiResource('semesters', SemesterController::class);
Route::apiResource('departments', DepartmentController::class);
Route::apiResource('lecturers', LecturerController::class);
Route::apiResource('study-periods', StudyPeriodController::class);
Route::apiResource('subjects', SubjectController::class);
Route::apiResource('majors', MajorController::class);
Route::apiResource('courses', CourseController::class);
Route::apiResource('schedules', ScheduleController::class);

// Giảng viên
Route::prefix('lecturer')->group(function () {
    Route::post('/login', [LecturerAuthController::class, 'login']);

    // Các route cần xác thực
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LecturerAuthController::class, 'logout']);

        //ROUTE MỚI ĐỂ LẤY LỊCH HỌC HÔM NAY 
        Route::get('/today-schedule', [LecturerScheduleController::class, 'getTodaySchedule']);
        // danh sách lớp học phần của giảng viên
        Route::get('/courses-by-period', [LecturerCourseController::class, 'getCoursesByPeriod']);
        // LẤY CHI TIẾT CÁC BUỔI HỌC CỦA LỚP HỌC PHẦN
        Route::get('/course/{course}/sessions', [LecturerCourseController::class, 'getCourseSessions']);
        // ===== CHI TIẾT BUỔI HỌC=====
        Route::get('/session/{session}', [LecturerSessionController::class, 'getSessionDetails']);
        // KHI GIẢNG VIÊN BẤM NÚT BẮT ĐẦU ĐIỂM DANH
        Route::post('/session/{session}/start', [LecturerSessionController::class, 'startSession']);
        // GIẢNG VIÊN BẤM NÚT KẾT THÚC ĐIỂM DANH
        Route::post('/session/{session}/end', [LecturerSessionController::class, 'endSession']);
        // DANH SÁCH ĐIỂM DANH CÁC SV CỦA 1 BUỔI
        Route::get('/session/{session}/records', [LecturerSessionController::class, 'getSessionRecords']);
        // GV CẬP NHẬT TRẠNG THÁI ĐIỂM DANH CHO SV
        Route::post('/record/{record}/update-status', [LecturerSessionController::class, 'updateRecordStatus']);
    });
});

// sinh viên
Route::prefix('student')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login']);

    // Thêm route logout (nên có)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [StudentAuthController::class, 'logout']);
        // Lấy lịch học hôm nay của sinh viên
        Route::get('/today-schedule', [StudentScheduleController::class, 'getTodaySchedule']);
        // DANH SÁCH LỚP HỌC PHẦN 
        Route::get('/my-courses', [StudentCourseController::class, 'getMyCoursesByPeriod']);
        // CÁC BUỔI HỌC CỦA 1 LỚP HỌC PHẦN
        Route::get('/course/{course}/sessions', [StudentCourseController::class, 'getCourseSessions']);
        // thông tin các nhân
        Route::get('/profile', [StudentProfileController::class, 'getProfile']);
        // upload ảnh
        Route::post('/upload-face-image', [StudentProfileController::class, 'uploadFaceImage']);
        // SV điểm danh
        Route::post('/session/{session}/attend', [StudentScheduleController::class, 'submitAttendance']);

        // ĐIỂM DANH BẰNG KHUÔN MẶT 
        Route::post('/session/{session}/attend-face', [StudentScheduleController::class, 'submitFaceAttendance']);
    });
});
