<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
                $table->string('original_url', 2048)->charset('utf8')->collation('utf8_general_ci')->index();
                $table->string('short_path')->charset('utf8')->collation('utf8_general_ci')->index();
            } else {
                $table->string('original_url', 2048)->index();
                $table->string('short_path')->index();
            }
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('available_at')->nullable();
            $table->dateTime('unavailable_at')->nullable();
            $table->boolean('forward_query_parameters');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('visit_count')->default(0);
            $table->boolean('send_ref_query_parameter');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
