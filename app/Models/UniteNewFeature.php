<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A newer, simpler "highlights" list for a venue — just a title plus a
 * JSON-cast description field (supports richer/multilingual content than
 * UniteFeature's plain-string description). Has no status field — every
 * row is always shown, there's no activate/deactivate concept here.
 *
 * Distinction from UniteFeature: this model has no dedicated repository
 * and is not used in search/filtering — it exists purely as additional
 * marketing-style highlights, managed inline through
 * UniteRepository::storeNewFeatures() rather than its own CRUD endpoints.
 *
 * Both this model's `newFeatures` relation and UniteFeature's `features`
 * relation are returned side-by-side in SingleUniteResource as separate
 * `new_features` and `features` API keys — the mobile app renders them as
 * two distinct UI sections, so this is not redundant data, just two
 * generations of the same concept that both remain in active use.
 *
 * @see UniteFeature for the older, structured, filterable feature list
 */
class UniteNewFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'title',
        'description',
    ];

    protected $casts = [
        'description' => 'json',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }
}
