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
    /**
     * @OA\Get(
     *     path="/book-shelves",
     *     summary="Get list of book shelves",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by code or location",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of book shelves retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $bookShelves = BookShelf::when($request->search, function ($query, $search) {
            $query->where('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
        })->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $bookShelves
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/book-shelves",
     *     summary="Create a new book shelf",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Book shelf code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         required=true,
     *         description="Book shelf location",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="capacity",
     *         in="query",
     *         required=true,
     *         description="Book shelf capacity",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         required=false,
     *         description="Book shelf description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book shelf created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $rules = [
            'code' => 'required|string|max:50|unique:book_shelves',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ];

        $validator = Validator::make($request->all(), $rules);

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
    /**
     * @OA\Get(
     *     path="/book-shelves/{id}",
     *     summary="Get a book shelf by ID",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book shelf ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book shelf retrieved successfully"
     *     )
     * )
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
    /**
     * @OA\Put(
     *     path="/book-shelves/{id}",
     *     summary="Update a book shelf by ID",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book shelf ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=false,
     *         description="Book shelf code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         required=false,
     *         description="Book shelf location",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="capacity",
     *         in="query",
     *         required=false,
     *         description="Book shelf capacity",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         required=false,
     *         description="Book shelf description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book shelf updated successfully"
     *     )
     * )
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

        $rules = [
            'code' => 'sometimes|required|string|max:50|unique:book_shelves,code,' . $id,
            'location' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string'
        ];

        $validator = Validator::make($request->all(), $rules);

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
    /**
     * @OA\Delete(
     *     path="/book-shelves/{id}",
     *     summary="Delete a book shelf by ID",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book shelf ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book shelf deleted successfully"
     *     )
     * )
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

    /**
     * @OA\Get(
     *     path="/book-shelves/code/{code}",
     *     summary="Get books by name",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(name="code", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Books retrieved successfully"),
     *     @OA\Response(response=404, description="Book not found")
     * )
    */
    public function getBooksByName($code)
    {
        $books = Book::where('code', 'like', "%{$code}%")->paginate(10);

        if ($books->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $books
        ]);
    }

    /**
     * @OA\Get(
     *     path="/book-shelves/location/{location}",
     *     summary="Get books by location",
     *     tags={"Book Shelves"},
     *     @OA\Parameter(name="location", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Books retrieved successfully"),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function getBooksByLocation($location)
    {
        $books = Book::where('location', $location)->paginate(10);

        if ($books->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $books
        ]);
    }


}
