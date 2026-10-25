<?php

use App\Models\Actor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('education')->nullable();
            $table->string('debut')->nullable();
            $table->string('agency')->nullable();
            $table->string('nationality')->nullable();
        });

        DB::table('actors')
            ->select(['id', 'profile'])
            ->orderBy('id')
            ->chunkById(500, function ($actors): void {
                foreach ($actors as $actor) {
                    $profile = json_decode((string) $actor->profile, true);

                    if (! is_array($profile)) {
                        continue;
                    }

                    $values = [];

                    foreach (Actor::PROFILE_COLUMNS as $column) {
                        if (isset($profile[$column]) && is_string($profile[$column]) && $profile[$column] !== '') {
                            $values[$column] = $profile[$column];
                            unset($profile[$column]);
                        }
                    }

                    if ($values === []) {
                        continue;
                    }

                    $values['profile'] = $profile === [] ? null : json_encode($profile, JSON_UNESCAPED_UNICODE);

                    DB::table('actors')->where('id', $actor->id)->update($values);
                }
            });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'birth_place',
                'education',
                'debut',
                'agency',
                'nationality',
            ]);
        });
    }
};
