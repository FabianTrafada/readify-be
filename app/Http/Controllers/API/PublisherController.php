<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Publisher::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
        }

        $publishers = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $publishers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:publishers',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $publisher = Publisher::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Publisher created successfully',
            'data' => $publisher
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $publisher = Publisher::with('books')->find($id);

        if (!$publisher) {
            return response()->json([
                'status' => false,
                'message' => 'Publisher not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $publisher
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $publisher = Publisher::find($id);

        if (!$publisher) {
            return response()->json([
                'status' => false,
                'message' => 'Publisher not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:publishers,name,' . $id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $publisher->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Publisher updated successfully',
            'data' => $publisher
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $publisher = Publisher::find($id);

        if (!$publisher) {
            return response()->json([
                'status' => false,
                'message' => 'Publisher not found'
            ], 404);
        }

        $publisher->delete();

        return response()->json([
            'status' => true,
            'message' => 'Publisher deleted successfully'
        ]);
    }
}
