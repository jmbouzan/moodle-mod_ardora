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

/**
 * ardora module upgrade code
 *
 * This file keeps track of upgrades to
 * the ardora module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 * created from the "Resource module" version created by 2009 Petr Skoda  {@link http://skodak.org}
 * @package    mod_ardora
 * @copyright  2026 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades the Ardora module to the specified version.
 *
 * This function is responsible for upgrading the database schema of the Ardora module
 * when the version is incremented. It performs the necessary changes to the database
 * structure or data when the module is updated.
 *
 * @param int $oldversion The previous version of the module.
 * @return bool True on success, false on failure.
 */
function xmldb_ardora_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.
    if ($oldversion < 2026030700) {
        // UPDATE V.2 PLUGIN Páginas en servidor
        // Define table ardora_server_pages to be created.
        $table = new xmldb_table('ardora_server_pages');

        // Adding fields to table ardora_server_pages.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ardora_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('father', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('field01', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field02', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field03', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field04', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field05', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field06', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field07', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field08', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field09', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field10', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field11', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field12', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field13', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field14', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('field15', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table ardora_server_pages.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table ardora_server_pages.
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('ardora_id', XMLDB_INDEX_NOTUNIQUE, ['ardora_id']);

        // Conditionally launch create table for ardora_server_pages.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ardora savepoint reached.
        upgrade_mod_savepoint(true, 2026030700, 'ardora');
    }

    if ($oldversion < 2026031300) {
        $table = new xmldb_table('ardora_server_pages');

        for ($i = 16; $i <= 30; $i++) {
            $fieldname = 'field' . $i;
            $field = new xmldb_field($fieldname, XMLDB_TYPE_TEXT, null, null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026031300, 'ardora');
    }

    if ($oldversion < 2026041800) {
        $table = new xmldb_table('ardora_server_pages');
        $field = new xmldb_field('folder', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'father');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026041800, 'ardora');
    }

    return true;
}
