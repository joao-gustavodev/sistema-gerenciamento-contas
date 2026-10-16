<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()
                ->constrained('account_transactions')->restrictOnDelete();
            $table->string('type', 10);
            $table->unsignedBigInteger('amount_minor');
            $table->string('description', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['financial_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
