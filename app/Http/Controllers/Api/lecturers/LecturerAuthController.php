<?php

namespace App\Http\Controllers\Api\lecturers;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LecturerAuthController extends Controller
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

        $lecturer = Lecturer::where('email', $request->email)->first();

        if (!$lecturer || !Hash::check($request->password, $lecturer->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email hoặc mật khẩu không đúng!',
            ], 401);
        }

        // Xóa token cũ (nếu có) và tạo token mới
        $lecturer->tokens()->delete();
        $token = $lecturer->createToken('lecturer-auth-token')->plainTextToken;
        // ================================

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công!',
            // Trả về cả token và thông tin giảng viên
            'data' => [
                'lecturer' => $lecturer,
                'token' => $token,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        // Yêu cầu middleware 'auth:sanctum'
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }
}
