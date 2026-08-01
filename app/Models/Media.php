<?php

namespace App\Models;

use App\Contracts\Loggable;
use App\Models\Concerns\LogsActivity;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['disk', 'path', 'name', 'mime_type', 'size', 'uploaded_by'])]
class Media extends Model implements Loggable
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'media';

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Remove the file as well as the row. Called from the model's deleting event
     * so a delete can never leave an orphan behind on disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            Storage::disk($media->disk)->delete($media->path);
        });
    }

    /**
     * @param  Builder<Media>  $query
     */
    public function scopeImages(Builder $query): void
    {
        $query->where('mime_type', 'like', 'image/%');
    }
}
