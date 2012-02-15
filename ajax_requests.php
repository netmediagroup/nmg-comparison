<?
/*
 * I had to set up this script, instead of /wp-admin/admin-ajax.php, to handle the AJAX calls
 * because that one requires the user to be logged in.
 */

define('DOING_AJAX', true);

require_once(dirname(__FILE__).'/../../../wp-load.php');
require_once(dirname(__FILE__)."/treadmill-comparison.php");

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));


if (isset($_REQUEST['action'])) {
  switch ($action = $_REQUEST['action']) {
    case 'treadmill_comparison_table' :
      die(ajaxResponseTreadmillComparisonTable());
    break;
    default :
      die;
    break;
  }
}

?>