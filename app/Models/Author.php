<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'biography',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function books()
    {
        // Tambahkan parameter nama tabel pivot yang benar
        return $this->belongsToMany(Book::class, 'book_author');
    }
}
