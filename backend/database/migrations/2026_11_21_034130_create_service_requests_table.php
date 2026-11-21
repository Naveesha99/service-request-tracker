<?php

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 100);
            $table->text('description');
            $table->enum('priority', array_column(ServiceRequestPriority::cases(), 'value'));
            $table->enum('status', array_column(ServiceRequestStatus::cases(), 'value'))->default(ServiceRequestStatus::OPEN->value);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('created_by');
            $table->index(['created_by', 'created_at']);
            $table->index(['created_by', 'status']);
            $table->index(['created_by', 'priority']);
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
CREATE TRIGGER service_requests_title_description_insert_check
BEFORE INSERT ON service_requests
BEGIN
    SELECT CASE WHEN length(new.title) < 3 OR length(new.title) > 100
        THEN RAISE(ABORT, 'title must be between 3 and 100 characters') END;
    SELECT CASE WHEN length(new.description) < 10 OR length(new.description) > 500
        THEN RAISE(ABORT, 'description must be between 10 and 500 characters') END;
END
SQL);

            DB::statement(<<<'SQL'
CREATE TRIGGER service_requests_title_description_update_check
BEFORE UPDATE OF title, description ON service_requests
BEGIN
    SELECT CASE WHEN length(new.title) < 3 OR length(new.title) > 100
        THEN RAISE(ABORT, 'title must be between 3 and 100 characters') END;
    SELECT CASE WHEN length(new.description) < 10 OR length(new.description) > 500
        THEN RAISE(ABORT, 'description must be between 10 and 500 characters') END;
END
SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS service_requests_title_description_update_check');
            DB::statement('DROP TRIGGER IF EXISTS service_requests_title_description_insert_check');
        }

        Schema::dropIfExists('service_requests');
    }
};
