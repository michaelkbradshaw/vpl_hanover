<?php
/**
 * @version		$Id: filegroup.class.php,v 1.19 2013-04-17 10:18:53 juanca Exp $
 * @package		VPL. class to edit a group of files
 * @copyright	2012 Juan Carlos Rodríguez-del-Pino
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author		Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/locallib.php';
require_once dirname(__FILE__).'/views/sh_factory.class.php';

class file_group_process{
	/**
	 * Name of file list
	 *
	 * @var string
	 */
	protected $filelistname;

	/**
	 * Path to directory where files are saved
	 *
	 * @var string
	 */
	protected $dir;

	/**
	 * Maximum number of files
	 *
	 * @var int
	 */
	protected $maxnumfiles;

	/**
	 * Number of files not changeables 0..($numstaticfiles-1)
	 *
	 * @var int
	 */
	protected $numstaticfiles;

	/**
	 * Constructor
	 *
	 * @param string $filelistname
	 * @param string $dir
	 * @param int $maxnumfiles
	 * @param int $numstaticfiles
	 */
	function __construct($filelistname,$dir,$maxnumfiles=10000,$numstaticfiles=0){
		$this->filelistname = $filelistname;
		$this->dir = $dir;
		if(strlen($dir) == 0 || $dir[strlen($dir)-1] != '/'){
			$this->dir .= '/';
		}
		$this->maxnumfiles = $maxnumfiles;
		$this->numstaticfiles = $numstaticfiles;
	}

	/**
	 * Get max number of files.
	 *
	 * @return int
	 */
	function get_maxnumfiles(){
		return $this->maxnumfiles;
	}

	/**
	 * Get number of static files.
	 *
	 * @return int
	 */
	function get_numstaticfiles(){
		return $this->numstaticfiles;
	}

	/**
	 * Add a new file to the group/Modify the data file
	 *
	 * @param string $filename
	 * @param string $data
	 * @return bool (added==true)
	 */
	function addFile($filename,$data=null){
		if($filename==''){
			return false;
		}
		ignore_user_abort (true);
		$filelist = $this->getFileList();
		foreach($filelist as $f){
			if($filename == $f){
				if($data !== null){
					$fd = vpl_fopen($this->dir.$filename);
					fwrite($fd,$data);
					fclose($fd);
				}
				return true;
			}
		}
		if(count($filelist)>= $this->maxnumfiles){
			return false;
		}
		$filelist[] = $filename;
		$this->setFileList($filelist);
		if($data){
			$fd = vpl_fopen($this->dir.$filename);
			fwrite($fd,$data);
			fclose($fd);
		}
		return true;
	}

	/**
	 * Delete a file from groupfile
	 *
	 * @param int $num file position
	 * @return bool
	 */
	function deleteFile($num){
		if($num < $this->numstaticfiles){
			return false;
		}
		ignore_user_abort (true);
		$filelist = $this->getFileList();
		$l = count($filelist);
		$ret = false;
		$filelistmod = array();
		for($i = 0 ;$i <$l; $i++){
			if($num== $i){
				$fullname = $this->dir.$filelist[$num];
				$ret = true;
				if(file_exists($fullname)){
					unlink($fullname);
				}
			}
			else{
				$filelistmod[]=$filelist[$i];
			}
		}
		if($ret){
			$this->setFileList($filelistmod);
		}
		return $ret;
	}

	/**
	 * Rename a file
	 *
	 * @param int $num
	 * @param string $filename new filename
	 * @return bool (renamed==true)
	 */
	function renameFile($num,$filename){
		if($num<$this->numstaticfiles || $filename == ''){
			return false;
		}
		ignore_user_abort (true);
		$filelist = $this->getFileList();
		if(array_search($filename,$filelist) !== false){
			return false;
		}
		if($num >= 0 && $num<count($filelist)){
			if(file_exists($this->dir.$filelist[$num])){
				rename($this->dir.$filelist[$num],$this->dir.$filename);
			}
			$filelist[$num] =$filename;
			$this->setFileList($filelist);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Get list of files
	 *
	 * @return string[]
	 */
	function getFileList(){
		return vpl_read_list_from_file($this->filelistname);
	}

	/**
	 * Set the file list.
	 *
	 * @param string[] $filelist
	 */
	function setFileList($filelist){
		vpl_write_list_to_file($this->filelistname,$filelist);
	}

	/**
	 * Get the file comment by number
	 *
	 * @param int $num
	 * @return string
	 */
	function getFileComment($num){
		return get_string('file').' '.($num+1);
	}

	/**
	 * Get the file data by number or name
	 *
	 * @param int/string $mix
	 * @return string
	 */
	function getFileData($mix){
		if(is_int($mix)){
			$num=$mix;
			$filelist = $this->getFileList();
			if($num>=0 && $num<count($filelist)){
				$filename =$this->dir.$filelist[$num];
				if(file_exists($filename)){
					return file_get_contents($filename);
				}else{
					return '';
				}
			}
		}
		elseif(is_string($mix)){
			$filename=basename($mix);
			$filelist = $this->getFileList();
			if(array_search($filename,$filelist)!== false){
				$fullfilename =$this->dir.$filename;
				if(file_exists($fullfilename)){
					return file_get_contents($fullfilename);
				}else{
					return '';
				}
			}
		}
		debugging("File not found $mix",DEBUG_DEVELOPER);
		return '';
	}

	/**
	 * Return is there is some file with data
	 * @return boolean
	 */
	function is_populated(){
		$filelist = $this->getFileList();
		foreach($filelist as $filename){
			$fullname = $this->dir.$filename;
			if(file_exists($fullname)){
				$info = stat($fullname);
			 	if($info['size']>0){
			 		return true;
			 	}
			}
		}
		return false;
	}


	/**
	 * Print file group
	 **/
	function print_files($if_no_exist=true){
		global $OUTPUT;
		$filenames = $this->getFileList();
		foreach ($filenames as $name) {
			if(file_exists($this->dir.$name)){
				echo '<h3>'.s($name).'</h3>';
				$printer= vpl_sh_factory::get_sh($name);
				echo $OUTPUT->box_start();
				$data = $this->getFileData($name);
				$printer->print_file($name,$data);
				echo $OUTPUT->box_end();
			}elseif($if_no_exist){
				echo '<h3>'.s($name).'</h3>';
			}
		}
	}
	
	/**
	 * Download files
	 * @parm $name name of zip file generated
	 **/
	function download_files($name){
		global $CFG;
		$zip = new ZipArchive();
		$zipfilename=tempnam($CFG->dataroot . '/temp/'  , 'vpl_zipdownload' );
		if($zip->open($zipfilename,ZIPARCHIVE::CREATE)){
			foreach ($this->getFileList() as $filename) {
				$zip->addFromString($filename, $this->getFileData($filename));
			}
			$zip->close();
			//Get zip data
			$data=file_get_contents($zipfilename);
			//remove zip file
			unlink($zipfilename);
			//Send zipdata
			@header('Content-Length: '.strlen($data));
			@header('Content-Type: application/octet-stream; charset=utf-8');
			@header('Content-Disposition: attachment; filename="'.$name.'.zip"');
			@header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
			@header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
			@header('Pragma: no-cache');
			@header('Accept-Ranges: none');
			echo $data;
		}
	}
}
?>
