<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlateTransfersHistoriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate' => new PlateResource($this->plate),
            'from_user' => new UserResource($this->fromUser),
            'to_user' => new UserResource($this->toUser),
            'transferred_at' => $this->transferred_at
        ];
    }
}
