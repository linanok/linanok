<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->string('protocol')->default('https');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_admin_panel_active')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['protocol', 'host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
