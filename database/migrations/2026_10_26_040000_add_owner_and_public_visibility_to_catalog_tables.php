<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['youtube_channels', 'youtube_videos', 'actors'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->boolean('is_public')->default(true)->index();
            });
        }

        $adminId = DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            return;
        }

        foreach (['youtube_channels', 'youtube_videos', 'actors'] as $tableName) {
            DB::table($tableName)->whereNull('user_id')->update(['user_id' => $adminId]);
        }
    }

    public function down(): void
    {
        foreach (['youtube_channels', 'youtube_videos', 'actors'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['user_id', 'is_public']);
            });
        }
    }
};
