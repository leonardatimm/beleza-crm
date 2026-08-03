<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->string('currency', 3)->default('BRL');
        });

        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('color', 7)->default('#7c3aed');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('normalized_phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'normalized_phone']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->unsignedBigInteger('price_cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('professional_service', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('professional_profile_id');
            $table->foreignId('service_id');
            $table->timestamps();
            $table->primary(['professional_profile_id', 'service_id']);
            $table->foreign(['tenant_id', 'professional_profile_id'])->references(['tenant_id', 'id'])->on('professional_profiles')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'service_id'])->references(['tenant_id', 'id'])->on('services')->cascadeOnDelete();
        });

        Schema::create('professional_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('professional_profile_id');
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign(['tenant_id', 'professional_profile_id'])->references(['tenant_id', 'id'])->on('professional_profiles')->cascadeOnDelete();
            $table->unique(['tenant_id', 'professional_profile_id', 'weekday', 'starts_at', 'ends_at'], 'professional_availability_unique');
        });

        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('professional_profile_id');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->foreign(['tenant_id', 'professional_profile_id'])->references(['tenant_id', 'id'])->on('professional_profiles')->cascadeOnDelete();
            $table->index(['tenant_id', 'professional_profile_id', 'starts_at']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('customer_id');
            $table->foreignId('professional_profile_id');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('scheduled');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedBigInteger('total_cents');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
            $table->foreign(['tenant_id', 'customer_id'])->references(['tenant_id', 'id'])->on('customers')->restrictOnDelete();
            $table->foreign(['tenant_id', 'professional_profile_id'])->references(['tenant_id', 'id'])->on('professional_profiles')->restrictOnDelete();
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'professional_profile_id', 'starts_at']);
            $table->index(['tenant_id', 'status', 'starts_at']);
        });

        Schema::create('appointment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('appointment_id');
            $table->foreignId('service_id')->nullable();
            $table->string('service_name');
            $table->unsignedInteger('duration_minutes');
            $table->unsignedBigInteger('price_cents');
            $table->timestamps();
            $table->foreign(['tenant_id', 'appointment_id'])->references(['tenant_id', 'id'])->on('appointments')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'service_id'])->references(['tenant_id', 'id'])->on('services')->nullOnDelete();
        });

        Schema::create('appointment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('appointment_id');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->foreign(['tenant_id', 'appointment_id'])->references(['tenant_id', 'id'])->on('appointments')->cascadeOnDelete();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name', 'type']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->string('origin_type', 30)->default('manual');
            $table->string('description');
            $table->unsignedBigInteger('amount_cents');
            $table->date('due_date');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('reversed_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('financial_entries')->nullOnDelete();
            $table->string('external_reference')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();
            $table->foreign(['tenant_id', 'appointment_id'])->references(['tenant_id', 'id'])->on('appointments')->restrictOnDelete();
            $table->foreign(['tenant_id', 'category_id'])->references(['tenant_id', 'id'])->on('financial_categories')->nullOnDelete();
            $table->foreign(['tenant_id', 'payment_method_id'])->references(['tenant_id', 'id'])->on('payment_methods')->nullOnDelete();
            $table->unique(['tenant_id', 'appointment_id', 'origin_type'], 'financial_entry_origin_unique');
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'status', 'due_date']);
            $table->index(['tenant_id', 'type', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('appointment_status_histories');
        Schema::dropIfExists('appointment_items');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('professional_availabilities');
        Schema::dropIfExists('professional_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('professional_profiles');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'currency']);
        });
    }
};
