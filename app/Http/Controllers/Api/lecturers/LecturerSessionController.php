<?php

namespace App\Http\Controllers\Api\lecturers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LecturerSessionController extends Controller
{
    /**
     * Lấy thông tin chi tiết và thống kê của một buổi học (session)
     */
    public function getSessionDetails(Request $request, AttendanceSession $session)
    {
        // 1. Bảo mật: Kiểm tra giảng viên có sở hữu buổi học này không
        $lecturer = $request->user();
        if ($session->schedule->course->lecturer_id !== $lecturer->id) {
            return response()->json(['status' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        // 2. Tải các thông tin liên quan
        $session->load([
            'schedule.course.subject:id,name',
            'schedule.course.studentClass:id,name',
            'schedule.room:id,name',
        ]);

        // 3. Tải danh sách sinh viên của lớp
        $students = $session->schedule->course->studentClass->students;
        $totalStudents = $students->count();

        // 4. Lấy thống kê từ bảng attendance_records
        // (chỉ đếm khi session không còn 'pending')
        $presentCount = 0;
        $absentCount = $totalStudents; // Mặc định vắng hết

        if ($session->status !== 'pending') {
            $presentCount = $session->attendanceRecords()
                ->where('status', 'present')
                ->count();
            $absentCount = $totalStudents - $presentCount;
        }

        // 5. Trả về dữ liệu
        return response()->json([
            'status' => true,
            'data' => [
                'session_id' => $session->id,
                'course_name' => $session->schedule->course->subject->name,
                'class_name' => $session->schedule->course->studentClass->name,
                'room_name' => $session->schedule->room->name,
                'session_date' => Carbon::parse($session->session_date)->format('d/m/Y'),
                'start_time' => Carbon::parse($session->schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($session->schedule->end_time)->format('H:i'),
                'status' => $session->status, // 'pending', 'active', 'closed'
                'total_students' => $totalStudents,
                'present_students' => $presentCount,
                'absent_students' => $absentCount,
            ]
        ], 200);
    }

    /**
     * Bắt đầu một buổi điểm danh
     */
    public function startSession(Request $request, AttendanceSession $session)
    {
        // 1. Bảo mật: (Như trên)
        $lecturer = $request->user();
        if ($session->schedule->course->lecturer_id !== $lecturer->id) {
            return response()->json(['status' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        // 2. Kiểm tra trạng thái
        if ($session->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Buổi học này đã được bắt đầu hoặc đã kết thúc.'], 409);
        }

        // 3. Validate input
        $validator = Validator::make($request->all(), [
            'attendance_mode' => 'required|in:manual,face_recognition_qr', // 'qr' thay cho 'face_recognition_qr'
            'duration_minutes' => 'required|integer|min:1|max:180', // Giới hạn 3 tiếng
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // 4. CẬP NHẬT BẢNG attendance_sessions
        $session->status = 'active';
        $session->attendance_mode = $request->attendance_mode;
        $session->started_at = $now;
        $session->ended_at = $now->copy()->addMinutes($request->duration_minutes);

        // (Tùy chọn: Tạo QR Token nếu là chế độ QR)
        if ($request->attendance_mode == 'face_recognition_qr') {
            $session->qr_token = \Illuminate\Support\Str::random(40);
            $session->qr_token_expires_at = $session->ended_at;
        }

        $session->save();

        // 5. BULK INSERT VÀO BẢNG attendance_records
        $students = $session->schedule->course->studentClass->students()->get(['id']);
        $records = [];
        $timestamp = Carbon::now();

        foreach ($students as $student) {
            $records[] = [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'status' => 'absent', // Mặc định là vắng
                'check_in_time' => null,
                'source' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Dùng DB transaction để đảm bảo an toàn
        DB::transaction(function () use ($records) {
            AttendanceRecord::insert($records);
        });

        // 6. Trả về thông báo thành công
        return response()->json([
            'status' => true,
            'message' => 'Buổi điểm danh đã bắt đầu!',
            'data' => $session // Trả về session đã cập nhật
        ], 200);
    }

    // GV BẤM NÚT KẾT THÚC ĐIỂM DANH
    public function endSession(Request $request, AttendanceSession $session)
    {
        // 1. Bảo mật
        $lecturer = $request->user();
        if ($session->schedule->course->lecturer_id !== $lecturer->id) {
             return response()->json(['status' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        // 2. Kiểm tra trạng thái
        if ($session->status !== 'active') {
            return response()->json([
                'status' => false, 
                'message' => 'Buổi học này đang không diễn ra.'
            ], 409); // 409 Conflict
        }

        // 3. Cập nhật trạng thái
        $session->status = 'closed';
        $session->ended_at = Carbon::now('Asia/Ho_Chi_Minh'); // Ghi đè thời gian kết thúc
        
        // Thu hồi QR token (nếu có)
        $session->qr_token = null;
        $session->qr_token_expires_at = null;
        
        $session->save();

        // 4. Trả về thành công
        return response()->json([
            'status' => true,
            'message' => 'Đã kết thúc buổi điểm danh!',
            'data' => $session // Trả về session đã cập nhật
        ], 200);
    }

    /**
     * Lấy danh sách sinh viên và trạng thái điểm danh của 1 session.
     */
    public function getSessionRecords(Request $request, AttendanceSession $session)
    {
        // 1. Bảo mật
        $lecturer = $request->user();
        if ($session->schedule->course->lecturer_id !== $lecturer->id) {
             return response()->json(['status' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        // 2. Nếu session chưa bắt đầu (pending), trả về mảng rỗng
        if ($session->status == 'pending') {
            return response()->json(['status' => true, 'data' => []], 200);
        }

        // 3. Lấy danh sách records và thông tin student liên quan
        $records = $session->attendanceRecords()
            ->with([
                'student:id,full_name' 
            ])
            // Sắp xếp theo tên sinh viên
            ->join('students', 'attendance_records.student_id', '=', 'students.id')
            ->orderBy('students.full_name', 'asc')
            ->select('attendance_records.*') // Tránh trùng lặp cột 'id'
            ->get();

        // 4. Định dạng lại dữ liệu
        $formattedRecords = $records->map(function ($record) {
            $studentIdentifier = $record->student->id ?? $record->student->email;
            
            return [
                'record_id' => $record->id,
                'student_id' => $record->student->id,
                'student_name' => $record->student->full_name,
                'student_code' => $studentIdentifier,
                'status' => $record->status, // 'present', 'absent', 'late'
                'check_in_time' => $record->check_in_time 
                                    ? Carbon::parse($record->check_in_time)->format('H:i') 
                                    : null,
            ];
        });

        return response()->json(['status' => true, 'data' => $formattedRecords], 200);
    }

    /**
     * Cập nhật thủ công trạng thái của một bản ghi điểm danh (record).
     */
    public function updateRecordStatus(Request $request, AttendanceRecord $record)
    {
        // 1. Bảo mật: Kiểm tra giảng viên này có sở hữu record này không
        $lecturer = $request->user();
        if ($record->session->schedule->course->lecturer_id !== $lecturer->id) {
             return response()->json(['status' => false, 'message' => 'Không có quyền truy cập'], 403);
        }

        // 2. Validate input
        $validator = Validator::make($request->all(), [
            // Đảm bảo trạng thái gửi lên hợp lệ với DB
            'status' => 'required|in:present,absent,late,excused', 
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $newStatus = $request->status;

        // 3. Cập nhật bản ghi
        $record->status = $newStatus;
        $record->source = 'lecturer'; // Ghi rõ là giảng viên sửa

        // Cập nhật check_in_time dựa trên status
        if (($newStatus == 'present' || $newStatus == 'late') && $record->check_in_time == null) {
            // Nếu là 'có mặt' hoặc 'muộn' và chưa check-in -> set giờ
            $record->check_in_time = Carbon::now('Asia/Ho_Chi_Minh');
        } elseif ($newStatus == 'absent' || $newStatus == 'excused') {
            // Nếu là 'vắng' -> xóa giờ check-in
            $record->check_in_time = null;
        }

        $record->save();

        // 4. Trả về thành công
        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'data' => $record // Trả về record đã cập nhật
        ], 200);
    }
}
