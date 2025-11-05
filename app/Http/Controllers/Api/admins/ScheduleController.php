<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;                 
use Carbon\CarbonPeriod;           

class ScheduleController extends Controller
{

    public function index()
    {
        return Schedule::with([
            'room',
            'course.subject', 
            'course.studentClass', 
            'course.lecturer',
            'course.studyPeriod.semester.academicYear' 
        ])
        ->orderBy('id', 'desc')
        ->paginate(5);
    }

    /**
     * Lưu thời khóa biểu MỚI và TỰ ĐỘNG TẠO BUỔI HỌC.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'room_id' => 'required|exists:rooms,id',
            'day_of_week' => 'required|integer|between:1,7', // 1 = Thứ 2, 7 = Chủ Nhật
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule = null;
        DB::transaction(function () use ($validatedData, &$schedule) {
            // 1. Tạo thời khóa biểu
            $schedule = Schedule::create($validatedData);

            // 2. Tự động tạo các buổi học
            $this->generateAttendanceSessions($schedule);
        });

        return response()->json($schedule, Response::HTTP_CREATED);
    }

    /**
     * Hiển thị chi tiết (không cần thiết lắm nhưng để cho đủ)
     */
    public function show(Schedule $schedule)
    {
        return $schedule->load(['room', 'course.subject', 'course.studentClass', 'course.lecturer', 'course.studyPeriod']);
    }

    /**
     * Cập nhật thời khóa biểu và TÁI TẠO các buổi học TƯƠNG LAI.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validatedData = $request->validate([
            'course_id' => 'sometimes|required|exists:courses,id',
            'room_id' => 'sometimes|required|exists:rooms,id',
            'day_of_week' => 'sometimes|required|integer|between:1,7',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
        ]);
        
        DB::transaction(function () use ($validatedData, $schedule) {
            // 1. Xóa tất cả các buổi học TRONG TƯƠNG LAI
            // (Giữ lại các buổi đã diễn ra để bảo toàn lịch sử điểm danh)
            AttendanceSession::where('schedule_id', $schedule->id)
                ->where('session_date', '>=', Carbon::today())
                ->delete();

            // 2. Cập nhật thời khóa biểu
            $schedule->update($validatedData);

            // 3. Tái tạo lại các buổi học
            $this->generateAttendanceSessions($schedule);
        });

        return response()->json($schedule);
    }

    /**
     * Xóa thời khóa biểu.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->noContent();
    }

    /**
     * Hàm helper để tạo các buổi điểm danh.
     * (ĐÃ CẬP NHẬT)
     */
    private function generateAttendanceSessions(Schedule $schedule)
    {
        // 1. Lấy thông tin Lớp học phần (Course) và Đợt học (StudyPeriod)
        $course = Course::with('studyPeriod')->find($schedule->course_id);

        // 2. Kiểm tra xem đợt học có ngày bắt đầu và kết thúc không
        if (!$course || !$course->studyPeriod || !$course->studyPeriod->start_date || !$course->studyPeriod->end_date) {
            return;
        }

        // 3. Xác định khoảng thời gian
        $today = Carbon::today();
        $startDate = Carbon::parse($course->studyPeriod->start_date);
        $endDate = Carbon::parse($course->studyPeriod->end_date);
        
        $loopStartDate = $startDate->isAfter($today) ? $startDate : $today;
        
        if($endDate->isBefore($loopStartDate)) {
            return;
        }

        $period = CarbonPeriod::create($loopStartDate, $endDate);
        
        $sessionsToInsert = [];
        $dayOfWeekToMatch = (int) $schedule->day_of_week; 

        // 4. Lặp qua từng ngày trong đợt học
        foreach ($period as $date) {
            if ($date->dayOfWeekIso == $dayOfWeekToMatch) {
                // --- ĐÃ CẬP NHẬT ---
                // Thêm các giá trị mặc định cho các cột mới
                $sessionsToInsert[] = [
                    'schedule_id' => $schedule->id,
                    'session_date' => $date,
                    'attendance_mode' => 'manual', // Mặc định
                    'status' => 'pending',         // Mặc định
                    'created_at' => now(),
                    'updated_at' => now()
                    // Các trường null khác sẽ tự động là null
                ];
                // --------------------
            }
        }

        // 5. Insert hàng loạt vào DB
        if (!empty($sessionsToInsert)) {
            AttendanceSession::insert($sessionsToInsert);
        }
    }
}