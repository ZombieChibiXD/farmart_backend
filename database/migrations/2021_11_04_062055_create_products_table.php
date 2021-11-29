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
            $table->foreignId('store_id')->constrained('stores');
            $table->string('fullname');
            $table->string('shortname');
            $table->string('unit');
            $table->string('slug');
            $table->decimal('price', 10, 2);
            $table->decimal('price_discounted', 10, 2)->nullable();
            $table->string('labeled')->nullable();
            $table->mediumText('description');
            $table->bigInteger('sold')->default(0);
            $table->bigInteger('stock')->default(0);
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
