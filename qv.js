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
 * Javascript helper function for Folder module
 *
 * @package    mod
 * @subpackage qv
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_qv = {};

M.mod_qv.init = function(Y, params) {
    setJarBase(params['qv_jarbase']);
    setReporter('TCPReporter','path='+params['qv_path']+';service='+params['qv_service']+';user='+params['qv_user']+';key='+params['id']+';lap='+params['qv_lap']+';protocol='+params['qv_protocol']);
    setSkin(params['skin']);
    setLanguage(params['lang']);
    setExitUrl(params['exiturl']);
    document.getElementById('qv_applet').innerHTML = getPlugin(params['qv_url'], params['width'], params['height']);   
};


function showSessionActivities(sessionid){
    activities = document.getElementById('session_'+sessionid);
    if (activities.className == 'qv-session-activities-visible') {
        activities.className='qv-session-activities-hidden';        
    } else{
        activities.className='qv-session-activities-visible';        
    }
}

//TODO: It has to be done with moodle functions
function openpopup(url,options,fullscreen) {
  windowobj = window.open(url,name,options);
  if (fullscreen) {
     windowobj.moveTo(0,0);
     windowobj.resizeTo(screen.availWidth, screen.availHeight);
  }
  windowobj.focus();
  return false;
}

function openpopupName(url,options,fullscreen) {
  windowobj = window.open(url,"QV",options);//Albert
  if (fullscreen) {
     windowobj.moveTo(0,0);
     windowobj.resizeTo(screen.availWidth, screen.availHeight);
  }
  windowobj.focus();
  return false;
}
