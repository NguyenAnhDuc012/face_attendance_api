<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoomController extends Controller
{
    // Lấy danh sách phòng, phân trang 5 bản ghi
    public function index()
    {
        return Room::orderBy('id', 'desc')->paginate(5);
    }

    // Tạo phòng mới
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $room = Room::create($validatedData);

        return response()->json($room, Response::HTTP_CREATED);
    }

    // Hiển thị chi tiết phòng
    public function show(Room $room)
    {
        return $room;
    }

    // Cập nhật phòng
    public function update(Request $request, Room $room)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $room->update($validatedData);

        return response()->json($room);
    }

    // Xóa phòng
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->noContent();
    }
}
