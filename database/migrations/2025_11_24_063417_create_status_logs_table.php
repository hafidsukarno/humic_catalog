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
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('action');
            $table->string('status_message')->nullable();

            // ===== Tambahan kolom snapshot (tanpa after) =====
            $table->string('product_title')->nullable();
            $table->string('partner_name')->nullable();

            $table->timestamps();

            // ===== Foreign keys =====
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['partner_id']);
        });

        Schema::dropIfExists('status_logs');
    }
};
