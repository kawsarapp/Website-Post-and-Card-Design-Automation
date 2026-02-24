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
        Schema::table('users', function (Blueprint $table) {
            // চেক করে নিচ্ছি কলামটি আগে থেকে আছে কি না (যদি আপনি SQL দিয়ে করে থাকেন)
            if (!Schema::hasColumn('users', 'staff_limit')) {
                $table->integer('staff_limit')->default(0)->after('daily_post_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'staff_limit')) {
                $table->dropColumn('staff_limit');
            }
        });
    }
};