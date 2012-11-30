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
 * Resource module upgrade related helper functions
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/qv/locallib.php');

/**
 * Migrate qv files to new area if found
 * @TODO: Test to check all qv files (and not only 1) is migrated
 * 
 * @return
 */
function qv_migrate_files() {
    global $CFG, $DB;
    
    $fs = get_file_storage();
    $sqlfrom = "FROM {qv} q
                JOIN {modules} m ON m.name = 'qv'
                JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = q.id)";
    $count = $DB->count_records_sql("SELECT COUNT('x') $sqlfrom");
    $rs = $DB->get_recordset_sql("SELECT q.id, q.assessmenturl, q.course, cm.id AS cmid $sqlfrom ORDER BY q.course, q.id");
    if ($rs->valid()) {
        $pbar = new progress_bar('migrateqvfiles', 500, true);
        $i = 0;
        foreach ($rs as $qv) {
            $i++;
            upgrade_set_timeout(180); // set up timeout, may also abort execution
            $pbar->update($i, $count, "Migrating qv files - $i/$count.");

            $context       = get_context_instance(CONTEXT_MODULE, $qv->cmid);
            $coursecontext = get_context_instance(CONTEXT_COURSE, $qv->course);

            if (!qv_is_valid_external_url($qv->assessmenturl)) {
                // first copy local files if found - do not delete in case they are shared ;-)
                $qvfile = clean_param($qv->assessmenturl, PARAM_PATH);
                $pathnamehash = sha1("/$coursecontext->id/course/legacy/0/$qvfile");
                if ($file = $fs->get_file_by_hash($pathnamehash)) {
                    $file_record = array('contextid'=>$context->id, 'component'=>'mod_qv', 'filearea'=>'content',
                                         'itemid'=>0, 'filepath'=>'/');
                    try {
                        $fs->create_file_from_storedfile($file_record, $file);
                    } catch (Exception $x) {
                        // ignore any errors, we can not do much anyway
                    }
                    $qv->assessmenturl = $pathnamehash;
                } else {
                    $qv->assessmenturl = '';
                }
                $DB->update_record('qv', $qv);
            }
        }
    }
    $rs->close();
}