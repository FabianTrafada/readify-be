<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/authors",
     *     security={{"bearerAuth":{}}},
     *     summary="Get list of authors",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by name or biography",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of authors retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Author::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('biography', 'like', "%{$search}%");
        }

        $authors = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $authors
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/authors",
     *     summary="Create a new author",
     *     security={{"bearerAuth":{}}},
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=true,
     *         description="Author name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="biography",
     *         in="query",
     *         required=false,
     *         description="Author biography",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="birth_date",
     *         in="query",
     *         required=false,
     *         description="Author birth date",
     *         @OA\Schema(type="date")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Author created successfully"
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
            'name' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $author = Author::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Author created successfully',
            'data' => $author
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/authors/{id}",
     *     summary="Get author by ID",
     *     security={{"bearerAuth":{}}},
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Author ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $author = Author::with('books')->find($id);

        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => 'Author not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $author
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/authors/{id}",
     *     summary="Update an author",
     *     security={{"bearerAuth":{}}},
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Author ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Author name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="biography",
     *         in="query",
     *         required=false,
     *         description="Author biography",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="birth_date",
     *         in="query",
     *         required=false,
     *         description="Author birth date",
     *         @OA\Schema(type="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => 'Author not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $author->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Author updated successfully',
            'data' => $author
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/authors/{id}",
     *     security={{"bearerAuth":{}}},
     *     summary="Delete an author",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Author ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $author = Author::find($id);

        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => 'Author not found'
            ], 404);
        }

        $author->books()->detach();
        $author->delete();

        return response()->json([
            'status' => true,
            'message' => 'Author deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/authors/name/{name}",
     *     security={{"bearerAuth":{}}},
     *     summary="Get author by name",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Author name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found"
     *     )
     * )
     */
    public function getAuthorByName(string $name)
    {
        $author = Author::where('name', 'like', "%{$name}%")->paginate(10);

        // Nah, ini nih yang bener! Ceknya pakai isEmpty()
        if ($author->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Author not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $author
        ]);
    }

    /**
     * @OA\Get(
     *     path="/authors/birth_date/{birth_date}",
     *     security={{"bearerAuth":{}}},
     *     summary="Get author by birth_date",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="birth_date",
     *         in="path",
     *         description="Author birth_date",
     *         @OA\Schema(type="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Author not found"
     *     )
     * )
     */
    public function getAuthorByBirthDate(string $birth_date)
    {
        $author = Author::where('birth_date', $birth_date)->paginate(10);

        if ($author->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Author not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $author
        ]);
    }
}
