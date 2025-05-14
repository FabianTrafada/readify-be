<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * @OA\Get(
     *     path="/books",
     *     summary="Retrieve list of books with optional filters",
     *     tags={"Books"},
     *     @OA\Parameter(name="search", in="query", required=false, description="Keyword for title, ISBN, or description", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", required=false, description="Filter by category", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="author_id", in="query", required=false, description="Filter by author", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="publisher_id", in="query", required=false, description="Filter by publisher", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="available", in="query", required=false, description="Only show available books", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Books retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        $books = Book::with(['authors', 'categories', 'publisher', 'bookShelf'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, fn($q, $id) => $q->whereHas('categories', fn($subQ) => $subQ->where('id', $id)))
            ->when($request->author_id, fn($q, $id) => $q->whereHas('authors', fn($subQ) => $subQ->where('id', $id)))
            ->when($request->publisher_id, fn($q, $id) => $q->where('publisher_id', $id))
            ->when($request->available === 'true', fn($q) => $q->where('available_copies', '>', 0))
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * @OA\Post(
     *     path="/books",
     *     summary="Add a new book",
     *     tags={"Books"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"title", "isbn", "publication_year", "total_copies", "available_copies", "author_ids", "category_ids"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="isbn", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="publication_year", type="integer"),
     *         @OA\Property(property="total_copies", type="integer"),
     *         @OA\Property(property="available_copies", type="integer"),
     *         @OA\Property(property="cover_image", type="string"),
     *         @OA\Property(property="publisher_id", type="integer"),
     *         @OA\Property(property="book_shelf_id", type="integer"),
     *         @OA\Property(property="author_ids", type="array", @OA\Items(type="integer")),
     *         @OA\Property(property="category_ids", type="array", @OA\Items(type="integer"))
     *     )),
     *     @OA\Response(response=201, description="Book created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $rules = [
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
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::create($request->only([
            'title', 'isbn', 'description', 'publication_year',
            'total_copies', 'available_copies', 'cover_image',
            'publisher_id', 'book_shelf_id'
        ]));

        $book->authors()->attach($request->author_ids);
        $book->categories()->attach($request->category_ids);
        $book->load(['authors', 'categories', 'publisher', 'bookShelf']);

        return response()->json([
            'success' => true,
            'message' => 'Book added successfully',
            'data' => $book
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/books/{id}",
     *     summary="Fetch a specific book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Book found"),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function show($id)
    {
        $book = Book::with(['authors', 'categories', 'publisher', 'bookShelf'])->find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $book
        ]);
    }

    /**
     * @OA\Put(
     *     path="/books/{id}",
     *     summary="Update existing book",
     *     tags={"Books"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Book updated"),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
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
                'success' => false,
                'message' => 'Validation failed',
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
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => $book
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/books/{id}",
     *     summary="Remove a book by ID",
     *     tags={"Books"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Book deleted"),
     *     @OA\Response(response=404, description="Book not found")
     * )
     */
    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }

        $book->authors()->detach();
        $book->categories()->detach();
        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully'
        ]);
    }
}
