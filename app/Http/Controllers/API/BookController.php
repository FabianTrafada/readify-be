<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/books",
     *     summary="Get list of books",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by title, isbn, or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="author_id",
     *         in="query",
     *         required=false,
     *         description="Filter by author ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="publisher_id",
     *         in="query",
     *         required=false,
     *         description="Filter by publisher ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="available",
     *         in="query",
     *         required=false,
     *         description="Filter by availability",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of books retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Book::with(['authors', 'categories', 'publisher', 'bookShelf']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Filter by author
        if ($request->has('author_id')) {
            $query->whereHas('authors', function($q) use ($request) {
                $q->where('authors.id', $request->author_id);
            });
        }

        // Filter by publisher
        if ($request->has('publisher_id')) {
            $query->where('publisher_id', $request->publisher_id);
        }

        // Filter by availability
        if ($request->has('available') && $request->available == 'true') {
            $query->where('available_copies', '>', 0);
        }

        $books = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $books
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/books",
     *     summary="Create a new book",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         required=true,
     *         description="Book title",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="isbn",
     *         in="query",
     *         required=true,
     *         description="Book ISBN",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         required=false,
     *         description="Book description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="publication_year",
     *         in="query",
     *         required=true,
     *         description="Book publication year",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="total_copies",
     *         in="query",
     *         required=true,
     *         description="Total copies of the book",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="available_copies",
     *         in="query",
     *         required=true,
     *         description="Available copies of the book",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="cover_image",
     *         in="query",
     *         required=false,
     *         description="Book cover image URL",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="publisher_id",
     *         in="query",
     *         required=false,
     *         description="Publisher ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="book_shelf_id",
     *         in="query",
     *         required=false,
     *         description="Book shelf ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="author_ids",
     *         in="query",
     *         required=true,
     *         description="Author IDs",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(
     *         name="category_ids",
     *         in="query",
     *         required=true,
     *         description="Category IDs",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books',
            'description' => 'nullable|string',
            'publication_year' => 'required|integer',
            'total_copies' => 'required|integer|min:0',
            'available_copies' => 'required|integer|min:0|lte:total_copies',
            'cover_image' => 'nullable|string',
            'publisher_id' => 'nullable|exists:publishers,id',
            'book_shelf_id' => 'nullable|exists:book_shelves,id',
            'author_ids' => 'required|array|min:1',
            'author_ids.*' => 'exists:authors,id',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::create([
            'title' => $request->title,
            'isbn' => $request->isbn,
            'description' => $request->description,
            'publication_year' => $request->publication_year,
            'total_copies' => $request->total_copies,
            'available_copies' => $request->available_copies,
            'cover_image' => $request->cover_image,
            'publisher_id' => $request->publisher_id,
            'book_shelf_id' => $request->book_shelf_id,
        ]);

        $book->authors()->attach($request->author_ids);
        $book->categories()->attach($request->category_ids);

        $book->load(['authors', 'categories', 'publisher', 'bookShelf']);

        return response()->json([
            'status' => true,
            'message' => 'Book created successfully',
            'data' => $book
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     summary="Get a book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book found"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $book = Book::with(['authors', 'categories', 'publisher', 'bookShelf'])->find($id);

        if (!$book) {
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $book
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/books/{id}",
     *     summary="Update a book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'isbn' => 'sometimes|required|string|unique:books,isbn,' . $id,
            'description' => 'nullable|string',
            'publication_year' => 'sometimes|required|integer',
            'total_copies' => 'sometimes|required|integer|min:0',
            'available_copies' => 'sometimes|required|integer|min:0|lte:total_copies',
            'cover_image' => 'nullable|string',
            'publisher_id' => 'nullable|exists:publishers,id',
            'book_shelf_id' => 'nullable|exists:book_shelves,id',
            'author_ids' => 'sometimes|required|array|min:1',
            'author_ids.*' => 'exists:authors,id',
            'category_ids' => 'sometimes|required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book->update($request->only([
            'title', 'isbn', 'description', 'publication_year', 
            'total_copies', 'available_copies', 'cover_image',
            'publisher_id', 'book_shelf_id'
        ]));

        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }

        if ($request->has('category_ids')) {
            $book->categories()->sync($request->category_ids);
        }

        $book->load(['authors', 'categories', 'publisher', 'bookShelf']);

        return response()->json([
            'status' => true,
            'message' => 'Book updated successfully',
            'data' => $book
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/books/{id}",
     *     summary="Delete a book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $book->authors()->detach();
        $book->categories()->detach();
        $book->delete();

        return response()->json([
            'status' => true,
            'message' => 'Book deleted successfully'
        ]);
    }
}
