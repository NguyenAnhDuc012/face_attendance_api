<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\FaceImage; // Import model

class StudentProfileController extends Controller
{
    /**
     * Lấy thông tin profile của sinh viên.
     */
    public function getProfile(Request $request)
    {
        $student = $request->user();

        // Tải thông tin lớp và ảnh khuôn mặt
        $student->load('studentClass:id,name', 'faceImages');

        // Lấy ảnh đầu tiên (hoặc null)
        $faceImage = $student->faceImages->first();
        
        $imageUrl = null;
        if ($faceImage) {
            // Đảm bảo bạn đã chạy 'php artisan storage:link'
            $imageUrl = Storage::url($faceImage->image_path);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'full_name' => $student->full_name,
                'email' => $student->email,
                'class_name' => $student->studentClass->name ?? 'N/A',
                'image_url' => $imageUrl,
            ]
        ]);
    }

    /**
     * Tải lên ảnh khuôn mặt mới.
     */
    public function uploadFaceImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'image' là key mà Flutter sẽ gửi lên
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $student = $request->user();
        $file = $request->file('image');

        // 1. Xóa ảnh cũ (nếu có)
        $oldImages = $student->faceImages;
        foreach ($oldImages as $oldImage) {
            Storage::delete($oldImage->image_path); // Xóa file
            $oldImage->delete(); // Xóa record
        }

        // 2. Xóa embedding cũ (quan trọng)
        // Việc này báo cho hệ thống (có thể là Python) biết cần tạo embedding mới
        $student->faceEmbeddings()->delete();

        // 3. Lưu ảnh mới
        // Path: storage/app/public/face_images/student_1/abc.jpg
        $pathWithoutPublic = 'face_images/student_' . $student->id;
        $path = $file->store($pathWithoutPublic, 'public');

        // 4. Lưu path vào bảng face_images
        $newImage = $student->faceImages()->create([
            'image_path' => $path,
        ]);

        // 5. Trả về URL công khai
        return response()->json([
            'status' => true,
            'message' => 'Tải ảnh lên thành công!',
            'new_image_url' => Storage::url($path) // URL cho Flutter hiển thị
        ]);
    }
}