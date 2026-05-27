<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('total_amount');
            }

            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }

            if (! Schema::hasColumn('orders', 'order_sound_requested_at')) {
                $table->timestamp('order_sound_requested_at')->nullable()->after('ready_at');
            }

            if (! Schema::hasColumn('orders', 'order_sound_played_at')) {
                $table->timestamp('order_sound_played_at')->nullable()->after('order_sound_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];

            foreach (['is_paid', 'paid_at', 'order_sound_requested_at', 'order_sound_played_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
