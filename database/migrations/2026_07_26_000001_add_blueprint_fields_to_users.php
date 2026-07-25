<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['unit_id', 'duty_location_id'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId($column));
            }
        }

        $legacyColumns = array_filter(
            ['employee_number', 'phone'],
            fn (string $column): bool => Schema::hasColumn('users', $column),
        );

        if ($legacyColumns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($legacyColumns));
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->unique();
            $table->foreignId('position_id')->constrained();
            $table->foreignId('work_schedule_id')->constrained();
            $table->foreignId('branch_office_id')->constrained();
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->date('join_date');
            $table->boolean('status');
            $table->string('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropConstrainedForeignId('branch_office_id');
            $table->dropConstrainedForeignId('work_schedule_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropUnique(['nip']);
            $table->dropColumn(['nip', 'join_date', 'status', 'role']);
        });
    }
};
