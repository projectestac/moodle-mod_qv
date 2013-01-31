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
 * Prints a particular instance of qv
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/completionlib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // qv instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('qv', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $qv         = $DB->get_record('qv', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $qv         = $DB->get_record('qv', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $qv->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('qv', $qv->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qv:view', $context);

add_to_log($course->id, 'qv', 'view', "view.php?id={$cm->id}", $qv->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/qv/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($qv->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);


// Mark viewed if required
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

qv_view_header($qv, $cm, $course);
qv_view_intro($qv, $cm);

$action = optional_param('action', '', PARAM_TEXT);
if (has_capability('mod/qv:grade', $context, $USER->id, true)){    
    if ($action == 'preview'){
        qv_view_applet($qv, $context, true);
    } else{
        qv_view_dates($qv, $cm);
        qv_print_results_table($qv, $context, $cm, $course, $action);
    }
    
} else{
    qv_view_assessment($qv, $USER, $context, $cm, $course);    
}

echo $OUTPUT->footer();
