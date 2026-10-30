<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('body');
        });

        foreach (DB::table('retro_columns')->pluck('id') as $columnId) {
            $position = 0;
            foreach (DB::table('cards')->where('retro_column_id', $columnId)->orderBy('id')->pluck('id') as $cardId) {
                DB::table('cards')->where('id', $cardId)->update(['position' => $position++]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
