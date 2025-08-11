<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')
                ->constrained('links')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('link_id');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_tags');
    }
};
