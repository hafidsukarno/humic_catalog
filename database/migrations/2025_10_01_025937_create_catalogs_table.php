<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('file_link', 500);
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};
