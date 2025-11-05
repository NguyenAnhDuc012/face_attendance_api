<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::with('faculty.facility')->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        $department = Department::create($validatedData);

        return response()->json($department, Response::HTTP_CREATED);
    }

    public function show(Department $department)
    {
        return $department->load('faculty');
    }

    public function update(Request $request, Department $department)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'faculty_id' => 'sometimes|required|exists:faculties,id',
        ]);

        $department->update($validatedData);

        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }
}
