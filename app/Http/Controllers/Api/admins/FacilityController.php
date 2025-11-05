<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacilityController extends Controller
{
    public function index()
    {
        return Facility::orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $facility = Facility::create($validatedData);

        return response()->json($facility, Response::HTTP_CREATED);
    }

    public function show(Facility $facility)
    {
        return $facility;
    }

    public function update(Request $request, Facility $facility)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $facility->update($validatedData);

        return response()->json($facility);
    }

    public function destroy(Facility $facility)
    {
        $facility->delete();

        return response()->noContent();
    }
}
