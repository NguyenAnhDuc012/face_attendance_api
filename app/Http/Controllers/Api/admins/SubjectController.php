<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectController extends Controller
{

    public function index()
    {
        return Subject::with('department.faculty.facility')->orderBy('id', 'desc')->paginate(5);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'credits' => 'required|integer|min:1|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        $subject = Subject::create($validatedData);

        return response()->json($subject, Response::HTTP_CREATED);
    }


    public function show(Subject $subject)
    {
        return $subject->load('department.faculty.facility');
    }

    public function update(Request $request, Subject $subject)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'credits' => 'sometimes|required|integer|min:1|max:255',
            'department_id' => 'sometimes|required|exists:departments,id',
        ]);

        $subject->update($validatedData);

        return response()->json($subject);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return response()->noContent();
    }
}
