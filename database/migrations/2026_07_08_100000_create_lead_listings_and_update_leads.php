<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('external_id')->nullable()->index(); // MLUxxxxxxx / 2clics ID
            $table->string('source')->nullable();               // ml, 2clics, manual

            $table->text('title')->nullable();
            $table->string('property_type')->nullable();
            $table->string('operation')->nullable();            // venta / alquiler

            $table->decimal('asking_price', 12, 2)->nullable();
            $table->string('price_currency', 5)->default('USD');

            $table->text('zone_raw')->nullable();               // zona completa sin parsear
            $table->text('listing_url')->nullable();

            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->decimal('m2_covered', 10, 2)->nullable();
            $table->decimal('m2_total', 10, 2)->nullable();

            $table->json('attributes')->nullable();             // resto de atributos

            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_listing_id')->nullable()->after('lead_status_id');
            $table->foreign('primary_listing_id')->references('id')->on('lead_listings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['primary_listing_id']);
            $table->dropColumn('primary_listing_id');
        });

        Schema::dropIfExists('lead_listings');
    }
};
