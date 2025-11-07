<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\FaceImage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $student = $request->user();
        $file = $request->file('image');

        // 1. Xóa ảnh cũ
        $oldImages = $student->faceImages;
        foreach ($oldImages as $oldImage) {
            Storage::delete($oldImage->image_path);
            $oldImage->delete();
        }

        // 2. Xóa embedding cũ
        $student->faceEmbeddings()->delete();

        // 3. Lưu ảnh mới (Dùng disk 'public')
        $path = $file->store('face_images/student_' . $student->id, 'public');

        // 4. Lưu path vào bảng face_images
        $newImage = $student->faceImages()->create([
            'image_path' => $path, // $path giờ là 'face_images/student_5/...'
        ]);

        // ===== 5. TẠO EMBEDDING MỚI (PHẦN CẬP NHẬT) =====
        try {
            $fullImagePath = Storage::disk('public')->path($path);
            
            // ===== THAY ĐỔI 2 DÒNG NÀY =====
            // 1. Đường dẫn đến python.exe TRONG MÔI TRƯỜNG ẢO
            $pythonExecutable = base_path('.venv/Scripts/python.exe');
            // 2. Đường dẫn đến script Python
            $pythonScriptPath = base_path('scripts/create_embedding.py');

            // 3. Cập nhật lệnh Process
            $process = new Process([$pythonExecutable, $pythonScriptPath, $fullImagePath]);
            // =================================
            
            $process->mustRun(); 

            $output = $process->getOutput();
            $embeddingData = json_decode($output, true);

            if ($embeddingData['status'] == 'error') {
                throw new \Exception($embeddingData['message']);
            }

            // Lưu embedding vector vào DB
            $student->faceEmbeddings()->create([
                'embedding_vector' => $embeddingData['embedding'],
            ]);
        } catch (\Exception $e) {
            // Nếu có lỗi, xóa ảnh vừa tải lên (rollback)
            Storage::disk('public')->delete($path);
            $newImage->delete();

            return response()->json([
                'status' => false,
                'message' => 'Lỗi xử lý khuôn mặt: ' . $e->getMessage()
            ], 500);
        }
        // =================================================

        // 6. Trả về URL công khai
        return response()->json([
            'status' => true,
            'message' => 'Tải ảnh lên thành công!',
            'new_image_url' => asset('storage/' . $path)
        ]);
    }
}
