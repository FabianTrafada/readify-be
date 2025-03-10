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
