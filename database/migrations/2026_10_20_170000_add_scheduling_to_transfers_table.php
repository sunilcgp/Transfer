<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->dateTime('scheduled_at')->nullable()->after('message')->index();
            $table->dateTime('sent_at')->nullable()->after('scheduled_at')->index();
        });

        DB::table('transfers')->whereNull('sent_at')->update(['sent_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->dropColumn(['scheduled_at', 'sent_at']);
        });
    }
};
