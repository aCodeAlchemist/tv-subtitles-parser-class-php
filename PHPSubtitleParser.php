<?php

/**
* Open Source MIT License
* V 1.0.0
* Author Shreejibawa
* Git Hub http://github.com/shreejibawa
*/
class PHPSubtitleParser
{

	/** Globals */
	private $SRT_STATE_SUBNUMBER = 0;
	private $SRT_STATE_TIME = 1;
	private $SRT_STATE_TEXT = 2;
	private $SRT_STATE_BLANK = 3;
	private $filePath = '';
	private $fileExtention = '';
	private $tmp_dir = '';
	
	/** Configs */
	private $allowedExtensions = array('srt', 'rar', 'zip');
	private $maxUploadSize = 1; // MB

	public function __construct($file=NULL) {
		$this->validateFile($file);
	}

	private function validateFile($file) {
		if(isset($file['file']['tmp_name'])) {

			$this->filePath = $file['file']['tmp_name'];

			$this->fileExtention = $this->getFileExtension($file['file']['name']);
			
			if(!in_array($this->fileExtention, $this->allowedExtensions)) {
				throw new Exception("File type you are attempting to upload is not allowed.");
			}

			if(!is_readable($this->filePath)) {
				throw new Exception("Read permission not allowed.");
			}

			if($file['file']['size'] >= ($this->maxUploadSize*1024*1024)) { // Max 1 MB
				throw new Exception("File type you are attempting to upload is too big.");
			}

		} else {
			throw new Exception("Couldn't find the file.");
		}
	}

	private function getFileExtension($path) {
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		return $ext;
	}

	/**
	 * Extract rar and get srt files
	 */
	// NEEDS EXTENSION rar 
	private function handleSRTFromRar() {
	    $rar_file = rar_open($this->filePath) or die("Can't open Rar archive");

	    $entries = rar_list($rar_file);

	    foreach ($entries as $entry) {
			
	        /** @var [string] [Get file extension] */
	        $ext = $this->getFileExtension($entry->getName());
			
	        /** If its a srt file then extract it */
	        if($ext === 'srt') {
	            $entry->extract(sys_get_temp_dir());

	            $this->filePath = sys_get_temp_dir() . $entry->getName();
	            break;
	        }
		   
	    }

	    rar_close($rar_file);
	}
	
	public static function deleteDir($dirPath) {
	    if (! is_dir($dirPath)) {
	        throw new InvalidArgumentException("$dirPath must be a directory");
	    }
	    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
	        $dirPath .= '/';
	    }
	    $files = glob($dirPath . '*', GLOB_MARK);
	    foreach ($files as $file) {
	        if (is_dir($file)) {
	            self::deleteDir($file);
	        } else {
	            unlink($file);
	        }
	    }
	    rmdir($dirPath);
	}

	private function response($array) {
		header("Content-type:application/json");
		echo json_encode($array);
	}
	
	private function handleZip() {
		$zip = new ZipArchive;
		$res = $zip->open($this->filePath);
		if ($res === TRUE) {
			
			$tmpDir = uniqid();
			
			if(mkdir($tmpDir, 0777)) {
				
				$this->tmp_dir = $tmpDir;

				$zip->extractTo($this->tmp_dir);
				
				$files = scandir($this->tmp_dir);
				
				foreach ($files as $value) {
					if($this->getFileExtension($value) == 'srt') {
						/** Copy Srt file to tmp dir  */
						copy($this->tmp_dir.'/'.$value, sys_get_temp_dir().'/'.$value);
						
						/** Delete temporary folder */
						$this->deleteDir($this->tmp_dir);

						/** Set original file path */
						$this->filePath = sys_get_temp_dir().'/'.$value;
						
						break;
					}
				}

			} else {
				$zip->close();
				throw new Exception("Error while creating temporary directory.");
			}

			$zip->close();

		} else {

		}
	}

	private function formatSubs($subs, $format) {
		switch ($format) {
			case 'json':
				$array = array("data" => $subs);
				$this->response($array);
				break;
			
			default:
				# code...
				break;
		}
	}

	private function parseSrt($format) {
		$lines   = file($this->filePath);

		$subs    = array();
		$state   = $this->SRT_STATE_SUBNUMBER;
		$subNum  = 0;
		$subText = '';
		$subTime = '';

		foreach($lines as $line) {
		    switch($state) {
		        case $this->SRT_STATE_SUBNUMBER:
		            $subNum = trim($line);
		            $state  = $this->SRT_STATE_TIME;
		            break;

		        case $this->SRT_STATE_TIME:
		            $subTime = trim($line);
		            $state   = $this->SRT_STATE_TEXT;
		            break;

		        case $this->SRT_STATE_TEXT:
		            if (trim($line) == '') {
		                $sub = new stdClass;
		                $sub->number = $subNum;
		                list($sub->startTime, $sub->stopTime) = explode(' --> ', $subTime);
		                $sub->text   = trim(preg_replace('/\s\s+/', ' ', $subText));
		                $subText     = '';
		                $state       = $this->SRT_STATE_SUBNUMBER;

		                $subs[]      = $sub;
		            } else {
		                $subText .= $line;
		            }
		            break;
		    }
		}

		$this->formatSubs($subs, $format);

	}
	
	
	public function get($format='json') {
		switch ($this->fileExtention) {
			
			case 'zip':

				$this->handleZip($this->filePath);
				
				$this->parseSrt($format);
				
				break;

			case 'srt':
				$this->parseSrt($format);
				break;
			
			default:
				# code...
				break;
		}
	}
}
?>
