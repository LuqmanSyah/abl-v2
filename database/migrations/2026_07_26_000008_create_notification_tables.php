<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::connection(config('webpush.database_connection'))
            ->create(config('webpush.table_name'), function (Blueprint $table): void {
                $table->id();
                $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
                $table->string('endpoint', 500)->unique();
                $table->string('public_key')->nullable();
                $table->string('auth_token')->nullable();
                $table->string('content_encoding')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('webpush.database_connection'))
            ->dropIfExists(config('webpush.table_name'));
        Schema::dropIfExists('notifications');
    }
};
