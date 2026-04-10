<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->string('preparation_area', 20)->default('kitchen')->after('price');
        });

        DB::table('dishes')
            ->whereNull('preparation_area')
            ->update(['preparation_area' => 'kitchen']);
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn('preparation_area');
        });
    }
};
