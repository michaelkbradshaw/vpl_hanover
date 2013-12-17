<?php
/**
 * @version		$Id: similarity_form.class.php,v 1.15 2013-06-10 08:30:21 juanca Exp $
 * @package		VPL. Similarity form class
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

global $CFG;
require_once dirname(__FILE__).'/../../../config.php';
require_once $CFG->libdir.'/formslib.php';
class vpl_similarity_form extends moodleform {
	private $vpl;
	function __construct($page,$vpl){
		$this->vpl = $vpl;
		parent::__construct($page);
	}
	function list_activities($vplid){
		global $DB;
		$list=array(''=>'');
		//Low privilegies
		$courses=get_user_capability_course(VPL_VIEW_CAPABILITY);
		foreach($courses as $course){
			$vpls = $DB->get_records(VPL,array('course' => $course->id));
			foreach($vpls as $vplinstace){
				if($vplinstace->id == $vplid){
					continue;
				}
				$othervpl = new mod_vpl(false,$vplinstace->id);
				if(! $othervpl->get_course_module()){
					continue;
				}
				if($othervpl->has_capability(VPL_SIMILARITY_CAPABILITY)){
					$list[$othervpl->get_course_module()->id]=$othervpl->get_course()->shortname.' '.$othervpl->get_printable_name();
				}
			}
			if(count($list)>400){
				break; //Stop loading instances
			}
		}
		asort($list);
		$list['']=get_string('select');
		return $list;
	}
	
	function get_directories($dirpath){
        $ret = array();
        $dd = @opendir($dirpath);
        if($dd !== false){
	        while ($dir=readdir($dd)) {
	            if ($dir!='.' && $dir!='..' && is_dir($dirpath."/".$dir)) {
	                $ret[] = $dir;
	            }
	        }
        	closedir($dd);
        }
        return $ret;
    }

	function list_directories($cid){
		global $CFG;
		$dirs=array();
		$dirs=array('' => get_string('select'));
		$basedir=$CFG->dataroot.'/'.$cid;
		foreach($this->get_directories($basedir) as $dir){
			$dirs[$dir]=$dir;
			foreach($this->get_directories($basedir.'/'.$dir) as $inner){
				$dirs[$dir.'/'.$inner]=$dir.'/'.$inner;
			}
		}
		return $dirs;
	}
	function definition(){
		$mform    =& $this->_form; 
        $mform->addElement('hidden','id',required_param('id',PARAM_INT));
		$mform->setType('id', PARAM_INT);
        $filelist = $this->vpl->get_required_fgm()->getFileList();
		if(count($filelist)>0){
	        $mform->addElement('header', 'headerfilestoprocess', get_string('filestoscan', VPL));
			$num=0;
			foreach($filelist as $filename){
				$mform->addElement('checkbox','file'.$num,$filename);
				$mform->setDefault('file'.$num, true);
				$mform->disabledIf('file'.$num, 'allfiles','checked');
				$num++;
			}
			$mform->addElement('checkbox','allfiles',get_string('allfiles',VPL));
			$mform->addElement('checkbox','joinedfiles',get_string('joinedfiles',VPL));
			$mform->disabledIf('joinedfiles', 'allfiles','checked');
		}else{
			$mform->addElement('hidden','allfiles','checked');
			$mform->setType('allfiles', PARAM_BOOL);
		}
		$mform->addElement('header', 'headerfilestoprocess', get_string('scanoptions', VPL));
        $options=array();
        for($i=5; $i<=40; $i+=5){
        	$options[$i]=$i;
        }
        for($i=60; $i<=100; $i+=20){
        	$options[$i]=$i;
        }
        for($i=150; $i<=400; $i+=50){
        	$options[$i]=$i;
        }
        $mform->addElement('select', 'maxoutput', get_string('maxsimilarityoutput',VPL), $options);
        $mform->setType('maxoutput', PARAM_INT);
        $cid=$this->vpl->get_course()->id;
		$mform->addElement('header', 'headerothersources', get_string('othersources', VPL));
        $mform->addElement('select', 'scanactivity', get_string('scanactivity',VPL),$this->list_activities($this->vpl->get_instance()->id));
        $mform->addElement('filepicker', 'scanzipfile0', get_string('scanzipfile', VPL));
        //TODO match 2.X? 
        //$mform->addElement('filepicker', 'scanzipfile1', get_string('scanzipfile', VPL));
        //$mform->addElement('select', 'scandirectory', get_string('scandirectory', VPL), $this->list_directories($cid));
        $mform->addElement('checkbox','searchotherfiles',get_string('scanother',VPL));
        $mform->setDefault('searchotherfiles', false);
        $this->add_action_buttons(false,get_string('search'));
	}
}
?>