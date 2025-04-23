<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrow;
use App\Models\Fine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BorrowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/borrows",
     *     summary="Get list of borrows",
     *     tags={"Borrows"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by user_id or book_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of borrows retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Borrow::with(['user', 'book']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by book
        if ($request->has('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('borrow_date', [$request->from_date, $request->to_date]);
        }

        $borrows = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $borrows
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/borrows",
     *     summary="Create a new borrow",
     *     tags={"Borrows"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="book_id",
     *         in="query",
     *         required=true,
     *         description="Book ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="borrow_date",
     *         in="query",
     *         required=true,
     *         description="Borrow date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="due_date",
     *         in="query",
     *         required=true,
     *         description="Due date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="notes",
     *         in="query",
     *         required=false,
     *         description="Notes",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Borrow created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'borrow_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:borrow_date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = Book::find($request->book_id);
        
        if ($book->available_copies <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Book is not available for borrow'
            ], 400);
        }

        $borrow = Borrow::create([
            'user_id' => $request->user_id,
            'book_id' => $request->book_id,
            'borrow_date' => $request->borrow_date,
            'due_date' => $request->due_date,
            'status' => 'borrowed',
            'notes' => $request->notes
        ]);

        // Update book available copies
        $book->available_copies = $book->available_copies - 1;
        $book->save();

        $borrow->load(['user', 'book']);

        return response()->json([
            'status' => true,
            'message' => 'Book borrowed successfully',
            'data' => $borrow
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/borrows/{id}",
     *     summary="Get a specific borrow",
     *     tags={"Borrows"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Borrow record retrieved successfully"
     *     )
     * )
     */
    public function show(string $id)
    {
        $borrow = Borrow::with(['user', 'book', 'fine'])->find($id);

        if (!$borrow) {
            return response()->json([
                'status' => false,
                'message' => 'Borrow record not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $borrow
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * @OA\Put(
     *     path="/api/borrows/{id}/return",
     *     summary="Return a book",
     *     tags={"Borrows"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book returned successfully"
     *     )
     * )
     */
    public function returnBook(Request $request, $id)
    {
        $borrow = Borrow::find($id);

        if (!$borrow) {
            return response()->json([
                'status' => false,
                'message' => 'Borrow record not found'
            ], 404);
        }

        if ($borrow->status === 'returned') {
            return response()->json([
                'status' => false,
                'message' => 'Book already returned'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'return_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $returnDate = Carbon::parse($request->return_date);
        $dueDate = Carbon::parse($borrow->due_date);
        $fineAmount = 0;

        // Calculate fine if return date is after due date
        if ($returnDate->gt($dueDate)) {
            $daysLate = $returnDate->diffInDays($dueDate);
            $fineAmount = $daysLate * 1000; // 1000 per day late
            
            // Create fine record
            Fine::create([
                'borrow_id' => $borrow->id,
                'user_id' => $borrow->user_id,
                'amount' => $fineAmount,
                'reason' => "Book returned {$daysLate} days late"
            ]);
        }

        $borrow->return_date = $request->return_date;
        $borrow->fine_amount = $fineAmount;
        $borrow->status = 'returned';
        $borrow->save();

        // Update book available copies
        $book = Book::find($borrow->book_id);
        $book->available_copies = $book->available_copies + 1;
        $book->save();

        $borrow->load(['user', 'book', 'fine']);

        return response()->json([
            'status' => true,
            'message' => 'Book returned successfully',
            'data' => $borrow
        ]);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/borrows/{id}",
     *     summary="Delete a borrow record",
     *     tags={"Borrows"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the borrow record",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Borrow record deleted successfully"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $borrow = Borrow::find($id);

        if (!$borrow) {
            return response()->json([
                'status' => false,
                'message' => 'Borrow record not found'
            ], 404);
        }

        // Check if book is not returned yet
        if ($borrow->status !== 'returned') {
            // Update book available copies
            $book = Book::find($borrow->book_id);
            $book->available_copies = $book->available_copies + 1;
            $book->save();
        }

        // Delete any associated fines
        if ($borrow->fine) {
            $borrow->fine->delete();
        }
        
        $borrow->delete();

        return response()->json([
            'status' => true,
            'message' => 'Borrow record deleted successfully'
        ]);
    }
}
