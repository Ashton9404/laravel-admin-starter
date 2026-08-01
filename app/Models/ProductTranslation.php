<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['locale', 'name', 'summary', 'body'])]
class ProductTranslation extends Model
{
    /**
     * Keeps products.updated_at honest: rewriting a product's text does change
     * the product, even though it changes no column on its own row.
     *
     * Worth knowing that this cannot be used to drive the activity log. Laravel
     * touches owners through the query builder, so the parent's updated event
     * never fires — the log records that edit from the controller instead.
     *
     * @var array<int, string>
     */
    protected $touches = ['product'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
