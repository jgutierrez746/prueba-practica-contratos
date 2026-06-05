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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            // Relación con el usuario logueado. Si el usuario se borra, se borran sus contratos.
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            $table->string('contract_name');       // Nombre descriptivo dado por el usuario
            $table->string('original_filename');   // Nombre real del archivo .pdf
            $table->string('stored_path');         // Ruta interna del archivo físico en el storage
            $table->string('file_size');           // Peso legible ya formateado (ej: 1.5 MB)
            $table->string('status')->default('Procesado'); 
            $table->timestamps();                  // created_at y updated_at automáticos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};