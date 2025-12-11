<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_quizgenerator.
 *
 * @package     mod_quizgenerator
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute quizgenerator upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_quizgenerator_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For future upgrades, add upgrade steps here.
    // Example:
    // if ($oldversion < 2025022101) {
    //     // Add new field to table
    //     $table = new xmldb_table('quizgenerator');
    //     $field = new xmldb_field('newfield', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    //
    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }
    //
    //     upgrade_mod_savepoint(true, 2025022101, 'quizgenerator');
    // }

    return true;
}