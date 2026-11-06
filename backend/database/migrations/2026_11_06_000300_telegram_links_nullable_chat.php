<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * chat_id is unique, and pending links stored '' — so a second user could never start linking.
     * Pending links now hold NULL, which unique indexes allow any number of times.
     */
    public function up(): void
    {
        Schema::table('user_telegram_links', function (Blueprint $table) {
            $table->string('chat_id')->nullable()->change();
        });
        DB::table('user_telegram_links')->where('chat_id', '')->update(['chat_id' => null]);
    }

    public function down(): void
    {
        // Reverting would need '' placeholders again, which the unique index rejects for more than one row.
    }
};
