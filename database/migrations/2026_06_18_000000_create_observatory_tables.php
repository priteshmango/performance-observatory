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
        $connection = config('observatory.storage.connection');

        Schema::connection($connection)->create('observatory_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('url')->index();
            $table->string('method', 10);
            $table->float('total_duration')->index();
            $table->json('metrics_payload'); // Store everything as JSON for now
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('observatory.storage.connection');
        Schema::connection($connection)->dropIfExists('observatory_requests');
    }
};
