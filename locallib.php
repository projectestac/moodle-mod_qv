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
 * Internal library of functions for module qv
 *
 * All the qv specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");

class qv_package_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}


    /**
    * Get an array with the languages
    *
    * @return array   The array with each language.
    */
    function qv_get_languages(){
        $tmplanglist = get_string_manager()->get_list_of_translations();
        $langlist = array();
        foreach ($tmplanglist as $lang=>$langname) {
            if (substr($lang, -5) == '_utf8') {   //Remove the _utf8 suffix from the lang to show
                $lang = substr($lang, 0, -5);
            }
            $langlist[$lang]=$langname;
        }
        return $langlist;
    }

    /**
    * Get an array with the skins
    *
    * @return array   The array with each skin.
    */
    function qv_get_skins(){
      global $CFG;
      
      return explode(',', $CFG->qv_skins);
    } 

    /**
    * Get an array with the file types
    *
    * @return array   The array with each file type
    */
    function qv_get_file_types(){
        $filetypes =  array(QV_FILE_TYPE_LOCAL => get_string('filetypelocal', 'qv'));
        $filetypes[QV_FILE_TYPE_EXTERNAL] = get_string('filetypeexternal', 'qv');
        return $filetypes;
    }    

    /**
     * Display the header and top of a page
     *
     * This is used by the view() method to print the header of view.php but
     * it can be used on other pages in which case the string to denote the
     * page in the navigation trail should be passed as an argument
     *
     * @global object
     * @param string $subpage Description of subpage to be used in navigation trail
     */
    function qv_view_header($qv, $cm, $course, $subpage='') {
        global $CFG, $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        //$PAGE->set_title($qv->name);
        //$PAGE->set_heading($course->fullname);

        echo $OUTPUT->header();

        groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/qv/view.php?id=' . $cm->id);

        echo  $OUTPUT->box(qv_submittedlink(),'reportlink clearfix');
    }


    /**
     * Display the qv intro
     *
     */
    function qv_view_intro($qv, $cm) {
        global $OUTPUT;
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        echo format_module_intro('qv', $qv, $cm->id);
        echo $OUTPUT->box_end();
    }

    /**
     * Display the qv dates
     *
     * Prints the qv start and end dates in a box.
     */
    function qv_view_dates($qv, $cm, $timenow=null) {
        global $OUTPUT;
        
        if (!$qv->timeavailable && !$qv->timedue) {
            return;
        }
        
        if (is_null($timenow)) $timenow = time();

        echo $OUTPUT->box_start('generalbox boxaligncenter qvdates', 'dates');
        if ($qv->timeavailable) {
            echo '<div class="title-time">'.get_string('availabledate','assignment').': </div>';
            echo '<div class="data-time">'.userdate($qv->timeavailable).'</div>';
        }
        if ($qv->timedue) {
            echo '<div class="title-time">'.get_string('duedate','assignment').': </div>';
            echo '<div class="data-time">'.userdate($qv->timedue).'</div>';
        }
        echo $OUTPUT->box_end();
    }
    
    /**
     * Display the qv applet
     *
     */
    function qv_view_applet($qv, $context, $ispreview=false, $timenow=null) {
        global $OUTPUT, $PAGE, $CFG, $USER;
        
        if (is_null($timenow)) $timenow = time();
        $isopen = (empty($qv->timeavailable) || $qv->timeavailable < $timenow);
        $isclosed = (!empty($qv->timedue) && $qv->timedue < $timenow);
        $sessions = qv_get_sessions($qv->id,$USER->id);
        $attempts=sizeof($sessions);
        if ($ispreview) {
            $url = new moodle_url('/mod/qv/view.php', array('id' => $context->instanceid));
            echo '<br><A href="'.$url.'"  >'.get_string('return_results', 'qv').'</A>';
        } else if ( $attempts > 0 || $isopen ) {
            echo '<br><A href="#" onclick="window.open(\'action/student_results.php?id='.$context->instanceid.'\',\'JClic\',\'navigation=0,toolbar=0,resizable=1,scrollbars=1,width=700,height=400\');" >'.get_string('show_my_results', 'qv').'</A>';
        }
        
        if (!$ispreview && !$isopen){
            echo $OUTPUT->box(get_string('notopenyet', 'qv', userdate($qv->timeavailable)), 'generalbox boxaligncenter qvdates');
        } else if (!$ispreview && $isclosed ) {
            echo $OUTPUT->box(get_string('expired', 'qv', userdate($qv->timedue)), 'generalbox boxaligncenter qvdates'); 
        } else {
            if ($qv->maxattempts<0 || $attempts < $qv->maxattempts){
              echo '<div id="qv_applet" style="text-align:center;padding-top:10px;">';
              echo '</div>';
              $PAGE->requires->js('/mod/qv/qvplugin.js');
              $PAGE->requires->js('/mod/qv/qv.js');
              $params = get_object_vars($qv);
              $params['qv_url'] = qv_get_url($qv);
              $params['qv_path'] = qv_get_server();
              $params['qv_service'] = qv_get_path().'/mod/qv/action/beans.php';
              $params['qv_user'] = $USER->id;
              $params['qv_jarbase'] = $CFG->qv_jarbase;
              $params['qv_lap'] = $CFG->qv_lap;
              $params['qv_protocol'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
              $PAGE->requires->js_init_call('M.mod_qv.init', array($params));
            }else{
                echo $OUTPUT->box(get_string('msg_noattempts', 'qv'), 'generalbox boxaligncenter');
            }
            qv_view_dates($qv, $context, $timenow);            
        }
    }

	/**
	 * Extracts QV package, sets up all variables.
	 * Called whenever qv changes
	 * @param object $qv instance - fields are updated and changes saved into database
	 * @param bool $full force full update if true
	 * @return void
	 */
	function qv_extract_package($qv, $full = false){
		global $DB;


		if (!isset($qv->cmid)) {
			$cm = get_coursemodule_from_instance('qv', $qv->id);
			$qv->cmid = $cm->id;
		}
		$context = context_module::instance($qv->cmid);

		$fs = get_file_storage();
		$packagefile = false;

		if ($packagefile = $fs->get_file($context->id, 'mod_qv', 'package', 0, '/', $qv->reference)) {
			$newhash = $packagefile->get_contenthash();

			//If index.htm does not exists
			if(!$fs->get_file($context->id, 'mod_qv', 'content', 0, '/html/', 'index.htm'))
				$full = true;
				
			//Content out of date or updating on demand
			if($full || $newhash != $qv->sha1hash){
				// now extract files
				$fs->delete_area_files($context->id, 'mod_qv', 'content');

				$packer = get_file_packer('application/zip');
				$files = $packagefile->extract_to_storage($packer, $context->id, 'mod_qv', 'content', 0, '/');
				$qv->sha1hash = $newhash;
				$DB->update_record('qv', $qv);

				if(isset($files['html/index.htm'])){
					return true;
				}
			} else {
				return true;
			}
		}
		return false;
	}
    
    /**
     * Display the qv assessment
     *
     */
    function qv_view_assessment($qv, $user, $context, $cm, $course, $timenow=null) {
        global $OUTPUT, $PAGE, $CFG;

        if (is_null($timenow)) $timenow = time();
        $isopen = (empty($qv->timeavailable) || $qv->timeavailable < $timenow);
        $isclosed = (!empty($qv->timedue) && $qv->timedue < $timenow);
        if (!$isopen){
            echo $OUTPUT->box(get_string('notopenyet', 'qv', userdate($qv->timeavailable)), 'generalbox boxaligncenter qvdates');
        } else if ($isclosed ) {
            echo $OUTPUT->box(get_string('expired', 'qv', userdate($qv->timedue)), 'generalbox boxaligncenter qvdates'); 
        } else {
            // TODO: Where it's necessary to check maxattemps
            $assignment = qv_get_assignment($qv);
            $qv_full_url = qv_get_full_url($qv, $assignment, $user->id, "$user->firstname%20$user->lastname");
            if ($qv->target=='self'){
                $qvwidth=$qv->width!=''?$qv->width:'99%';
                $qvheight=$qv->height!=''?$qv->height:'400';
                echo "<iframe src='$qv_full_url' title='Quaderns Virtuals' width='$qvwidth' height='$qvheight'></iframe>";
            } else {
                $PAGE->requires->js('/mod/qv/qv.js');
                $fullscreen=$qv->width==''||$qv->width=='100%'||$qv->height==''?'true':'false';
                echo "<a href='#' onclick='openpopupName(\"$qv_full_url\",\"toolbar=no,status=no,scrollbars=yes,resizable=yes,width=$qv->width,height=$qv->height\",$fullscreen);'>";//Albert
                print_string("start", "qv");
                echo "</a><br/>";
            }
            qv_view_dates($qv, $context, $timenow);            
        }
                        
    }
    
    /**
     * Returns a link with info about the state of the qv attempts
     *
     * This is used by view_header to put this link at the top right of the page.
     * For teachers it gives the number of attempted qvs with a link
     * For students it gives the time of their last attempt.
     *
     * @global object
     * @global object
     * @param bool $allgroup print all groups info if user can access all groups, suitable for index.php
     * @return string
     */
    function qv_submittedlink($allgroups=false) {
        global $USER;
        global $CFG;

        $submitted = '';
        $urlbase = "{$CFG->wwwroot}/mod/qv/";

/*      $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        if (has_capability('mod/qv:grade', $context)) {
            if ($allgroups and has_capability('moodle/site:accessallgroups', $context)) {
                $group = 0;
            } else {
                $group = groups_get_activity_group($this->cm);
            }
            $submitted = 'teacher';
        } else {
            if (isloggedin()) {
                $submitted = 'student';
            }
        }
*/
        return $submitted;
    }


    /**
    * Get moodle server
    *
    * @return string                myserver.com:port
    */
    function qv_get_server() {
        global $CFG;

        if (!empty($CFG->wwwroot)) {
            $url = parse_url($CFG->wwwroot);
        }

        if (!empty($url['host'])) {
            $hostname = $url['host'];
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        } else if (!empty($_ENV['SERVER_NAME'])) {
            $hostname = $_ENV['SERVER_NAME'];
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $hostname = $_SERVER['HTTP_HOST'];
        } else if (!empty($_ENV['HTTP_HOST'])) {
            $hostname = $_ENV['HTTP_HOST'];
        } else {
            notify('Warning: could not find the name of this server!');
            return false;
        }

        if (!empty($url['port'])) {
            $hostname .= ':'.$url['port'];
        } else if (!empty($_SERVER['SERVER_PORT'])) {
            if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
                $hostname .= ':'.$_SERVER['SERVER_PORT'];
            }
        }

        return $hostname;
    }


    /**
    * Get moodle path
    *
    * @return string                /path_to_moodle
    */
    function qv_get_path() {
        global $CFG;

		$path = '/';
        if (!empty($CFG->wwwroot)) {
            $url = parse_url($CFG->wwwroot);
                    if (array_key_exists('path', $url)){
                            $path = $url['path'];			
                    }
        }
        return $path;
    }    
    
    function qv_get_filemanager_options(){
        $filemanager_options = array();
        $filemanager_options['return_types'] = 3;  // 3 == FILE_EXTERNAL & FILE_INTERNAL. These two constant names are defined in repository/lib.php
        $filemanager_options['accepted_types'] = 'archive';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['subdirs'] = 0;
        $filemanager_options['maxfiles'] = 1;
        return $filemanager_options;
    }

    function qv_set_mainfile($data) {
        $filename = null;
        $fs = get_file_storage();
        $cmid = $data->coursemodule;
        $draftitemid = $data->reference;

        $context = context_module::instance($cmid);
        if ($draftitemid) {
            file_save_draft_area_files($draftitemid, $context->id, 'mod_qv', 'package', 0, qv_get_filemanager_options());
        }
        
        $files = $fs->get_area_files($context->id, 'mod_qv', 'package', 0, 'sortorder', false);
        if (count($files) == 1) {
            // only one file attached, set it as main file automatically
            $file = reset($files);
            file_set_sortorder($context->id, 'mod_qv', 'package', 0, $file->get_filepath(), $file->get_filename(), 1);
            $filename = $file->get_filename();
        }
        return $filename;
    }
        
    function qv_is_valid_external_url($url){
        return preg_match('/(http:\/\/|https:\/\/|www).*\/*(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?$/i', $url);
    }
    
    
////////////////////////////////////////////////////////////////////////////////
// Activity sessions                                                          //
////////////////////////////////////////////////////////////////////////////////
    
    /**
    * Returns an object to represent a user assignment to an assessment.
    * If the ->id field is not set, then the object is written to the database.
    *
    * @return object           The assignment object.
    * @param object $qv        The assessment to get an assignment for.
    */
    function qv_get_assignment($qv) {
        global $USER, $CFG, $DB;
        
        if (!$assignment = $DB->get_record('qv_assignments', array('qvid'=>$qv->id, 'userid'=>$USER->id))){
            srand(time());
            $assignment = new stdClass();
            $assignment->qvid = $qv->id;
            $assignment->userid = $USER->id;
            $assignment->idnumber = '';
            $assignment->sectionorder = rand(1, 80);
            $assignment->itemorder = rand(1, 80);

            $assignment->id = $DB->insert_record('qv_assignments', $assignment);
            // TODO: update gradebook functions 
            //if (isset($assignment)) qv_update_gradebook(null, $qv);	
        }
        return $assignment;
    }
    
    /**
    * Get user assignment summary
    *
    * @return object	assignment object with state, score and deliver information
    * @param object $qvid    The assessment identifier.
    * @param object $userid  The userid identifier.
    */
    function qv_get_assignment_summary($qvid, $userid) {
        global $CFG, $DB;

        $states=array();
        $score=0;
        $pending_score=0;//A
        $attempts=0;
        $time="00:00:00";//Albert

        $assignment_summary = new stdClass();
        if ($qv_assignment = $DB->get_record('qv_assignments', array('qvid'=> $qvid, 'userid'=>$userid))){
            if ($qv_sections = $DB->get_records('qv_sections', array('assignmentid'=>$qv_assignment->id))){
                foreach($qv_sections as $qv_section){
                    $section_summary = qv_get_section_summary($qv_section);
                    $score += $section_summary->score;
                    $pending_score += $section_summary->pending_score;//A

                    $time = qv_add_time($time, $qv_section->time); //Albert

                    if ($section_summary->attempts > $attempts){
                        $attempts = $section_summary->attempts;
                    }
                    // States
                    if (!array_key_exists('state_'.$qv_section->state, $states)) $states['state_'.$qv_section->state]=0;
                    $states['state_'.$qv_section->state] = $states['state_'.$qv_section->state]+1;
                }
                // State
                $state = -1;
                if (array_key_exists('state_2', $states) && sizeOf($qv_sections)==$states['state_2']) $state=2;
                else if (array_key_exists('state_1', $states) && sizeOf($qv_sections)==$states['state_1']) $state=1;
                else if (array_key_exists('state_1', $states) && $states['state_1']>0) $state='1-';
                else if (array_key_exists('state_2', $states) && $states['state_2']>0) $state='2-';
                else if (array_key_exists('state_0', $states) && $states['state_0']>0) $state=0;
                $states['state'] = $state;
            }
            $assignment_summary->id = $qv_assignment->id;
            $assignment_summary->sectionorder = $qv_assignment->sectionorder;//Albert
            $assignment_summary->itemorder = $qv_assignment->itemorder;//Albert

        }
        $assignment_summary->score = $score;
        $assignment_summary->pending_score = $pending_score;//A
        $assignment_summary->attempts = $attempts;
        $assignment_summary->states = $states;
        $assignment_summary->time = $time;//Albert

        return $assignment_summary;
    }
    
    /**
    * Get user section assignment summary
    *
    * @return object	section object with state, score and attempts information.
    * @param object $qv_section    The section object to get summary information.
    */
    function qv_get_section_summary($qv_section){
      // Score
      $start=strpos($qv_section->scores, $qv_section->sectionid.'_score=');
      $section_summary = new stdClass();
      if ($start>=0){
        $start+=strlen($qv_section->sectionid.'_score=');
        $length=strpos(substr($qv_section->scores, $start+1), '#')+1;
        $section_summary->score=substr($qv_section->scores, $start, $length);
      }
      // Pending Score //A
      $start2=strpos($qv_section->pending_scores, $qv_section->sectionid.'_score=');
      if ($start2>=0){
        $start2+=strlen($qv_section->sectionid.'_score=');
        $length2=strpos(substr($qv_section->pending_scores, $start2+1), '#')+1;
        $section_summary->pending_score=substr($qv_section->pending_scores, $start2, $length2);
      }

      // Attempts
      $section_summary->attempts=$qv_section->attempts;
      return $section_summary;
    }

	function qv_get_url($qv){
        global $CFG, $DB;
        
        $url = '';
        if ($qv->sha1hash == QV_HASH_ONLINE) {
            $url = $qv->reference;
        } else {
			if(qv_extract_package($qv,true)){
				$cm = get_coursemodule_from_instance('qv', $qv->id);
				$qv->cmid = $cm->id;
				$context = context_module::instance($cm->id);
				$fs = get_file_storage();
				$indexfile = $fs->get_file($context->id, 'mod_qv', 'content', 0, '/html/', 'index.htm');
				if($indexfile){
					$path = '/'.$context->id.'/mod_qv/content/0'.$indexfile->get_filepath().$indexfile->get_filename();
					$url = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
				}
			} else {
				return false;
			}
        }
        return $url;
    }
    
    /**
    * Compose the full assessment url
    *
    * @return string	full url composed with specified params
     * 
    * @param object $course object with the course information
    * @param object $qv object with the assessment information
    * @param object $assignment object with the assignment information
    * @param object $userid user identifier
    * @param object $fullname full name of the user
    * @param object $isteacher true if is teacher
    */
    function qv_get_full_url($qv, $assignment, $userid, $fullname,$isteacher=false){
		global $CFG;

		$qv_url = qv_get_url($qv);

		$params = "";
		/*
		$qv_file = $qv_url;
		$last=strrpos($qv_file, "/html/");
		if ($last<strlen($qv_file)){
			$base_file=substr($qv_file, 0, $last+1);
			if (!(qv_exists_url($base_file."html/appl/qv_local.jar"))) $params.="&appl=$CFG->qv_qvdistplugin_appl";
			if (!(qv_exists_url($base_file."html/css/generic.css"))) $params.="&css=$CFG->qv_qvdistplugin_css";
			if (!(qv_exists_url($base_file."html/scripts/qv_local.js"))) $params.="&js=$CFG->qv_qvdistplugin_scripts";
		}*/
		if (isset($assignment->id)){
			$qv_url.= (strpos($qv_url, "?") === false) ? '?' : '&';
			$params = "server=".$CFG->wwwroot."/mod/qv/action/beans.php&assignmentid=$assignment->id&userid=$userid&fullname=$fullname&skin=$qv->skin&lang=$qv->assessmentlang&showinteraction=$qv->showinteraction&showcorrection=$qv->showcorrection&order_sections=$qv->ordersections&order_items=$qv->orderitems".($isteacher?"&userview=teacher":"").(($assignment->sectionorder>0 && $qv->ordersections==1)?"&section_order=$assignment->sectionorder":"").(($assignment->itemorder>0 && $qv->orderitems==1)?"&item_order=$assignment->itemorder":"").$params;
			$fullurl = $qv_url.$params;
			return $fullurl;
		}
		return "";
    }    

    /**
    * Get user sessions
    *
    * @return array			[0=>session1,1=>session2...] where session1 is an array with keys: id,score,totaltime,starttime,done,solved,attempts. First sessions are newest.
    * @param object $qvid	The qv to get sessions
    * @param object $userid		The user id to get sessions
    */
    function qv_get_sessions($qvid, $userid) {
        global $CFG, $DB;
        
        $sessions=array();
        qv_normalize_date();
        $sql = "SELECT js.*
                FROM {qv} j, {qv_sessions} js 
                WHERE j.id=js.qvid AND js.qvid=? AND js.user_id=?
                ORDER BY js.session_datetime";
        $params = array($qvid, $userid);
        
        if($rs = $DB->get_records_sql($sql, $params)){
            $i = 0;
            foreach($rs as $session){
                    $activity = qv_get_activity($session);
                    $activity->attempts=$i+1;
                    $sessions[$i++]=$activity;
            }
        }
        return $sessions;
    }    
    
    /**
    * Get session activities
    *
    * @return array			[0=>act0,1=>act1...] where act0 is an array with keys: activity_id,activity_name,num_actions,score,activity_solved,qualification, total_time. First activity are oldest.
    * @param string $session_id		The session id to get actitivies
    */
    function qv_get_activities($session_id) {
        global $CFG, $DB;
        
        $activities = array();
        if($rs = $DB->get_records('qv_activities', array('session_id'=>$session_id), 'activity_id')){
            $i=0;
            foreach($rs as $activity){
                $activities[$i++]=$activity;
            }
        }
        return $activities;
    }
    
    
    /**
    * Get information about activities of specified session
    *
    * @return array		Array has these keys id,score,totaltime,starttime,done,solved,attempts
    * @param object $session	The session object
    */
    function qv_get_activity($session) {
        global $CFG, $DB;

        $activity = new stdClass();
        $activity->starttime=$session->session_datetime;
        $activity->session_id=$session->session_id;
        if($rs = $DB->get_record_sql("SELECT AVG(ja.qualification) as qualification, SUM(ja.total_time) as totaltime
                                 FROM {qv_activities} ja 
                                 WHERE ja.session_id='$session->session_id'")){
                $activity->score = round($rs->qualification,0);                
                $activity->totaltime = qv_format_time($rs->totaltime);
        }
        if ($rs = $DB->get_record_sql("SELECT COUNT(*) as done
                            FROM (SELECT DISTINCT ja.activity_name 
                                  FROM  {qv_activities} ja 
                                  WHERE ja.session_id='$session->session_id') t")){
            $activity->done=$rs->done;
        }
        
        if ($rs = $DB->get_record_sql("SELECT COUNT(*) as solved
                                FROM (SELECT DISTINCT ja.activity_name 
                                      FROM {qv_activities} ja 
                                      WHERE ja.session_id='$session->session_id' AND ja.activity_solved=1) t")){
            $activity->solved=$rs->solved;
        }
        
        return $activity;
    }    
        
    /**
    * Print a table data with all session activities 
    * 
    * @param string $session_id The session identifier
    */
    function qv_get_session_activities_html($session_id){
        $table_html='';

        // Import language strings
        $stractivity = get_string("activity", "qv");
        $strsolved = get_string("solved", "qv");
        $stractions = get_string("actions", "qv");
        $strtime = get_string("time", "qv");
        $strscore  = get_string("score", "qv");
        $stryes = get_string("yes");
        $strno = get_string("no");
        

        // Print activities for each session
        $activities = qv_get_activities($session_id);    
        if (sizeof($activities)>0){ 
            $table = new html_table();
            $table->attributes = array('class'=>'qv-activities-table');
            $table->head = array($stractivity, $strsolved, $stractions, $strtime, $strscore);
            foreach($activities as $activity){
                $act_percent=$activity->num_actions>0?round(($activity->score/$activity->num_actions)*100,0):0;
                $row = new html_table_row();
                $row->attributes = array('class' => ($activity->activity_solved?'qv-activity-solved':'qv-activity-unsolved') ) ;
                $row->cells = array($activity->activity_name, ($activity->activity_solved?$stryes:$strno), $activity->score.'/'.$activity->num_actions.' ('.$act_percent.'%)', qv_time2str($activity->total_time), $activity->qualification.'%');
                $table->data[] = $row;
            }
            $table_html = html_writer::table($table);
        }
        return $table_html;
    }
    
    /**
     * Convert specified time (in milliseconds) to XX' YY'' format
     * 
     * @param type $time time (in milliseconds) to format
     */
    function qv_format_time($time){
        return floor($time/60000)."' ".round(fmod($time,60000)/1000,0)."''";
    }

    /**
    * Get user activity summary
    *
    * @return object	session object with score, totaltime, activities done and solved and attempts information
    */
    function qv_get_sessions_summary($qvid, $userid) {
        global $CFG, $DB;

        qv_normalize_date();
        $sessions_summary = new stdClass(); 
        $sessions_summary->attempts = '';
        $sessions_summary->score = '';
        $sessions_summary->totaltime = '';
        $sessions_summary->starttime = '';
        $sessions_summary->done = '';
        $sessions_summary->solved = '';
        
        if ($rs = $DB->get_record_sql("SELECT COUNT(*) AS attempts, AVG(t.qualification) AS qualification, SUM(t.totaltime) AS totaltime, MAX(t.starttime) AS starttime
                            FROM (SELECT AVG(ja.qualification) AS qualification, SUM(ja.total_time) AS totaltime, MAX(js.session_datetime) AS starttime
                                  FROM {qv} j, {qv_sessions} js, {qv_activities} ja  
                                  WHERE j.id=js.qvid AND js.user_id='$userid' AND js.qvid=$qvid AND ja.session_id=js.session_id
                                  GROUP BY js.session_id) t")){
                $sessions_summary->attempts=$rs->attempts;
                $sessions_summary->score=round($rs->qualification,0);
                $sessions_summary->totaltime= qv_format_time($rs->totaltime);
                $sessions_summary->starttime=$rs->starttime;
        }

        if ($rs = $DB->get_record_sql("SELECT COUNT(*) as done
                            FROM (SELECT DISTINCT ja.activity_name 
                                  FROM {qv} j, {qv_sessions} js, {qv_activities} ja 
                                  WHERE j.id=js.qvid AND js.user_id='$userid' AND js.qvid=$qvid AND js.session_id=ja.session_id)  t")){
                $sessions_summary->done=$rs->done;
        }
        if ($rs = $DB->get_record_sql("SELECT COUNT(*) as solved
                            FROM (SELECT DISTINCT ja.activity_name 
                                  FROM {qv} j, {qv_sessions} js, {qv_activities} ja 
                                  WHERE j.id=js.qvid AND js.user_id='$userid' AND js.qvid=$qvid AND js.session_id=ja.session_id AND ja.activity_solved=1) t")){
        $sessions_summary->solved=$rs->solved;
        }
        return $sessions_summary;
    }    

    /**
    * Format time from milliseconds to string 
    *
    * @return string Formated string [x' y''], where x are the minutes and y are the seconds.	
    * @param int $time	The time (in ms)
    */
    function qv_time2str($time){
        return round($time/60000,0)."' ".round(fmod($time,60000)/1000,0)."''";
    } 
    

    function qv_print_results_table($qv, $context, $cm, $course, $action){
        global $CFG, $DB, $OUTPUT, $PAGE;
        
        // Preview link to JClic activity
        $url = new moodle_url('/mod/qv/view.php', array('id' => $context->instanceid, 'action' => 'preview'));
        //echo '<div id="qv-preview-link" ><A href="'.$url.'"  >'.get_string('preview_qv', 'qv').'</A></div>';

        // Show students list with their results
        require_once($CFG->libdir.'/gradelib.php');
        $perpage = optional_param('perpage', 10, PARAM_INT);
        $perpage = ($perpage <= 0) ? 10 : $perpage ;
        $page    = optional_param('page', 0, PARAM_INT);


        /// find out current groups mode
        $groupmode = groups_get_activity_groupmode($cm);
        $currentgroup = groups_get_activity_group($cm, true);

        /// Get all ppl that are allowed to submit qv
        list($esql, $params) = get_enrolled_sql($context, 'mod/qv:submit', $currentgroup); 
        $sql = "SELECT u.id FROM {user} u ".
               "LEFT JOIN ($esql) eu ON eu.id=u.id ".
               "WHERE u.deleted = 0 AND eu.id=u.id ";

        $users = $DB->get_records_sql($sql, $params);
        if (!empty($users)) {
            $users = array_keys($users);
        }

        // if groupmembersonly used, remove users who are not in any group
        if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }

        // Create results table
        if (function_exists('get_extra_user_fields') ) {
            $extrafields = get_extra_user_fields($context);
        } else{
            $extrafields = array();
        }
        $tablecolumns = array_merge(array('picture', 'fullname'), $extrafields,
                array('state', 'unread', 'grade', 'delivers', 'time', 'actions'));

        $extrafieldnames = array();
        foreach ($extrafields as $field) {
            $extrafieldnames[] = get_user_field_name($field);
        }
        
        $tableheaders = array_merge(
                array('', get_string('fullnameuser')),
                $extrafieldnames,
                array(
                    get_string('state', 'qv'),
                    get_string('unread', 'qv'),
                    get_string('score', 'qv'),
                    get_string('delivers', 'qv'),
                    get_string('time', 'qv'),
                    '&nbsp;',
                ));

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-qv-results');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/qv/view.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup.'&amp;action='.$action);

        $table->sortable(true, 'lastname'); //sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        foreach ($extrafields as $field) {
            $table->column_class($field, $field);
        }

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'results generaltable generalbox');
        $table->set_attribute('width', '100%');

        $table->no_sorting('state'); 
        $table->no_sorting('unread'); 
        $table->no_sorting('grade'); 
        $table->no_sorting('delivers'); 
        $table->no_sorting('time'); 
        $table->no_sorting('actions'); 

        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();

        /// Construct the SQL
        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $ufields = user_picture::fields('u', $extrafields);
        if (!empty($users)) {
            $select = "SELECT $ufields ";

            $sql = 'FROM {user} u '.
                   'WHERE '.$where.'u.id IN ('.implode(',',$users).') ';

            $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(), $table->get_page_size());

            $table->pagesize($perpage, count($users));
            $offset = $page * $perpage; //offset used to calculate index of student in that particular query, needed for the pop up to know who's next
            if ($ausers !== false) {
                //$grading_info = grade_get_grades($course->id, 'mod', 'qv', $qv->id, array_keys($ausers));
                $endposition = $offset + $perpage;
                $currentposition = $offset;
                $ausersObj = new ArrayObject($ausers);
                $iterator = $ausersObj->getIterator();
                $iterator->seek($currentposition);

                  while ($iterator->valid() && $currentposition < $endposition ) {
                    $auser = $iterator->current();
                    $picture = $OUTPUT->user_picture($auser);
                    $student_name = fullname($auser, has_capability('moodle/site:viewfullnames', $context));
                    $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $auser->id . '&amp;course=' . $course->id . '">' . $student_name . '</a>';
                    $extradata = array();
                    foreach ($extrafields as $field) {
                        $extradata[] = $auser->{$field};
                    }
                    
                    $assignment_summary = qv_get_assignment_summary($qv->id, $auser->id);
                    $qv_full_url = qv_get_full_url($qv, $assignment_summary, $auser->id, $student_name, true);
                    $student_info = $qv_full_url!=''?"<A href='#' onclick='openpopupName(\"$qv_full_url\",\"toolbar=no,status=no,scrollbars=yes,resizable=yes\",\"true\");'>$student_name</A>":$student_name; //Albert
                    $alert = qv_print_alerts($assignment_summary->states);
                    $states = qv_print_states($assignment_summary->states, $qv->maxdeliver);
                    if (isset($assignment_summary->id)) $unread_messages = qv_assignment_messages($assignment_summary->id);
                    else $unread_messages = 0;
                    
                    $viewlink = '';
                    if (!empty($assignment_summary->id)){
                        $viewlink = '<a href="'.$qv_full_url.'" target="_blank">'.get_string('view','qv').'</a>';                    
                    }
                    $row = array_merge(array($picture, $userlink), $extradata,
                            array($states, $unread_messages, $assignment_summary->pending_score, $assignment_summary->attempts, $assignment_summary->time, $viewlink));
                    $table->add_data($row);
                    
                    // Forward iterator
                    $currentposition++;
                    $iterator->next();
                }
                $table->print_html();  /// Print the whole table    
            }
        }        
    }

    /**
    * Check if exists specified url
    *
    * @return boolean           True if the specified URL exists; otherwise false.
    * @param string $url        The url
    */
    function qv_exists_url($url){
        $exists=false;
        if (substr($url, 0, 4)!='http') {
          $exists = (@fopen($url,"r"))?true:false;
        }
        return $exists;
    }    
    
    /**
    * Print QV alerts (for interactions).
    *
    * @return string                HTML code to print alert interaction of a qv activity (if it has).
    * @param array $states          The array with all qv states (and true or false for each one).
    * @param boolean $isteacher     False if must be printed alerts for students.
    */
    function qv_print_alerts($states, $isteacher=true){
        $html="";
        $state="intervencio_alumne";
        if (!$isteacher) $state="intervencio_docent";

        $strinteraction  = get_string("interaction", "qv");
        if(array_key_exists($state, $states) && $states[$state]=="true") {
            $html.="<IMG src='pix/qv_interaction.gif' alt='".$strinteraction."' title='".$strinteraction."'/>";
        }
        return $html;
    }    
    
    /**
    * Print QV states.
    *
    * @return string                HTML code to print the state of a qv activity.
    * @param array $states          The array with all qv states (and true or false for each one).
    * @param int $maxdeliver        The maximum number of delivers allowed
    */
    function qv_print_states($states, $maxdeliver){
        $statesimg="";
        if (isset($states)) {
            if (array_key_exists('state_-1', $states) && $states['state_-1']>0 && $states['state_0']==0 && $states['state_1']==0 && $states['state_2']==0){
                $statesimg.=qv_print_state_not_started(false);					
            }else if(array_key_exists('state_0', $states) && $states['state_0']>0){
                $statesimg.=qv_print_state_started(false);
            }
            if (array_key_exists('state_1', $states) && $states['state_1']>0){
                if (array_key_exists('state_0', $states) &&  $states['state_0']==0 && array_key_exists('state_-1', $states) && $states['state_-1']==0 && $states['state']=='1'){
                    $statesimg.=qv_print_state_delivered(false);
                }else {
                    $statesimg.=qv_print_state_partially_delivered(false);
                }
            }
            if (array_key_exists('state_2', $states) && $states['state_2']>0){
                if ( $states['state']=='2' && ( !array_key_exists('state_0', $states) || $states['state_0']==0) && (!array_key_exists('state_-1', $states) || $states['state_-1']==0 ) ){
                    $statesimg.=qv_print_state_corrected(false);
                }else {
                    $statesimg.=qv_print_state_partially_corrected(false);;
                }
            }
        }
        if ($statesimg=="") $statesimg=qv_print_state_not_started(false);;
        return $statesimg;
    }

    function qv_print_state_not_started($text=true){
        $strstatenotstarted  = get_string("statenotstarted", "qv");
        $html="<IMG src='pix/qv_state_not_started.gif' alt='".$strstatenotstarted."' title='".$strstatenotstarted."'/>";
        if ($text) $html.=' '.$strstatenotstarted;
        return $html;
    }
    function qv_print_state_started($text=true){
        $strstatestarted  = get_string("statestarted", "qv");
        $html="<IMG src='pix/qv_state_started.gif' alt='".$strstatestarted."' title='".$strstatestarted."'/>";
        if ($text) $html.=' '.$strstatestarted;
        return $html;
    }
    function qv_print_state_delivered($text=true){
        $strstatedelivered  = get_string("statedelivered", "qv");
        $html="<IMG src='pix/qv_state_delivered.gif' alt='".$strstatedelivered."' title='".$strstatedelivered."'/>";
        if ($text) $html.=' '.$strstatedelivered;
        return $html;
    }
    function qv_print_state_partially_delivered($text=true){
        $strstatepartiallydelivered  = get_string("statepartiallydelivered", "qv");
        $html="<IMG src='pix/qv_state_part_delivered.gif' alt='".$strstatepartiallydelivered."' title='".$strstatepartiallydelivered."'/>";
        if ($text) $html.=' '.$strstatepartiallydelivered;
        return $html;
    }
    function qv_print_state_corrected($text=true){
        $strstatecorrected  = get_string("statecorrected", "qv");
        $html="<IMG src='pix/qv_state_corrected.gif' alt='".$strstatecorrected."' title='".$strstatecorrected."'/>";
        if ($text) $html.=' '.$strstatecorrected;
        return $html;
    }
    function qv_print_state_partially_corrected($text=true){
        $strstatepartiallycorrected  = get_string("statepartiallycorrected", "qv");
        $html="<IMG src='pix/qv_state_part_corrected.gif' alt='".$strstatepartiallycorrected."' title='".$strstatepartiallycorrected."'/>";
        if ($text) $html.=' '.$strstatepartiallycorrected;
        return $html;
    }
    function qv_print_message_not_read($text=true){
        $strmessagenotread  = get_string("messagenotread", "qv");
        $html="<IMG src='pix/qv_msg_notread.gif' alt='".$strmessagenotread."' title='".$strmessagenotread."'/>";
        if ($text) $html.=' '.$strmessagenotread;
        return $html;
    }    
 
    /**
     * Workaround to fix an Oracle's bug when inserting a row with date
     */
    function qv_normalize_date () {
        global $CFG, $DB;
        if ($CFG->dbtype == 'oci'){
            $sql = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'";
            $DB->execute($sql);                        
        }        
    } 

    
    function qv_add_time($time1, $time2){//Albert
            $h1 = (int)substr($time1,0,2);
            $m1 = (int)substr($time1,3,5);
            $s1 = (int)substr($time1,6,8);
            $h2 = (int)substr($time2,0,2);
            $m2 = (int)substr($time2,3,5);
            $s2 = (int)substr($time2,6,8);

            $s3 = $s1+$s2;
            $m3 = $m1+$m2;
            $h3 = $h1+$h2;

            if ($s3>59){
                    $s3=$s3-60;
                    $m3=$m3+1;
            }
            if ($m3>59){
                    $m3=$m3-60;
                    $h3=$h3+1;
            }

            $s4 = "";
            if ($s3<10){
                    $s4 = "0".$s3;
            } else {
                    $s4 = $s3."";
            }
            $m4 = "";
            if ($m3<10){
                    $m4 = "0".$m3;
            } else {
                    $m4 = $m3."";
            }
            $h4 = "";
            if ($h3<10){
                    $h4 = "0".$h3;
            } else {
                    $h4 = $h3."";
            }

            return $h4.":".$m4.":".$s4;
    }

    function qv_assignment_messages($assignmentid){
        global $CFG, $USER, $DB;
        
        $unread = 0;
        if ($messages = $DB->get_record_sql("SELECT COUNT(*) AS unread
                                    FROM {$CFG->prefix}qv_sections s, {$CFG->prefix}qv_messages m
                                    WHERE s.id = m.sid AND s.assignmentid ='$assignmentid'
                                        AND (m.sid NOT IN (SELECT mr.sid FROM {$CFG->prefix}qv_messages_read mr WHERE mr.userid=$USER->id)
                                        OR m.created>(SELECT MAX(timereaded) FROM {$CFG->prefix}qv_messages_read mr WHERE mr.userid=$USER->id )) ")){
            $unread = $messages->unread;
        }
        return $unread;
    }
    
    
