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
        Schema::table('package_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_questions', function (Blueprint $table) {
            Schema::table('package_questions', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        });
    }
};
