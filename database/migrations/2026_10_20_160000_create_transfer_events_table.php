<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transfer_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('channel', 30)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at');
            $table->index(['transfer_id', 'event_type']);
            $table->index(['transfer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_events');
    }
};
