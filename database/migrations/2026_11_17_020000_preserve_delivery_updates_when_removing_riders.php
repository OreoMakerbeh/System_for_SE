<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_updates', function (Blueprint $table): void {
            $table->dropForeign(['rider_id']);
        });

        Schema::table('delivery_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('rider_id')->nullable()->change();
            $table->foreign('rider_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('delivery_updates')->whereNull('rider_id')->exists()) {
            throw new RuntimeException('Restore a rider for every delivery update before rolling back this migration.');
        }

        Schema::table('delivery_updates', function (Blueprint $table): void {
            $table->dropForeign(['rider_id']);
        });

        Schema::table('delivery_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('rider_id')->nullable(false)->change();
            $table->foreign('rider_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
