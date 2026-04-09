<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_ready')->default(false)->after('total_amount');
            $table->timestamp('ready_at')->nullable()->after('is_ready');
            $table->timestamp('ready_sound_requested_at')->nullable()->after('ready_at');
            $table->timestamp('ready_sound_played_at')->nullable()->after('ready_sound_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_ready',
                'ready_at',
                'ready_sound_requested_at',
                'ready_sound_played_at',
            ]);
        });
    }
};
