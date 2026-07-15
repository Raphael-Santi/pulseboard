<?php

declare(strict_types=1);

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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('target')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->unsignedInteger('interval_sec')->default(60);
            $table->unsignedInteger('timeout_sec')->default(10);
            $table->unsignedTinyInteger('failure_threshold')->default(3);
            $table->boolean('is_paused')->default(false);
            $table->string('token', 64)->nullable()->unique();
            $table->unsignedInteger('grace_sec')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
