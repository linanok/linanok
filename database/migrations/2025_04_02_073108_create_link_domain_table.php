<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_domain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')
                ->constrained('links')
                ->cascadeOnDelete();
            $table->foreignId('domain_id')
                ->constrained('domains')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_domain');
    }
};
