<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->unique()->after('name');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('admin_one_time_access')->default(false)->after('company_notes');
        });

        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $phone = null;
            if ($user->shop_id) {
                $shopPhone = DB::table('shops')->where('id', $user->shop_id)->value('phone');
                $phone = preg_replace('/\D+/', '', (string) $shopPhone) ?: null;
            }
            if (! $phone) {
                $phone = '09'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT);
            }
            $exists = DB::table('users')->where('phone', $phone)->where('id', '!=', $user->id)->exists();
            if ($exists) {
                $phone = $phone.$user->id;
            }
            DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn('phone');
        });
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('admin_one_time_access');
        });
    }
};
