<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('stock');
            $table->json('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->string('sku')->nullable();
            $table->string('brand')->nullable();
            $table->string('storage')->nullable();
            $table->string('color')->nullable();
            $table->string('condition')->nullable();
            $table->string('model_number')->nullable();
            $table->decimal('buy_price', 10, 2)->nullable();
            $table->decimal('sell_price', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
