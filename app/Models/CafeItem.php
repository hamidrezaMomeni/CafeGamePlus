<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CafeItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'price',
        'is_available',
        'image_path',
        'description',
    ];

    protected $casts = [
        'price'        => 'integer',
        'is_available' => 'boolean',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () =>
        $this->image_path
            ? asset('storage/' . $this->image_path)
            : asset('images/default-item.png')
        );
    }
}
