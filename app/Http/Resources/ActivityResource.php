<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            // Falls back to the name stored on the entry, so a deleted account
            // still reads as the person it was rather than as nobody.
            'causer' => [
                'id' => $this->causer_id,
                'name' => $this->causer?->name ?? $this->causer_name,
                // Lets the UI say "deleted account" instead of pretending the
                // row is still clickable.
                'exists' => $this->causer !== null,
            ],
            'subject' => $this->when($this->subject_type !== null, fn () => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'label' => $this->subject_label,
                'exists' => $this->subject !== null,
            ]),
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
