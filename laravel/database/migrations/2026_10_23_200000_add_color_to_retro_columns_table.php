<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retro_columns', function (Blueprint $table): void {
            $table->string('color', 20)->default('sky')->after('title');
        });

        $map = [
            'Хорошо'   => 'emerald', 'Радует' => 'emerald', 'Продолжить' => 'emerald',
            'Плохо'    => 'rose', 'Бесит' => 'rose', 'Прекратить' => 'rose',
            'Грустно'  => 'amber',
            'Действия' => 'sky', 'Начать' => 'sky',
        ];

        foreach ($map as $title => $color) {
            DB::table('retro_columns')->where('title', $title)->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('retro_columns', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }
};
