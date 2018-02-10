<?php
spl_autoload_register(function($class){
	$baseDir = './src/';
	
	$prefix = 'FHMJ\\PdoFlexibleSearch\\';
	if(($pos = strpos($class, $prefix)) === 0){
		$class = substr($class, strlen($prefix));
	}
	$class = str_replace('\\', '/', $class);
	
	if(file_exists($baseDir.$class.'.php')){
		require($baseDir.$class.'.php');
	}
});
