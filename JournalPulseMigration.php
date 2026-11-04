<?php

/**
 * @file plugins/themes/journalPulse/JournalPulseMigration.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalPulseMigration
 *
 * @brief Moves journals over from the theme's former name: copies the theme
 *  options of the "journalMetrics" theme and makes every journal (and the
 *  site) that used it use journalPulse. OJS runs it on install and on every
 *  upgrade, so it only copies what is not there yet.
 */

namespace APP\plugins\themes\journalPulse;

use APP\core\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class JournalPulseMigration extends Migration
{
    /** Plugin name (settings) and folder (theme path) before the rename */
    public const FORMER_NAME = 'journalmetricsthemeplugin';
    public const FORMER_PATH = 'journalMetrics';

    public function up(): void
    {
        DB::table('plugin_settings')->insertUsing(
            ['plugin_name', 'context_id', 'setting_name', 'setting_value', 'setting_type'],
            DB::table('plugin_settings as o')
                ->select(DB::raw("'journalpulsethemeplugin'"), 'o.context_id', 'o.setting_name', 'o.setting_value', 'o.setting_type')
                ->where('o.plugin_name', self::FORMER_NAME)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('plugin_settings as n')
                    ->where('n.plugin_name', 'journalpulsethemeplugin')
                    ->whereColumn('n.setting_name', 'o.setting_name')
                    // site-wide settings have no context (NULL), and NULL = NULL is not true
                    ->where(fn ($q) => $q->whereColumn('n.context_id', 'o.context_id')
                        ->orWhere(fn ($q) => $q->whereNull('n.context_id')->whereNull('o.context_id'))))
        );

        foreach ([Application::getContextDAO()->settingsTableName, 'site_settings'] as $table) {
            DB::table($table)
                ->where('setting_name', 'themePluginPath')
                ->where('setting_value', self::FORMER_PATH)
                ->update(['setting_value' => 'journalPulse']);
        }
    }
}
