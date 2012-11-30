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
 * The main qv configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once('locallib.php');

/**
 * Module instance settings form
 */
class mod_qv_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();
        
        
        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'qv'), array('optional'=>true));
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'qv'), array('optional'=>true));
        
        //-------------------------------------------------------------------------------
        // Adding the rest of qv settings, spreeading all them into this fieldset
        $mform->addElement('header', 'header_qv', get_string('header_qv', 'qv'));

        $mform->addElement('select', 'filetype', get_string('filetype', 'qv'), qv_get_file_types());
        $mform->addHelpButton('filetype', 'filetype', 'qv');
        $mform->addElement('text', 'qvurl', get_string('qvurl', 'qv'), array('size'=>60));
        $mform->setType('qvurl', PARAM_RAW);
        $mform->addHelpButton('qvurl', 'qvurl', 'qv');
        $mform->disabledIf('qvurl', 'filetype', 'eq', QV_FILE_TYPE_LOCAL);
        
        $mform->addElement('filemanager', 'qvfile', get_string('qvfile', 'qv'), array('optional'=>false), qv_get_filemanager_options());   
        $mform->addHelpButton('qvfile', 'urledit', 'qv');
        $mform->disabledIf('qvfile', 'filetype', 'noteq', QV_FILE_TYPE_LOCAL);
        
        $options = qv_get_languages();
        $mform->addElement('select', 'assessmentlang', get_string('assessmentlang', 'qv'), $options);
        $mform->setDefault('assessmentlang', substr($CFG->lang, 0, -5));

        $options = qv_get_skins();
        $mform->addElement('select', 'assessmentskin', get_string('assessmentskin', 'qv'), $options);

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'header_score', get_string('header_score', 'qv'));

        $options = array(-1 => get_string('unlimited','qv'), 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 10 => 10);
        $mform->addElement('select', 'maxdeliver', get_string('assessmentmaxdeliver', 'qv'), $options);
        $mform->setDefault('maxdeliver', '-1');
        
        $mform->addElement('selectyesno', 'showcorrection', get_string('showcorrection', 'qv'));
        $mform->setDefault('showcorrection', '1');
        
        $mform->addElement('selectyesno', 'showinteraction', get_string('showinteraction', 'qv'));
        $mform->setDefault('showinteraction', '1');
        
        $mform->addElement('selectyesno', 'ordersections', get_string('ordersections', 'qv')); //Albert
        $mform->addHelpButton('ordersections', 'ordersections', 'qv');
        $mform->setDefault('ordersections', '1');
        
        $mform->addElement('selectyesno', 'orderitems', get_string('orderitems', 'qv')); //Albert
        $mform->addHelpButton('orderitems', 'orderitems', 'qv');
        $mform->setDefault('orderitems', '1');

        //-------------------------------------------------------------------------------

        $this->standard_grading_coursemodule_elements();
        
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'header_target', get_string('header_target', 'qv'));
        
        $options = array('self' => get_string("targetself","qv"), 'blank' => get_string("targetblank","qv"));
        $mform->addElement('select', 'target', get_string('target', 'qv'), $options);
        $mform->setDefault('target', 'blank');
        
        $mform->addElement('text', 'width', get_string('width', 'qv'), array('size'=>'5'));
        $mform->setDefault('width', '100%');
        
        $mform->addElement('text', 'height', get_string('height', 'qv'), array('size'=>'5'));
        $mform->setDefault('height', '400');
        
        
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
    
    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('qvfile');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_qv', 'content', 0, qv_get_filemanager_options());
            $default_values['qvfile'] = $draftitemid;
        } 
    }
    
    public function validation($data, $files) {
        global $USER; 
        
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeavailable'] != 0 && $data['timedue'] != 0 &&
            $data['timedue'] < $data['timeavailable']) {
            $errors['timedue'] = get_string('closebeforeopen', 'qv');
        }
        
        $type = $data['filetype'];
        if ($type === QV_FILE_TYPE_LOCAL) {
            $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
            $fs = get_file_storage();
            if (!$files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['qvfile'], 'sortorder, id', false)) {
                $errors['qvfile'] = get_string('required');
            } else{
                $file = reset($files);
                $filename = $file->get_filename();
                if (!qv_is_valid_file($filename)){
                    $errors['qvfile'] = get_string('invalidqvfile', 'qv');
                }
            }
        } else if ($type === QV_FILE_TYPE_EXTERNAL) {
            $reference = $data['qvurl'];
            if (!qv_is_valid_external_url($reference)) {
                $errors['qvurl'] = get_string('invalidurl', 'qv');
            }
        }

        return $errors;
    }    
    
    // Need to translate the "url" field
    function set_data($default_values) {
        $default_values = (array)$default_values;

        if (isset($default_values['assessmenturl'])) {
            if (qv_is_valid_external_url($default_values['assessmenturl'])) {
                $default_values['filetype'] = QV_FILE_TYPE_EXTERNAL;
                $default_values['qvurl'] = $default_values['assessmenturl'];
            } else{
                $default_values['filetype'] = QV_FILE_TYPE_LOCAL;
                $default_values['qvfile'] = $default_values['assessmenturl'];            
            }
        }
        unset($default_values['assessmenturl']);
        
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }
    
    function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }
    
    
}
