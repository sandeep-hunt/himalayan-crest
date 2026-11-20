<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Records when an admin approved an account, so "approved" is permanent:
 *   approved_at NULL                 -> waiting for approval (new sign-up)
 *   approved_at set + is_active = 1  -> active
 *   approved_at set + is_active = 0  -> deactivated (can be reactivated, never "pending" again)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });

        // Existing accounts that are active or have logged in before were clearly approved
        DB::table('users')
            ->whereNull('approved_at')
            ->where(fn ($q) => $q->where('is_active', true)->orWhereNotNull('last_login_at'))
            ->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
