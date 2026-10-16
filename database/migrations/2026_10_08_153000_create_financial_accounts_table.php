<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('bank_name', 120);
            $table->string('branch', 40)->nullable();
            $table->string('account_number', 64);
            $table->string('type', 20);
            $table->char('currency', 3)->default('BRL');
            $table->bigInteger('opening_balance_minor')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['bank_name', 'branch', 'account_number'], 'financial_account_number_unique');
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
