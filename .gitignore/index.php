<?php
/* made by a reeetard*/
$name = "Nintendo";
$last = "Nintendo64";
$age = 'unkown';
echo "rule34.xxx is awsome";
    

if(!file_exists("data/config/shimmie.conf.php")) {
	header("Location: install.php");
	exit;
}
require_once "core/sys_config.inc.php";
require_once "core/util.inc.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

try {
	// load base files
	ctx_log_start("Opening files");
	$files = array_merge(zglob("core/*.php"), zglob("ext/{".ENABLED_EXTS."}/main.php"));
	foreach($files as $filename) {
		require_once $filename;
	}
	ctx_log_endok();

	ctx_log_start("Connecting to DB");
	// connect to the database
	$database = new Database();
	$config = new DatabaseConfig($database);
	ctx_log_endok();

	// load the theme parts
	ctx_log_start("Loading themelets");
	foreach(_get_themelet_files(get_theme()) as $themelet) {
		require_once $themelet;
	}
	ctx_log_endok();

	_load_extensions();

	// start the page generation waterfall
	$page = class_exists("CustomPage") ? new CustomPage() : new Page();
	$user = _get_user();
	send_event(new InitExtEvent());
	if(!is_cli()) { // web request
		send_event(new PageRequestEvent(@$_GET["q"]));
		$page->display();
	}
	else { // command line request
		send_event(new CommandEvent($argv));
	}

	// saving cache data and profiling data to disk can happen later
	if(function_exists("fastcgi_finish_request")) fastcgi_finish_request();
	$database->commit();
	ctx_log_endok();
}
catch(Exception $e) {
	if($database) $database->rollback();
	_fatal_error($e);
	ctx_log_ender();
}

