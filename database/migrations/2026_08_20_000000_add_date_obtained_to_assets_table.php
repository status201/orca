<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds assets.date_obtained — when the asset was purchased or otherwise obtained.
 *
 * This replaces license_expiry_date in the UI, not in the schema. The policy is now
 * to never acquire assets whose licence expires, so the expiry input leaves the edit
 * form (see specs/features/asset-model.md); the column, its REST-API field and its
 * CSV column deliberately stay so stored values survive and integrations keep
 * working. Nothing is dropped here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('date_obtained')->nullable()->after('license_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('date_obtained');
        });
    }
};
