<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'base_currency')) {
                $table->string('base_currency', 10)->default('USD')->after('exchange_rate');
            }
            if (!Schema::hasColumn('shops', 'secondary_currency')) {
                $table->string('secondary_currency', 10)->default('SYP')->after('base_currency');
            }
        });

        // Enable always-on double-entry auto-posting for all existing shops
        if (Schema::hasColumn('shops', 'posting_enabled')) {
            DB::table('shops')->update(['posting_enabled' => true]);
        }

        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('shop_id');
                $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('accounts', 'level')) {
                $table->unsignedTinyInteger('level')->default(3)->after('parent_id');
            }
            if (!Schema::hasColumn('accounts', 'is_postable')) {
                $table->boolean('is_postable')->default(true)->after('classification');
            }
            if (!Schema::hasColumn('accounts', 'normal_balance')) {
                $table->string('normal_balance', 10)->default('debit')->after('is_postable');
            }
            if (!Schema::hasColumn('accounts', 'statement_type')) {
                $table->string('statement_type', 20)->default('balance_sheet')->after('normal_balance');
            }
            if (!Schema::hasColumn('accounts', 'currency')) {
                $table->string('currency', 10)->default('USD')->after('statement_type');
            }
            if (!Schema::hasColumn('accounts', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('currency');
            }
            if (!Schema::hasColumn('accounts', 'system_key')) {
                $table->string('system_key', 50)->nullable()->after('is_system');
                $table->index(['shop_id', 'system_key']);
            }
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_lines', 'exchange_rate')) {
                $table->decimal('exchange_rate', 14, 4)->default(1)->after('currency');
            }
            if (!Schema::hasColumn('journal_lines', 'foreign_debit')) {
                $table->decimal('foreign_debit', 16, 2)->default(0)->after('exchange_rate');
            }
            if (!Schema::hasColumn('journal_lines', 'foreign_credit')) {
                $table->decimal('foreign_credit', 16, 2)->default(0)->after('foreign_debit');
            }
            if (!Schema::hasColumn('journal_lines', 'contact_id')) {
                $table->unsignedBigInteger('contact_id')->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('journal_lines', 'cost_center_id')) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('contact_id');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('expense_type');
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['exchange_rate', 'foreign_debit', 'foreign_credit', 'contact_id', 'cost_center_id'],
                fn ($c) => Schema::hasColumn('journal_lines', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }
            $cols = array_values(array_filter(
                ['parent_id', 'level', 'is_postable', 'normal_balance', 'statement_type', 'currency', 'is_system', 'system_key'],
                fn ($c) => Schema::hasColumn('accounts', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('shops', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['base_currency', 'secondary_currency'],
                fn ($c) => Schema::hasColumn('shops', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
