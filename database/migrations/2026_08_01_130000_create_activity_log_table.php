<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();

            // Who did it. The account may be deleted later, so the name is kept
            // alongside the key: an audit trail that forgets who acted the moment
            // their account goes is useless exactly when it matters most.
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('causer_name')->nullable();

            // What it was done to. Nullable because sign-ins have no subject.
            $table->nullableMorphs('subject');
            // Same reasoning as causer_name: after a product is deleted the row
            // has to still say *which* product, and the morph relation cannot.
            $table->string('subject_label')->nullable();

            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();

            // created_at only. A log entry is a statement about a moment that has
            // already passed; an updated_at column would advertise that entries
            // can be rewritten, which is the one thing an audit trail must not do.
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
