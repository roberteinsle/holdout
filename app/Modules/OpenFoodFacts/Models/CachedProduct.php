<?php

namespace App\Modules\OpenFoodFacts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One cached Open Food Facts answer for a barcode.
 */
class CachedProduct extends Model
{
    protected $table = 'off_cache';

    protected $primaryKey = 'barcode';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['barcode', 'found', 'payload', 'fetched_at', 'image_path'];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
