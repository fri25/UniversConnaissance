<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Identifiant du produit correspondant dans Chariow (ex. prd_abc123).
            $table->string('chariow_product_id', 100)->nullable()->after('epub_path');
        });

        Schema::table('users', function (Blueprint $table) {
            // Code pays ISO 3166-1 alpha-2 du téléphone (requis par Chariow).
            $table->string('phone_country', 2)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('chariow_product_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('phone_country'));
    }
};
