<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', ['superadmin@nexiocrm.uz'])->update([
            'is_platform_admin' => 1,
            'platform_role' => 'platform_superadmin',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', ['superadmin@nexiocrm.uz'])->update([
            'is_platform_admin' => 0,
            'platform_role' => null,
            'updated_at' => now(),
        ]);
    }
};
