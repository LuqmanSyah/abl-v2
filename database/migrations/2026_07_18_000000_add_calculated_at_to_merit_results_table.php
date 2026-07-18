<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merit_results', function (Blueprint $table): void {
            $table->timestamp('calculated_at')->nullable()->after('estimated_bonus');
        });
    }

    public function down(): void
    {
        Schema::table('merit_results', function (Blueprint $table): void {
            $table->dropColumn('calculated_at');
        });
    }
};
