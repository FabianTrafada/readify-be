<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookShelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookShelfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BookShelf::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
        }

        $bookShelves = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $bookShelves
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:book_shelves',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $bookShelf = BookShelf::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Book shelf created successfully',
            'data' => $bookShelf
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bookShelf = BookShelf::with('books')->find($id);

        if (!$bookShelf) {
            return response()->json([
                'status' => false,
                'message' => 'Book shelf not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $bookShelf
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bookShelf = BookShelf::find($id);

        if (!$bookShelf) {
            return response()->json([
                'status' => false,
                'message' => 'Book shelf not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:50|unique:book_shelves,code,' . $id,
            'location' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $bookShelf->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Book shelf updated successfully',
            'data' => $bookShelf
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bookShelf = BookShelf::find($id);

        if (!$bookShelf) {
            return response()->json([
                'status' => false,
                'message' => 'Book shelf not found'
            ], 404);
        }

        // Check if there are books assigned to this shelf
        if ($bookShelf->books()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete book shelf with assigned books'
            ], 400);
        }

        $bookShelf->delete();

        return response()->json([
            'status' => true,
            'message' => 'Book shelf deleted successfully'
        ]);
    }
}
