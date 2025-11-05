<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class LecturerController extends Controller
{

    public function index()
    {
        return Lecturer::with('faculty.facility')->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'email' => 'required|string|email|max:255|unique:lecturers',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $lecturer = Lecturer::create($validatedData);

        return response()->json($lecturer, Response::HTTP_CREATED);
    }


    public function show(Lecturer $lecturer)
    {
        return $lecturer->load('faculty.facility');
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $validatedData = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'faculty_id' => 'sometimes|required|exists:faculties,id',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('lecturers')->ignore($lecturer->id),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        $lecturer->update(array_filter($validatedData));

        return response()->json($lecturer);
    }


    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return response()->noContent();
    }
}
