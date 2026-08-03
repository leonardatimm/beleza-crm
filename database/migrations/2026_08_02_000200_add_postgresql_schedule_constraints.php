<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(<<<'SQL'
            ALTER TABLE appointments
            ADD CONSTRAINT appointments_no_overlap
            EXCLUDE USING gist (
                tenant_id WITH =,
                professional_profile_id WITH =,
                tstzrange(starts_at, ends_at, '[)') WITH &&
            ) WHERE (status <> 'cancelled')
        SQL);
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_valid_interval CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_no_overlap');
        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_valid_interval');
    }
};
