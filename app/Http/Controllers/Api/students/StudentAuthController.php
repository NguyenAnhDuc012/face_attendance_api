<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // ===== CẬP NHẬT TRUY VẤN: LẤY THÊM TÊN LỚP =====
        $student = Student::with('studentClass:id,name') // Tải quan hệ studentClass
            ->where('email', $request->email)
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email hoặc mật khẩu không đúng!',
            ], 401);
        }

        $student->tokens()->delete();
        $token = $student->createToken('student-auth-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công!',
            'data' => [
                'student' => $student, // $student giờ đã chứa 'student_class'
                'token' => $token,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }
}
