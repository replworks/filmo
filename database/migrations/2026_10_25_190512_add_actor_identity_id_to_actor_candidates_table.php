<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actor_candidates', function (Blueprint $table) {
            $table->foreignId('actor_identity_id')
                ->nullable()
                ->constrained('actor_identities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('actor_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actor_identity_id');
        });
    }
};
