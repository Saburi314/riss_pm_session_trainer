<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Enums are strictly checked by some DB engines.
        // For MySQL, we can use DB::statement or the fluent syntax if supported.
        // Adding 'special' to the enum.
        DB::statement("ALTER TABLE pdf_files MODIFY COLUMN season ENUM('spring', 'autumn', 'special') NOT NULL");
    }

    public function down(): void
    {
        // To revert, we'd need to handle records with 'special' season.
        // For now, mapping back to the original enum.
        DB::statement("ALTER TABLE pdf_files MODIFY COLUMN season ENUM('spring', 'autumn') NOT NULL");
    }
};
