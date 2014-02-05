<?php
/**
 * File autoload configuration
 * 
 * @package     CarMe API
 * @author      Nico Staple <nico@getmighty.com>
 *
 */


/**
 * Class autoloader
 *
 * @param string $file_name name for class file
 * @return string $file_path location of file to load
 */
function classAutoload($file_name) {
	
	$lib_path 						= LIB_DIR . DS . str_replace('\\', DS, $file_name) . '.php';
	
	if (file_exists($lib_path)) {
		return require $lib_path;
	}
	else {
		die("Class file not found -- ".$file_name);
	}
}

spl_autoload_register('classAutoload');