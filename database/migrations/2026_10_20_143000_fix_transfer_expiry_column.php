<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MariaDB can implicitly add ON UPDATE CURRENT_TIMESTAMP to the first
        // non-null TIMESTAMP column. A later transfer update then destroys the
        // expiry chosen by the sender. DATETIME has no implicit update behavior.
        DB::statement('ALTER TABLE transfers MODIFY expires_at DATETIME NOT NULL');

        // Existing values shorter than one day were overwritten by that
        // implicit behavior. The transfer form defaults to seven days.
        DB::statement(<<<'SQL'
            UPDATE transfers
            SET expires_at = DATE_ADD(created_at, INTERVAL 7 DAY)
            WHERE expires_at >= created_at
              AND expires_at < DATE_ADD(created_at, INTERVAL 1 DAY)
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transfers MODIFY expires_at TIMESTAMP NOT NULL');
        }
    }
};
