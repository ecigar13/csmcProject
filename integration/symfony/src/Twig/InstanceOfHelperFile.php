<?php

namespace App\Twig;
use App\Helpers\File as HelperFile;
use App\Entity\File\Link as CSMCLink;

class InstanceOfHelperFile extends \Twig_Extension {
	    public function getTests() {
        return array(
            new \Twig_SimpleTest('isCSMCFile', array($this, 'isFile')),
			new \Twig_SimpleTest('isCSMCLink', array($this, 'islink')),
         );
     }
	
	/**
	* @param $var
	* @param $instance
	* @return bool
	*/
	public function isFile($var) {
		return  $var instanceof HelperFile;
	}

		/**
	* @param $var
	* @param $instance
	* @return bool
	*/
	public function isLink($var) {
		return  $var instanceof CSMCLink;
	}
}