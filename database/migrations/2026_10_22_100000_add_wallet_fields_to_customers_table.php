<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // موجودی کیف پول؛ مقدار منفی یعنی مشتری بدهکار است.
            $table->decimal('wallet_balance', 14, 2)->default(0)->after('visit_count');
            // سقف اعتبار اختصاصی؛ null یعنی از مقدار پیش‌فرض سیستم استفاده شود.
            $table->decimal('credit_limit', 14, 2)->nullable()->after('wallet_balance');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'credit_limit']);
        });
    }
};