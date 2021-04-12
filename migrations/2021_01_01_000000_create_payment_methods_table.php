<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodsTable extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', static function (Blueprint $table): void {
            $table->id();

            $table->morphs('customerable');

            $table->boolean('is_primary')->default(false)->index();

            $table->string('stripe_payment_method_id');
            $table->string('stripe_payment_method_type');

            $table->string('bank_name')->nullable();
            $table->string('iban_country')->nullable();
            $table->string('card_country')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->date('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
}
