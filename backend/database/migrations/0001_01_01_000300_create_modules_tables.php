<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version', 20);
            $table->json('requires')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('module_key', 50);
            $table->boolean('active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_key']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('module_key')->references('key')->on('modules')->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('modules');
    }
};
