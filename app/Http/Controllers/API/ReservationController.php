<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['user', 'book']);

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

        $reservations = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $reservations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'reservation_date' => 'required|date',
            'expiry_date' => 'required|date|after:reservation_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already has an active reservation for this book
        $existingReservation = Reservation::where('user_id', $request->user_id)
            ->where('book_id', $request->book_id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();
            
        if ($existingReservation) {
            return response()->json([
                'status' => false,
                'message' => 'User already has an active reservation for this book'
            ], 400);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user_id,
            'book_id' => $request->book_id,
            'reservation_date' => $request->reservation_date,
            'expiry_date' => $request->expiry_date,
            'status' => 'pending'
        ]);

        $reservation->load(['user', 'book']);

        return response()->json([
            'status' => true,
            'message' => 'Reservation created successfully',
            'data' => $reservation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservation = Reservation::with(['user', 'book'])->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'Reservation not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $reservation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, string $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'Reservation not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,canceled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation->status = $request->status;
        $reservation->save();

        $reservation->load(['user', 'book']);

        return response()->json([
            'status' => true,
            'message' => 'Reservation status updated successfully',
            'data' => $reservation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'status' => false,
                'message' => 'Reservation not found'
            ], 404);
        }

        $reservation->delete();

        return response()->json([
            'status' => true,
            'message' => 'Reservation deleted successfully'
        ]);
    }
}
