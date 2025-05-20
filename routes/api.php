<?php
// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\AuthorController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\PublisherController;
use App\Http\Controllers\API\BorrowController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\FineController;
use App\Http\Controllers\API\BookShelfController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Search books (public)
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{id}', [AuthorController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/publishers', [PublisherController::class, 'index']);
Route::get('/publishers/{id}', [PublisherController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Member routes
    Route::get('/my-borrows', [BorrowController::class, 'userBorrows']);
    Route::get('/my-reservations', [ReservationController::class, 'userReservations']);
    Route::get('/my-fines', [FineController::class, 'userFines']);
    Route::post('/reserve-book', [ReservationController::class, 'reserveBook']);
    
    // Admin & Librarian routes
    Route::middleware('role:admin,librarian')->group(function () {
        // Books management
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
        Route::get('/books/author/{author_id}', [BookController::class, 'getBooksByAuthorId']);
        Route::get('/books/category/{category_id}', [BookController::class, 'getBooksByCategoryId']);
        Route::get('/books/name/{name}', [BookController::class, 'getBooksByName']);
        
        // Authors management
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::put('/authors/{id}', [AuthorController::class, 'update']);
        Route::delete('/authors/{id}', [AuthorController::class, 'destroy']);
        Route::get('/authors/name/{name}', [AuthorController::class, 'getAuthorByName']);
        Route::get('/authors/birth_date/{birth_date}', [AuthorController::class, 'getAuthorByBirthDate']);
        
        // Categories management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        // Publishers management
        Route::post('/publishers', [PublisherController::class, 'store']);
        Route::put('/publishers/{id}', [PublisherController::class, 'update']);
        Route::delete('/publishers/{id}', [PublisherController::class, 'destroy']);
        
        // Borrows management
        Route::get('/borrows', [BorrowController::class, 'index']);
        Route::post('/borrows', [BorrowController::class, 'store']);
        Route::get('/borrows/{id}', [BorrowController::class, 'show']);
        Route::post('/borrows/{id}/return', [BorrowController::class, 'returnBook']);
        Route::delete('/borrows/{id}', [BorrowController::class, 'destroy']);
        
        // Reservations management
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/reservations/{id}', [ReservationController::class, 'show']);
        Route::put('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
        Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);
        
        // Fines management
        Route::get('/fines', [FineController::class, 'index']);
        Route::get('/fines/{id}', [FineController::class, 'show']);
        Route::post('/fines/{id}/pay', [FineController::class, 'payFine']);
        
        // Book shelves management
        Route::get('/book-shelves', [BookShelfController::class, 'index']);
        Route::post('/book-shelves', [BookShelfController::class, 'store']);
        Route::get('/book-shelves/{id}', [BookShelfController::class, 'show']);
        Route::put('/book-shelves/{id}', [BookShelfController::class, 'update']);
        Route::delete('/book-shelves/{id}', [BookShelfController::class, 'destroy']);
    });
    
    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::get('/users', [AuthController::class, 'index']);
        Route::post('/users', [AuthController::class, 'createUser']);
        Route::get('/users/{id}', [AuthController::class, 'showUser']);
        Route::put('/users/{id}', [AuthController::class, 'updateUser']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
        Route::put('/users/{id}/role', [AuthController::class, 'updateUserRole']);
    });
});