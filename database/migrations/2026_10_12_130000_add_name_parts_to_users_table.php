<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 120)->nullable();
            $table->string('middle_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $parts = preg_split('/\s+/u', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $firstName = array_shift($parts) ?? '';
                    $lastName = count($parts) > 0 ? array_pop($parts) : null;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'first_name' => $firstName,
                            'middle_name' => $parts !== [] ? implode(' ', $parts) : null,
                            'last_name' => $lastName,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name']);
        });
    }
};
