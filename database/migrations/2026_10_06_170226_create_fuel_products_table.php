<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_products', function (Blueprint $table) {
            $table->smallIncrements('id');

            $table->string('code', 16)->unique();

            $table->string('name', 80);

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_products');
    }
};