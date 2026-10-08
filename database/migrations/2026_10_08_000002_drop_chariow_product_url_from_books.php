<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retour au paiement par API Chariow : le lien de la page produit n'est plus utilisé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'chariow_product_url')) {
            Schema::table('books', fn (Blueprint $table) => $table->dropColumn('chariow_product_url'));
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('chariow_product_url', 500)->nullable()->after('chariow_product_id');
        });
    }
};
