<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_number')->nullable()->unique();
        });

        DB::table('orders')->update(['order_number' => DB::raw('id')]);

        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('lock_version')->default(0);
        });

        DB::table('order_number_sequences')->insert([
            'id' => 1,
            'lock_version' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }
};
