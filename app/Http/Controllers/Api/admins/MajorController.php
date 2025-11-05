<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MajorController extends Controller
{
    public function index()
    {
        return Major::with('department.faculty.facility')->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        $major = Major::create($validatedData);

        return response()->json($major, Response::HTTP_CREATED);
    }

    public function show(Major $major)
    {
        return $major->load('department.faculty.facility');
    }

    public function update(Request $request, Major $major)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'department_id' => 'sometimes|required|exists:departments,id',
        ]);

        $major->update($validatedData);

        return response()->json($major);
    }

    public function destroy(Major $major)
    {
        $major->delete();
        return response()->noContent();
    }
}
