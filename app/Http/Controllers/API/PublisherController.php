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
    /**
     * @OA\Get(
     *     path="/publishers",
     *     security={{"bearerAuth":{}}},
     *     summary="Get list of publishers",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by name or address",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of publishers retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Publisher::query();

        if ($request->has("search")) {
            $search = $request->search;
            $query
                ->where("name", "like", "%{$search}%")
                ->orWhere("address", "like", "%{$search}%");
        }

        $publishers = $query->paginate(10);

        return response()->json([
            "status" => true,
            "data" => $publishers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/publishers",
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new publisher",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=true,
     *         description="Name of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="address",
     *         in="query",
     *         required=false,
     *         description="Address of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         required=false,
     *         description="Phone number of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=false,
     *         description="Email of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Publisher created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255|unique:publishers",
            "address" => "nullable|string",
            "phone" => "nullable|string|max:20",
            "email" => "nullable|email",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Validation error",
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        $publisher = Publisher::create($request->all());

        return response()->json(
            [
                "status" => true,
                "message" => "Publisher created successfully",
                "data" => $publisher,
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/publishers/{id}",
     *     security={{"bearerAuth":{}}},
     *     summary="Get a publisher by ID",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the publisher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Publisher retrieved successfully"
     *     )
     * )
     */
    public function show(string $id)
    {
        $publisher = Publisher::with("books")->find($id);

        if (!$publisher) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Publisher not found",
                ],
                404
            );
        }

        return response()->json([
            "status" => true,
            "data" => $publisher,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/publishers/{id}",
     *     security={{"bearerAuth":{}}},
     *     summary="Update a publisher by ID",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the publisher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Name of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="address",
     *         in="query",
     *         required=false,
     *         description="Address of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         required=false,
     *         description="Phone number of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=false,
     *         description="Email of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Publisher updated successfully"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $publisher = Publisher::find($id);

        if (!$publisher) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Publisher not found",
                ],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            "name" =>
                "sometimes|required|string|max:255|unique:publishers,name," .
                $id,
            "address" => "nullable|string",
            "phone" => "nullable|string|max:20",
            "email" => "nullable|email",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Validation error",
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        $publisher->update($request->all());

        return response()->json([
            "status" => true,
            "message" => "Publisher updated successfully",
            "data" => $publisher,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/publishers/{id}",
     *     security={{"bearerAuth":{}}},
     *     summary="Delete a publisher by ID",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the publisher",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Publisher deleted successfully"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $publisher = Publisher::find($id);

        if (!$publisher) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Publisher not found",
                ],
                404
            );
        }

        $publisher->delete();

        return response()->json([
            "status" => true,
            "message" => "Publisher deleted successfully",
        ]);
    }

    /**
     * @OA\Get(
     *     path="/publishers/name/{name}",
     *     security={{"bearerAuth":{}}},
     *     summary="Get all publishers with name",
     *     tags={"Publishers"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Name of the publisher",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Publishers retrieved successfully"
     *     )
     * )
     */
    public function getAllPublishersWithName(Request $request)
    {
        $publishers = Publisher::where("name", $request->name)->get();
        return response()->json($publishers);
    }
}
