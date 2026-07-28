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
        Schema::table('swipes', function (Blueprint $table) {
            $table->dateTime('watch_scheduled_at')->nullable()->after('is_liked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swipes', function (Blueprint $table) {
            $table->dropColumn('watch_scheduled_at');
        });
    }
};
