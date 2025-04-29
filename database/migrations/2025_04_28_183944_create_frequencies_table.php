<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('frequencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            // date de début de la première occurrence
            $table->dateTime('start_date')->comment('Date de début de la première occurrence');

            // date de fin si on choisit "jusqu’à"
            $table->dateTime('end_date')->nullable()->comment('Date de fin de la dernière occurrence');

            // ─── Choix du type de fin ───
            // never        = jamais
            // until_date   = jusqu’à end_at
            // occurrences  = après X occurrences
            $table
                ->enum('end_type', ['never', 'until_date', 'occurrences'])
                ->default('never')
                ->comment("Type de fin : never=jamais, until_date=jusqu'à une date, occurrences=après X occurrences");

            // nombre d'occurrences max si end_type = 'occurrences'
            $table->unsignedInteger('occurrence_count')->nullable()->comment("Nombre max d'occurrences si end_type = 'occurrences'");

            // intervalle & unité
            $table->unsignedSmallInteger('interval')->default(1)->comment('Tous les X ‹unit›');
            $table->enum('unit', ['day', 'week', 'month', 'year'])->default('day');

            // pour les répétitions par semaine : [1..7] (1 = lun, 7 = dim)
            $table->json('weekdays')->nullable()->comment('Jours de la semaine pour unit=week');

            // pour les répétitions par jours fixes du mois : [1..31]
            $table->json('month_days')->nullable()->comment('Jours fixes du mois pour unit=month');

            // pour les ordinales dans le mois :
            // tableau d’objets { ordinal: int (1,2,3,4,-1), weekday: 1..7 }
            // ex. [ {"ordinal":1,"weekday":2}, {"ordinal":-1,"weekday":7} ]
            $table->json('month_occurrences')->nullable()->comment('Occurrences ordinales du mois (1er, dernier…)');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequencies');
    }
};
