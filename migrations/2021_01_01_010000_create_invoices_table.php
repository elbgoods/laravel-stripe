<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', static function (Blueprint $table): void {
            $table->id();

            $table->morphs('customerable');

            $table->string('stripe_invoice_id')->nullable()->index()->unique();
            $table->string('stripe_invoice_status')->nullable();
            $table->string('stripe_payment_intent_status')->nullable();
            $table->string('stripe_pdf_url')->nullable();

            $table->integer('total')->unsigned()->nullable();
            $table->string('currency', 3)->nullable();

            $table->timestamp('billing_range_start')->nullable();
            $table->timestamp('billing_range_end')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}
