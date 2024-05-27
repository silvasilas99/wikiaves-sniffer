<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("sniffer_exportation_records", function (Blueprint $table) {
            $table->id();
            $table->json("items_data")->nullable(true);
            $table->integer("items_amount")->nullable(false);
            $table->string("title")->nullable(false);
            $table->string("exportation_database_file_path")->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sniffer_exportation_records");
    }
};
