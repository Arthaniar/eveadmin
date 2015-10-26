<?php
//Class autoloader
Class Autoloader {
	public static function load($class_name) {
		require_once(DOCUMENT_ROOT.'/classes/'.$class_name.'.class.php');
	}
}