<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\RichText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private readonly RichText $richText) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Product::class, only: ['index']),
            new Middleware('can:create,'.Product::class, only: ['store']),
            new Middleware('can:view,product', only: ['show']),
            new Middleware('can:update,product', only: ['update']),
            new Middleware('can:delete,product', only: ['destroy']),
            new Middleware('can:reorder,'.Product::class, only: ['reorder']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::in([Product::DRAFT, Product::PUBLISHED])],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ]);

        $products = Product::query()
            ->with('translations')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';

                // Grouped: without the outer where() closure the OR would escape
                // its scope and turn "status = draft AND (name OR slug)" into
                // "(status = draft AND name) OR slug".
                $query->where(fn ($q) => $q
                    ->whereHas('translations', fn ($t) => $t->where('name', 'like', $term))
                    ->orWhere('slug', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->ordered()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load('translations'));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create([
                'slug' => $request->string('slug'),
                'status' => $request->string('status'),
                // New products land at the end of the catalogue rather than
                // silently sharing position 0 with everything else.
                'sort_order' => (int) Product::max('sort_order') + 1,
                'published_at' => $request->string('status') === Product::PUBLISHED ? now() : null,
            ]);

            $this->syncTranslations($product, $request->input('translations', []));

            return $product;
        });

        return ProductResource::make($product->load('translations'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        DB::transaction(function () use ($request, $product) {
            $product->fill($request->safe()->only('slug', 'status'));

            // Stamp the first publication only. Re-publishing after a spell as a
            // draft should not rewrite the original date.
            if ($product->isPublished() && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();

            if ($request->has('translations')) {
                $this->syncTranslations($product, $request->input('translations', []));
            }
        });

        return ProductResource::make($product->fresh()->load('translations'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => __('Product deleted.')]);
    }

    /**
     * Persist a drag-and-drop reordering.
     *
     * The client sends the ids in their new visual order; position is derived
     * from the array index rather than trusted from the client, so the stored
     * sequence can never end up with gaps or duplicates.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $position => $id) {
                Product::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->json(['message' => __('Order saved.')]);
    }

    /**
     * @param  array<string, array{name: string, summary?: string|null, body?: string|null}>  $translations
     */
    private function syncTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $locale => $values) {
            $product->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => $values['name'],
                    'summary' => $values['summary'] ?? null,
                    // Sanitised on the way in, so the database only ever holds
                    // HTML that is safe to render.
                    'body' => $this->richText->sanitize($values['body'] ?? null),
                ],
            );
        }
    }
}
