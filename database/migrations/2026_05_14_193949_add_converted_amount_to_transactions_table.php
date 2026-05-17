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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('converted_amount', 14, 2)->nullable()->after('amount');
            $table->string('converted_currency', 10)->nullable()->after('converted_amount');
            $table->decimal('exchange_rate', 12, 6)->default(1)->after('converted_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['converted_amount', 'converted_currency', 'exchange_rate']);
        });
    }
};
