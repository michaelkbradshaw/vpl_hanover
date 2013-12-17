<?php
/**
 * @version		$Id: mod_form.php,v 1.30 2013-06-10 11:06:25 juanca Exp $
 * @package		VPL. vpl instance form
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot.'/course/moodleform_mod.php';
require_once dirname(__FILE__).'/lib.php';
require_once dirname(__FILE__).'/vpl.class.php';

class mod_vpl_mod_form extends moodleform_mod {
	function definition(){
		global $CFG;
		$mform = & $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // name
        $modname= 'vpl';
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'50'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->applyFilter('name','trim');
        // shortdescription
        $mform->addElement('textarea', 'shortdescription', get_string('shortdescription',VPL), array('cols'=>70, 'rows'=>1));
        $mform->setType('shortdescription', PARAM_RAW);
        $this->add_intro_editor(false,get_string('fulldescription',VPL));
        $mform->addElement('header', 'submissionperiod', get_string('submissionperiod', VPL));
        $secondsday=24*60*60;
        $now = time();
        $inittime = round($now / $secondsday) * $secondsday+5*60;
        $endtime = $inittime + (8*$secondsday) - 5*60;
        // startdate
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', VPL), array('optional'=>true));
		$mform->setDefault('startdate', 0);
        $mform->setAdvanced('startdate');
        // duedate
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', VPL), array('optional'=>true));
        $mform->setDefault('duedate', $endtime);
	//
	$mform->addElement("text","percent_drop","Drop by Percentage", //get_string('percent_drop',VPL),
			   array('optional'=>true));
	$mform->setType('percent_drop',PARAM_FLOAT);
	$mform->setDefault('percent_drop',0);

	$mform->addElement('advcheckbox', 'must_complete', "Must Complete?", 'Only perfect evaluations will be recorded');
	$mform->setDefault('must_complete',0);

        // maxfiles
        $mform->addElement('header', 'submissionrestrictions', get_string('submissionrestrictions', VPL));
        $mform->addElement('text', 'maxfiles', get_string('maxfiles',VPL),array('size'=>'2'));
        $mform->setType('maxfiles', PARAM_INT);
        $mform->setDefault('maxfiles', 1);
        $mform->addElement('select', 'worktype', get_string('worktype',VPL),
        					array(0 => get_string('individualwork',VPL),1 => get_string('groupwork',VPL)));
        $mform->addElement('selectyesno', 'restrictededitor', get_string('restrictededitor',VPL));
        $mform->setDefault('restrictededitor', false);
        $mform->setAdvanced('restrictededitor');
        $mform->addElement('selectyesno', 'example', get_string('isexample',VPL));
        $mform->setDefault('example', false);
        $mform->setAdvanced('example');
        $max = vpl_get_max_post_size();
		if($CFG->vpl_maxfilesize > 0 && $CFG->vpl_maxfilesize < $max){
			$max = $CFG->vpl_maxfilesize;
		}
        $mform->addElement('select', 'maxfilesize', get_string('maxfilesize',VPL),
        					vpl_get_select_sizes($max));
        $mform->setType('maxfilesize', PARAM_INT);
        $mform->setDefault('maxfilesize', 0);
        $mform->setAdvanced('maxfilesize');
        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password');
        $mform->addElement('text', 'requirednet', get_string('requirednet',VPL),array('size'=>'60'));
        $mform->setType('requirednet', PARAM_TEXT);
        $mform->setDefault('requirednet', '');
        $mform->setAdvanced('requirednet');
        // grade
        $this->standard_grading_coursemodule_elements();
        $mform->addElement('selectyesno', 'visiblegrade', get_string('visiblegrade',VPL));
        $mform->setDefault('visiblegrade', 1);
        //Standard course elements
        $this->standard_coursemodule_elements();
        // end form
        $this->add_action_buttons();
	}
	function display(){
		$id = optional_param('update',FALSE,PARAM_INT);
		if($id){
			$vpl = new mod_vpl($id);
			$vpl->print_configure_tabs('edit');
			if($vpl->get_grade()){
				$vpl->get_instance()->visiblegrade = ($vpl->get_grade_info()->hidden)?0:1;
			}
			$this->set_data($vpl->get_instance());
		}
		parent::display();
	}
}
?>
