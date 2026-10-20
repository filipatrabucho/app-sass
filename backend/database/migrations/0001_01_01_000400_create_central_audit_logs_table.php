<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('central_admin_id')->nullable()->constrained('central_admins')->nullOnDelete();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('action', 100);
            $table->string('auditable_type')->nullable();
            $table->string('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_audit_logs');
    }
};
