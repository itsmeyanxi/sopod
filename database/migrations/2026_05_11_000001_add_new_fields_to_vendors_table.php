<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('name_2307')->nullable()->after('vendor_name');
            $table->string('billing_street')->nullable()->after('name_2307');
            $table->string('billing_block')->nullable()->after('billing_street');
            $table->string('billing_city')->nullable()->after('billing_block');
            $table->string('billing_zip')->nullable()->after('billing_city');
            $table->string('billing_country')->nullable()->after('billing_zip');
            $table->string('shipping_street')->nullable()->after('billing_country');
            $table->string('shipping_block')->nullable()->after('shipping_street');
            $table->string('shipping_city')->nullable()->after('shipping_block');
            $table->string('shipping_zip')->nullable()->after('shipping_city');
            $table->string('shipping_country')->nullable()->after('shipping_zip');
            $table->string('payment_terms')->nullable()->after('shipping_country');
            $table->string('selling_price_list')->nullable()->after('payment_terms');
            $table->string('vat')->nullable()->after('selling_price_list');
            $table->string('withholding')->nullable()->after('vat');
            $table->string('registration')->nullable()->after('withholding');
            $table->string('tin')->nullable()->after('registration');
            $table->string('account')->nullable()->after('tin');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'name_2307',
                'billing_street','billing_block','billing_city','billing_zip','billing_country',
                'shipping_street','shipping_block','shipping_city','shipping_zip','shipping_country',
                'payment_terms','selling_price_list','vat','withholding','registration','tin','account',
            ]);
        });
    }
};
