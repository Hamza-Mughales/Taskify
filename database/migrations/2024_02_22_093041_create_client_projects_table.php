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
        Schema::create('client_projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            // Unique key
            $table->unique(['project_id', 'client_id'], 'project_client_project_id_client_id_unique');

            // Indexes
            $table->index('client_id', 'project_client_client_id_foreign');
            $table->index('admin_id', 'client_project_admin_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_projects');
    }
};
