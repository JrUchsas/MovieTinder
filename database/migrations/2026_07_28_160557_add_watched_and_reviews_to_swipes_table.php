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
        Schema::table('swipes', function (Blueprint $table): void {
            $table->boolean('is_watched')->default(false)->after('is_liked');
            $table->unsignedTinyInteger('user_rating')->nullable()->after('is_watched');
            $table->text('user_review')->nullable()->after('user_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swipes', function (Blueprint $table): void {
            $table->dropColumn(['is_watched', 'user_rating', 'user_review']);
        });
    }
};
