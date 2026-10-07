<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Lien de la page de paiement Chariow du produit (ex. https://boutique.mychariow.com/p/mon-livre).
            $table->string('chariow_product_url', 500)->nullable()->after('chariow_product_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // Compte créé automatiquement lors d'un achat sans connexion : le client n'a pas encore choisi son mot de passe.
            $table->boolean('is_guest')->default(false)->after('is_admin');
        });

        // Une vente du prestataire = une seule commande, même si le webhook est rejoué en parallèle.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_reference']);
            $table->unique('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->index('payment_reference');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_guest'));
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('chariow_product_url'));
    }
};
