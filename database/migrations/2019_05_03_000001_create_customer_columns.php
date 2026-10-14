<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shopper\Core\Helpers\Migration;

return new class extends Migration
{
    /**
     * Columns this migration owns. The host application may already carry
     * some of them (or a `name` column it relies on): every column is added
     * only when it is missing, nothing is renamed, and `down()` removes only
     * what `up()` added.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'last_name' => 'string',
        'first_name' => 'string',
        'gender' => 'string',
        'birth_date' => 'date',
        'avatar_type' => 'avatar_type',
        'avatar_location' => 'string',
        'timezone' => 'string',
        'opt_in' => 'opt_in',
        'last_login_at' => 'timestamp',
        'last_login_ip' => 'string',
    ];

    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            foreach (self::COLUMNS as $column => $kind) {
                if (Schema::hasColumn('users', $column)) {
                    continue;
                }

                match ($kind) {
                    'string' => $table->string($column)->nullable(),
                    'date' => $table->date($column)->nullable(),
                    'timestamp' => $table->timestamp($column)->nullable(),
                    'avatar_type' => $table->string($column)->default('avatar_ui'),
                    'opt_in' => $table->boolean($column)->default(false),
                };
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            foreach (array_keys(self::COLUMNS) as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
