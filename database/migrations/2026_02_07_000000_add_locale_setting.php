<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'locale'],
            [
                'value' => 'en',
                'type' => 'string',
                'group' => 'display',
                'description' => 'Application UI language',
            ]
        );
    }

    public function down(): void
    {
        Setting::where('key', 'locale')->delete();
    }
};
