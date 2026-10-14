<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shopper\Core\Helpers\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'gender')) {
            return;
        }

        Schema::table('users', static function (Blueprint $table): void {
            $table->string('gender')->nullable()->change();
        });
    }

    public function down(): void
    {
        // The gender column stays nullable: making it mandatory again could
        // not succeed on a table that already holds users without a gender.
    }
};
