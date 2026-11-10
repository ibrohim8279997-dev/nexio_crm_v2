<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Records which filesystem disk holds each attachment so uploads can move to S3 without orphaning old files. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('attachments') && !Schema::hasColumn('attachments', 'disk')) {
            Schema::table('attachments', function (Blueprint $t) {
                $t->string('disk', 20)->default('local');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attachments') && Schema::hasColumn('attachments', 'disk')) {
            Schema::table('attachments', fn (Blueprint $t) => $t->dropColumn('disk'));
        }
    }
};
