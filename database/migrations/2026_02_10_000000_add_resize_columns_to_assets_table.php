<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('resize_s_s3_key')->nullable()->after('thumbnail_s3_key');
            $table->string('resize_m_s3_key')->nullable()->after('resize_s_s3_key');
            $table->string('resize_l_s3_key')->nullable()->after('resize_m_s3_key');
        });

        // Seed resize dimension settings
        $settings = [
            ['key' => 'resize_s_width', 'value' => '250', 'type' => 'integer', 'group' => 'general', 'description' => 'Small resize preset width (px)'],
            ['key' => 'resize_s_height', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Small resize preset height (px), empty for auto'],
            ['key' => 'resize_m_width', 'value' => '600', 'type' => 'integer', 'group' => 'general', 'description' => 'Medium resize preset width (px)'],
            ['key' => 'resize_m_height', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Medium resize preset height (px), empty for auto'],
            ['key' => 'resize_l_width', 'value' => '1200', 'type' => 'integer', 'group' => 'general', 'description' => 'Large resize preset width (px)'],
            ['key' => 'resize_l_height', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Large resize preset height (px), empty for auto'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['resize_s_s3_key', 'resize_m_s3_key', 'resize_l_s3_key']);
        });

        Setting::whereIn('key', [
            'resize_s_width', 'resize_s_height',
            'resize_m_width', 'resize_m_height',
            'resize_l_width', 'resize_l_height',
        ])->delete();
    }
};
