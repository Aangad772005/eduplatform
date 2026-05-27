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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable(); // code editor, quiz options selected, or short answer text
            $table->string('file_path')->nullable(); // path to uploaded file if any
            $table->string('status')->default('submitted'); // submitted, graded, late
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('auto_graded_score', 8, 2)->nullable();
            $table->text('auto_graded_feedback')->nullable();
            $table->text('feedback')->nullable(); // teacher feedback
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            // Allow only one submission per student per assignment
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
