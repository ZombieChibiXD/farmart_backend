<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('store_id')->constrained('stores');
            $table->decimal('price', 10, 2);
            $table->decimal('discountedPrice', 10, 2)->nullable();
            $table->string('labeled')->nullable();
            $table->mediumText('description');
            $table->bigInteger('in_stock')->default(0);
            $table->bigInteger('likes')->default(0);
            // $table->foreignId('season_id')->constrained('seasons');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
