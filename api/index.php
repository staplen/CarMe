<?php 



/* Set global directory / system root variables
------------------------------------------------------------------*/
define('DS',              DIRECTORY_SEPARATOR);
define('SYSROOT',         dirname(__FILE__));
define('LIB_DIR',         SYSROOT . DS . 'lib');
define('CONFIG_DIR',      SYSROOT . DS . 'config');
define('ASSETS_DIR',      SYSROOT . DS . 'assets');
define('SYSPATH',         SYSROOT);



/* Call config files
------------------------------------------------------------------*/
require_once (CONFIG_DIR . DS . 'config.php');
require_once (CONFIG_DIR . DS . 'autoload.php');
require_once (CONFIG_DIR . DS . 'environment.php');
require_once (CONFIG_DIR . DS . 'locale.php');
define('URL_ROOT',        $config['site_url']);


/* Connect to the database
------------------------------------------------------------------*/
$carme_db  = new fDatabase($config['db_type'], $config['db_name'], $config['db_user'], $config['db_pass'], $config['db_url']);


/* Start the session
------------------------------------------------------------------*/
fSession::setPath(SYSROOT . DS . 'tmp' . DS . 'sessions');
fSession::setLength('7 days');
fSession::open();
fAuthorization::setLoginPage('/api/login');
fAuthorization::setAuthLevels(
  array(
      'admin' => 100,
      'user'  => 50,
      'guest' => 25
  )
);


/* Get queries
------------------------------------------------------------------*/
$page         = strtolower(fRequest::get('page') ?      fRequest::get('page')     : 'vehicles');
$query        = strtolower(fRequest::get('query') ?     fRequest::get('query')    : FALSE);
$subquery     = strtolower(fRequest::get('subquery') ?  fRequest::get('subquery') : FALSE);

require_once('oauth.php');
require_once('helper_functions.php');

if ($page === 'login') {
  require_once('login.php');
  exit;
}
else if ($page === 'logout') {
  require_once('logout.php');
  exit;
}
else if ($page === 'getuserinfo') {
  require_once('user_info.php');
  exit;
}
else if ($page === 'vehicles') {
  require_once('vehicles.php');
  exit;
}
else if ($page === 'closestvehicle') {
  require_once('closest_vehicle.php');
  exit;
}
else if ($page === 'all_vehicles') {
  require_once('all_vehicles.php');
  exit;
}
else if ($query || $page === 'booking') {
  require_once('router_advanced.php');
  exit;
}
else if ($page == 'datacron') {
  require_once('data_cron.php');
  exit;
}
else {
  require_once('router_get.php');
  exit;
}

?>