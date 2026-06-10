<?php

namespace App\Models;

use App\Http\Controllers\PlateController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Psy\Readline\Interactive\Input\History;

class Plate extends Model
{
    use HasFactory;

    protected $fillable = ['license_plate_number', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plateTransfersHistory(): HasMany
    {
        return $this->hasMany(PlateTransfersHistory::class);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($plate) {
            $plate->license_plate_number = PlateController::generate();
            $plate->user_id = Auth::id();
        });
    }
}
