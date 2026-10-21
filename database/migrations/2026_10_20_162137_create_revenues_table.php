<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('amount_minor');
            $table->date('expected_date');
            $table->string('source', 120)->nullable();
            $table->text('notes')->nullable();
            $table->char('currency', 3)->default('BRL');
            $table->string('status', 20)->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status', 'expected_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
