<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')
                ->constrained('links')
                ->cascadeOnDelete();
            $table->foreignId('domain_id')
                ->constrained('domains')
                ->cascadeOnDelete();
            $table->string('country')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('ip');
            $table->timestamp('created_at')->nullable();

            $table->index('domain_id');
            $table->index('link_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_visits');
    }
};
