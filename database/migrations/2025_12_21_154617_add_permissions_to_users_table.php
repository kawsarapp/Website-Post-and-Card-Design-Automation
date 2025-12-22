<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
	public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // permissions কলামটি json টাইপ হিসেবে যুক্ত করা হচ্ছে
        $table->json('permissions')->nullable()->after('role');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('permissions');
    });
}
	
};
