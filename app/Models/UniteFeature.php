<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The original, structured amenities/features list for a venue — e.g.
 * "Parking", "Free WiFi", "Catering" — each with an activate/deactivate
 * status and an optional longer description.
 *
 * Distinction from UniteNewFeature: this is the older of the two feature
 * lists and is the one actually queryable in search/filtering — see
 * UniteRepository::all() which does whereHas('features', fn($q) =>
 * $q->whereIn('name', $services)) to filter venues by amenity. It also has
 * a dedicated repository (UniteFeatureRepository / UniteFeatureInterface)
 * with full CRUD, used by the admin/provider "manage features" screens.
 *
 * Both this model's `features` relation and UniteNewFeature's `newFeatures`
 * relation are returned side-by-side in SingleUniteResource as separate
 * `features` and `new_features` API keys — the mobile app renders them as
 * two distinct UI sections, so this is not redundant data, just two
 * generations of the same concept that both remain in active use.
 *
 * @see UniteNewFeature for the newer, simpler highlights list
 * @see \App\Repositories\Unite\UniteFeatureRepository
 */
class UniteFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'name',
        'description',
        'status',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }
}
