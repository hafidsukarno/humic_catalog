<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->unique();
            $table->string('slug', 255)->unique(); 
            $table->string('subtitle', 255)->nullable();
            $table->enum('category', ['Research Project', 'Internship Project']);
            $table->text('description')->nullable();

            // kolom untuk menyimpan file utama (pdf, dokumen, dsb)
            $table->string('file_path', 500)->nullable();

            // kolom untuk menyimpan thumbnail / poster produk
            $table->string('thumbnail_path', 500)->nullable();

            // relasi ke tabel admins
            $table->foreignId('admin_id')
                  ->constrained('admins')
                  ->onDelete('cascade');

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
