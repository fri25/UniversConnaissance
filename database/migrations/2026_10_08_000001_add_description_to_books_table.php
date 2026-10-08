<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Description mise en forme (HTML nettoyé) saisie avec l'éditeur de l'admin.
            $table->longText('description')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('description'));
    }
};
