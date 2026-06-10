<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[WithoutTimestamps]
class PlateTransfersHistory extends Model
{
    use HasFactory;

    protected $fillable = ['from_user_id', 'to_user_id', 'plate_id', 'transferred_at'];

    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($plateTransfersHistory) {
            $plateTransfersHistory->from_user_id = Auth::id();
            $plateTransfersHistory->transferred_at = Carbon::now()->toJSON();
        });
    }
}
