<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('face_descriptor_path', 255)->nullable()->after('face_descriptor');
        });

        $dir = storage_path('app/private/face-descriptors');
        File::ensureDirectoryExists($dir, 0700);

        foreach (DB::table('attendances')->whereNotNull('face_descriptor')->select('id', 'face_descriptor', 'client_uuid')->cursor() as $row) {
            $filename = ($row->client_uuid ?? (string) Str::uuid()) . '.json';
            $path = "face-descriptors/{$filename}";
            File::put("{$dir}/{$filename}", $row->face_descriptor);
            DB::table('attendances')->where('id', $row->id)->update(['face_descriptor_path' => $path]);
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('face_descriptor_path');
        });
    }
};
