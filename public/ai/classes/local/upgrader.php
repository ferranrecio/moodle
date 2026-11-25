<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core_ai\local;

/**
 * AI subsystem upgrade helpers.
 *
 * @package    core_ai
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upgrader {
    /**
     * Migrate action names from basenames to fully qualified class names.
     *
     * @param array|null $pluginactions Optional plugin action map, keyed by component.
     */
    public static function migrate_action_names(?array $pluginactions = null): void {
        global $DB;

        $migrateregisterrows = static function (
            string $basename,
            string $classname,
            ?string $provider = null,
        ) use ($DB): void {
            $select = 'actionname = :legacyactionname';
            $params = ['legacyactionname' => $basename];
            if ($provider !== null) {
                $select .= ' AND provider = :provider';
                $params['provider'] = $provider;
            }
            $DB->set_field_select('ai_action_register', 'actionname', $classname, $select, $params);
        };

        $legacyactionclasses = [
            'generate_text' => \core_ai\aiactions\generate_text::class,
            'generate_image' => \core_ai\aiactions\generate_image::class,
            'summarise_text' => \core_ai\aiactions\summarise_text::class,
            'explain_text' => \core_ai\aiactions\explain_text::class,
        ];

        if ($pluginactions === null) {
            $pluginactions = [];
            foreach (['aiprovider', 'aiplacement'] as $plugintype) {
                $subclassname = $plugintype === 'aiprovider' ? 'provider' : 'placement';
                foreach (\core_plugin_manager::instance()->get_plugins_of_type($plugintype) as $plugin) {
                    $component = "{$plugintype}_{$plugin->name}";
                    $classname = "\\{$component}\\{$subclassname}";
                    if (!class_exists($classname) || !method_exists($classname, 'get_action_list')) {
                        continue;
                    }
                    foreach ($classname::get_action_list() as $actionclass) {
                        $actionclass = ltrim($actionclass, '\\');
                        if (!is_a($actionclass, \core_ai\aiactions\base::class, true)) {
                            continue;
                        }
                        $pluginactions[$component][$actionclass] = $actionclass;
                    }
                }
            }
        }

        $groupbybasename = static function (array $classnames): array {
            $result = [];
            foreach ($classnames as $classname) {
                $basename = substr($classname, (strrpos($classname, '\\') + 1));
                $result[$basename][$classname] = $classname;
            }
            return $result;
        };
        $pluginactionsbybasename = [];
        foreach ($pluginactions as $component => $classnames) {
            $pluginactionsbybasename[$component] = $groupbybasename($classnames);
        }

        foreach ($pluginactionsbybasename as $component => $pluginbasenamemap) {
            if (!str_starts_with($component, 'aiprovider_')) {
                continue;
            }
            foreach ($pluginbasenamemap as $basename => $classnames) {
                if (count($classnames) === 1) {
                    $migrateregisterrows($basename, reset($classnames), $component);
                }
            }
        }

        foreach ($legacyactionclasses as $basename => $classname) {
            $migrateregisterrows($basename, $classname);
        }

        foreach ($pluginactionsbybasename as $component => $pluginbasenamemap) {
            if (!str_starts_with($component, 'aiplacement_')) {
                continue;
            }
            $enabledactions = get_config($component, 'enabledactions');
            $enabledactions = $enabledactions ? (array) json_decode($enabledactions, true) : [];
            $legacykeys = [];
            foreach ($pluginbasenamemap as $legacykey => $classnames) {
                $legacyvalue = get_config($component, $legacykey);
                if ($legacyvalue === false) {
                    continue;
                }
                foreach ($classnames as $actionclass) {
                    if (!array_key_exists($actionclass, $enabledactions)) {
                        $enabledactions[$actionclass] = (int) $legacyvalue;
                    }
                }
                $legacykeys[] = $legacykey;
            }
            if ($legacykeys) {
                set_config('enabledactions', json_encode($enabledactions), $component);
                foreach ($legacykeys as $legacykey) {
                    unset_config($legacykey, $component);
                }
            }
        }

        $modules = $DB->get_recordset_select(
            'course_modules',
            'enabledaiactions IS NOT NULL',
            [],
            '',
            'id, enabledaiactions',
        );
        foreach ($modules as $module) {
            $enabledactions = json_decode($module->enabledaiactions, true);
            if (!is_array($enabledactions)) {
                continue;
            }
            $newenabledactions = $enabledactions;
            $changed = false;
            foreach ($enabledactions as $actionname => $enabled) {
                if (str_contains($actionname, '\\')) {
                    continue;
                }
                $actionclass = "core_ai\\aiactions\\{$actionname}";
                if (!array_key_exists($actionclass, $newenabledactions)) {
                    $newenabledactions[$actionclass] = $enabled;
                }
                unset($newenabledactions[$actionname]);
                $changed = true;
            }
            if ($changed) {
                $DB->set_field(
                    'course_modules',
                    'enabledaiactions',
                    json_encode($newenabledactions),
                    ['id' => $module->id],
                );
            }
        }
        $modules->close();
    }
}
