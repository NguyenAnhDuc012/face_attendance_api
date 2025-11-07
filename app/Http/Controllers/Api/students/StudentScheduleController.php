<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class StudentScheduleController extends Controller
{
    public function getTodaySchedule(Request $request)
    {
        $student = $request->user();
        $today = Carbon::today('Asia/Ho_Chi_Minh');

        // 1. Lấy các buổi học (session) hôm nay của lớp sinh viên
        $sessions = AttendanceSession::whereHas('schedule.course', function ($query) use ($student) {
            $query->where('class_id', $student->class_id);
        })
            ->whereDate('session_date', $today)
            ->with([
                'schedule.course.subject:id,name',
                'schedule.course.lecturer:id,full_name', // Lấy tên giảng viên
                'schedule.room:id,name',
                // Tải *chỉ* bản ghi điểm danh CỦA SINH VIÊN NÀY
                'attendanceRecords' => function ($query) use ($student) {
                    $query->where('student_id', $student->id);
                }
            ])
            // Sắp xếp theo giờ bắt đầu
            ->join('schedules', 'attendance_sessions.schedule_id', '=', 'schedules.id')
            ->select('attendance_sessions.*')
            ->orderBy('schedules.start_time', 'asc')
            ->get();

        // 2. Định dạng lại dữ liệu
        $formattedSchedules = $sessions->map(function ($session) {

            // Lấy trạng thái của sinh viên (present, absent, late, ...)
            $myRecord = $session->attendanceRecords->first();
            $myStatus = $myRecord ? $myRecord->status : 'chưa có'; // Trạng thái của SV

            return [
                'session_id' => $session->id,
                'course_id' => $session->schedule->course_id,
                'subject_name' => $session->schedule->course->subject->name,
                'lecturer_name' => $session->schedule->course->lecturer->full_name, // Tên GV
                'room_name' => $session->schedule->room->name,
                'start_time' => Carbon::parse($session->schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($session->schedule->end_time)->format('H:i'),
                'session_status' => $session->status, // 'pending', 'active', 'closed'
                'my_attendance_status' => $myStatus, // 'present', 'absent', ...
            ];
        });

        return response()->json(['status' => true, 'data' => $formattedSchedules], 200);
    }

    /**
     * Sinh viên tự nộp trạng thái điểm danh (Thủ công).
     */
    public function submitAttendance(Request $request, AttendanceSession $session)
    {
        $student = $request->user();

        // 1. Validate input
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,late,absent,excused', // Cho phép 4 trạng thái
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Kiểm tra trạng thái buổi học
        if ($session->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Đã hết thời gian điểm danh.'], 403);
        }

        // 3. Kiểm tra chế độ điểm danh (Bảo mật)
        if ($session->attendance_mode !== 'manual') {
            return response()->json(['status' => false, 'message' => 'Buổi học này không điểm danh thủ công.'], 403);
        }

        // 4. Tìm bản ghi của sinh viên này
        $record = $session->attendanceRecords()
            ->where('student_id', $student->id)
            ->first();

        if (!$record) {
            return response()->json(['status' => false, 'message' => 'Bạn không có trong danh sách lớp này.'], 404);
        }

        // 5. Cập nhật bản ghi
        $newStatus = $request->status;
        $record->status = $newStatus;
        $record->source = 'student'; // Nguồn là 'sinh viên'

        // Cập nhật giờ check-in
        if (($newStatus == 'present' || $newStatus == 'late') && $record->check_in_time == null) {
            $record->check_in_time = Carbon::now('Asia/Ho_Chi_Minh');
        } elseif ($newStatus == 'absent' || $newStatus == 'excused') {
            $record->check_in_time = null;
        }

        $record->save();

        return response()->json([
            'status' => true,
            'message' => 'Điểm danh thành công!',
            'data' => $record
        ]);
    }

    /**
     * Sinh viên nộp ảnh "live" và QR token để điểm danh.
     */
    public function submitFaceAttendance(Request $request, AttendanceSession $session)
    {
        $student = $request->user();

        // 1. Validate input (QR token và ảnh live)
        $validator = Validator::make($request->all(), [
            'qr_token' => 'required|string',
            'live_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Kiểm tra trạng thái buổi học
        if ($session->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Đã hết thời gian điểm danh.'], 403);
        }

        // 3. KIỂM TRA QR TOKEN (Chống giả mạo vị trí)
        // (Đây là logic từ Bước 2, LecturerController, khi Giảng viên 'start' session)
        if ($session->qr_token !== $request->qr_token || 
            $session->qr_token_expires_at < Carbon::now()) {
            return response()->json(['status' => false, 'message' => 'QR Code không hợp lệ hoặc đã hết hạn.'], 403);
        }

        // 4. GỌI DỊCH VỤ PYTHON ĐỂ SO SÁNH KHUÔN MẶT
        // (Đây là dịch vụ AI Python bạn đã tạo ở Bước 1)
        
        $liveImagePath = $request->file('live_image')->getPathname();

        // Lấy embedding đã lưu trong DB
        $knownEmbedding = $student->faceEmbeddings()->first();
        if (!$knownEmbedding) {
             return response()->json(['status' => false, 'message' => 'Không tìm thấy dữ liệu khuôn mặt. Vui lòng tải ảnh profile.'], 404);
        }

        try {
            // Gọi API Python (ví dụ: chạy trên cổng 5000)
            $response = Http::post('http://127.0.0.1:5000/verify-face', [
                'live_image_path' => $liveImagePath, // Hoặc gửi file
                'known_embedding' => $knownEmbedding->embedding_vector, // Gửi vector
            ]);

            if (!$response->successful() || !$response->json('match')) {
                return response()->json(['status' => false, 'message' => 'Khuôn mặt không khớp. Vui lòng thử lại.'], 401);
            }
            
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi dịch vụ AI: ' . $e->getMessage()], 500);
        }

        // 5. NẾU KHỚP: Cập nhật bản ghi điểm danh
        $record = $session->attendanceRecords()->where('student_id', $student->id)->first();
        if ($record) {
            $record->status = 'present'; // (Hoặc 'late' nếu bạn check giờ)
            $record->source = 'system'; // 'system' (nhận diện)
            $record->check_in_time = Carbon::now('Asia/Ho_Chi_Minh');
            $record->save();
        }

        return response()->json(['status' => true, 'message' => 'Điểm danh thành công!']);
    }
}
