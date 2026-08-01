<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ProductCoverController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [new Middleware('can:update,product')];
    }

    /**
     * Replace a product's cover image.
     *
     * Kept off the JSON resource endpoints deliberately: mixing a file upload
     * into the product payload would force the whole editor — nested
     * translations and all — through multipart form encoding.
     */
    public function store(Request $request, Product $product): ProductResource
    {
        $request->validate([
            // `image` checks the actual decoded image, not just the extension,
            // so a renamed .php cannot walk in as a .jpg.
            'cover' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        $previous = $product->cover_path;

        $product->forceFill([
            'cover_path' => $request->file('cover')->store('products', 'public'),
        ])->save();

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return ProductResource::make($product->load('translations'));
    }

    public function destroy(Product $product): ProductResource
    {
        if ($product->cover_path) {
            Storage::disk('public')->delete($product->cover_path);
            $product->forceFill(['cover_path' => null])->save();
        }

        return ProductResource::make($product->load('translations'));
    }
}
