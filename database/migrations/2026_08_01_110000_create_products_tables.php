<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // The public URL key. Language-independent on purpose: a product
            // keeps one address however many languages it is written in.
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            // Lower sorts first. Indexed because the public listing orders by it
            // on every request.
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('cover_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('summary')->nullable();
            // Sanitised HTML from the editor. longText because a product page
            // with images and tables outruns TEXT's 64KB sooner than you think.
            $table->longText('body')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
    }
};
