<?php

namespace App\Http\Controllers;

use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MediaController extends Controller implements HasMiddleware
{
    /**
     * Extensions accepted for upload.
     *
     * An allow-list, never a block-list: enumerating what is dangerous is a game
     * you lose the first time someone finds an extension you forgot.
     *
     * SVG is deliberately absent. It is XML, it can carry <script>, and served
     * from our own origin the browser executes it — which would reopen exactly
     * the stored-XSS hole the rich-text sanitiser exists to close. Supporting it
     * safely needs either sanitising the XML or serving uploads from a separate
     * origin; until one of those exists, PNG and WebP cover the same ground.
     */
    private const ALLOWED = 'jpeg,jpg,png,webp,gif,pdf';

    private const MAX_KILOBYTES = 8192;

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Media::class, only: ['index']),
            new Middleware('can:create,'.Media::class, only: ['store']),
            new Middleware('can:delete,medium', only: ['destroy']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images_only' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:12', 'max:100'],
        ]);

        $media = Media::query()
            ->with('uploader')
            ->when($request->filled('search'), fn ($query) => $query->where(
                'name',
                'like',
                '%'.$request->string('search').'%'
            ))
            // The picker embedded in the editor and the cover field only care
            // about images; the library page shows everything.
            ->when($request->boolean('images_only'), fn ($query) => $query->images())
            ->latest()
            ->paginate($request->integer('per_page', 24))
            ->withQueryString();

        return MediaResource::collection($media);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:'.self::ALLOWED, 'max:'.self::MAX_KILOBYTES],
        ]);

        $file = $request->file('file');

        $media = Media::create([
            'disk' => 'public',
            // Laravel generates a random storage name, so two people uploading
            // "logo.png" get two files rather than one overwriting the other.
            'path' => $file->store('media', 'public'),
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->getKey(),
        ]);

        return MediaResource::make($media->load('uploader'))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Media $medium): JsonResponse
    {
        // The model's deleting hook removes the file, so the row and the bytes
        // can never drift apart.
        $medium->delete();

        return response()->json(['message' => __('File deleted.')]);
    }
}
