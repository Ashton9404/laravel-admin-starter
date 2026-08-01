<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            // The name the user recognises. Kept separate from `path`, which is
            // a randomised storage key so two uploads called "logo.png" cannot
            // overwrite each other.
            $table->string('name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            // Deleting an account leaves its uploads in place: the files are the
            // organisation's, not the individual's.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
