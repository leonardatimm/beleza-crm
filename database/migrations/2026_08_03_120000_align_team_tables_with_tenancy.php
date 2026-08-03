<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('teams', 'tenants');
        Schema::rename('team_members', 'tenant_memberships');

        Schema::table('tenant_memberships', function (Blueprint $table) {
            $table->renameColumn('team_id', 'tenant_id');
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->renameColumn('team_id', 'tenant_id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('business_type', 20)->nullable()->after('is_personal');
            $table->string('document_type', 4)->nullable()->after('business_type');
            $table->string('document_number', 14)->nullable()->after('document_type');
            $table->string('legal_name')->nullable()->after('document_number');
            $table->string('trade_name')->nullable()->after('legal_name');
            $table->string('phone', 20)->nullable()->after('trade_name');
            $table->string('responsible_cpf', 11)->nullable()->after('phone');
            $table->timestamp('onboarding_completed_at')->nullable()->after('currency');
            $table->unique(['document_type', 'document_number'], 'tenants_document_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique('tenants_document_unique');
            $table->dropColumn([
                'business_type',
                'document_type',
                'document_number',
                'legal_name',
                'trade_name',
                'phone',
                'responsible_cpf',
                'onboarding_completed_at',
            ]);
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'team_id');
        });

        Schema::table('tenant_memberships', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'team_id');
        });

        Schema::rename('tenant_memberships', 'team_members');
        Schema::rename('tenants', 'teams');
    }
};
