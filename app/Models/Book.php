<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'isbn',
        'description',
        'publication_year',
        'total_copies',
        'available_copies',
        'cover_image',
        'publisher_id',
        'book_shelf_id',
    ];

    public function authors()
    {
        // Tambahkan parameter nama tabel pivot yang benar
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function bookShelf()
    {
        return $this->belongsTo(BookShelf::class);
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
