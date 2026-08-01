<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The catalogue as the outside world sees it.
 *
 * Separate from ProductController rather than a public branch inside it. The
 * admin controller's job is to expose everything to whoever is authorised;
 * this one's job is to expose almost nothing to everyone. Those are opposite
 * defaults, and mixing them into one class is how a draft ends up on the public
 * site because a query scope was applied in four places out of five.
 */
class PublicProductController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->published()
            ->with('translations')
            // The order an administrator set by dragging, which is the whole
            // point of the reordering feature having existed.
            ->ordered()
            ->paginate(self::PER_PAGE);

        return PublicProductResource::collection($products);
    }

    public function show(string $slug): PublicProductResource
    {
        // 404 rather than 403 for a draft. A 403 would confirm that a product
        // with that slug exists, which is exactly what an unpublished product
        // should not be telling anyone.
        $product = Product::query()
            ->published()
            ->with('translations')
            ->where('slug', $slug)
            ->firstOrFail();

        return PublicProductResource::make($product)->withBody();
    }
}
