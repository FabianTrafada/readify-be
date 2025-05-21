<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Fine::with(['user', 'borrow']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by payment status
        if ($request->has('is_paid')) {
            $query->where('is_paid', $request->is_paid === 'true');
        }

        $fines = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $fines
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fine = Fine::with(['user', 'borrow'])->find($id);

        if (!$fine) {
            return response()->json([
                'status' => false,
                'message' => 'Fine not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $fine
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function payFine(Request $request, string $id)
    {
        $fine = Fine::find($id);

        if (!$fine) {
            return response()->json([
                'status' => false,
                'message' => 'Fine not found'
            ], 404);
        }

        if ($fine->is_paid) {
            return response()->json([
                'status' => false,
                'message' => 'Fine already paid'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'paid_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $fine->is_paid = true;
        $fine->paid_date = $request->paid_date;
        $fine->save();

        $fine->load(['user', 'borrow']);

        return response()->json([
            'status' => true,
            'message' => 'Fine paid successfully',
            'data' => $fine
        ]);
    }

    /**
     * @OA\Get(
     *     path="/fines",
     *     summary="Get all fines with is_paid",
     *     tags={"Fines"},
     *     @OA\Parameter(
     *         name="is_paid",
     *         in="query",
     *         required=true,
     *         description="Payment status of the fine",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fines retrieved successfully"
     *     )
     * )
    */
    public function getAllFinesWithIsPaid(Request $request)
    {
        $fines = Fine::where('is_paid', $request->is_paid)->get();
        return response()->json($fines);
    }

    /**
     * @OA\Get(
     *     path="/fines",
     *     summary="Get all fines with paid_date",
     *     tags={"Fines"},
     *     @OA\Parameter(
     *         name="paid_date",
     *         in="query",
     *         required=true,
     *         description="Date the fine was paid",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fines retrieved successfully"
     *     )
     * )
    */
    public function getAllFinesWithPaidDate(Request $request)
    {
        $fines = Fine::where('paid_date', $request->paid_date)->get();
        return response()->json($fines);
    }
}
