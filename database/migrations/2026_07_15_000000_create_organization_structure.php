<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();
            $table->unique(['unit_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Employee->value)->index();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_number')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'role',
                'unit_id',
                'position_id',
                'manager_id',
                'employee_number',
                'phone',
                'is_active',
            ]);
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('units');
    }
};
