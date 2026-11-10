
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('old_status')->nullable();
            $table->string('new_status');

            $table->text('comment')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['service_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
