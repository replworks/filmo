<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('contributable');
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(
                ['user_id', 'contributable_type', 'contributable_id', 'status'],
                'contributions_user_record_status_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
