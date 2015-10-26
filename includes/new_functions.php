<?php
/**
 * Main functions file for EveAdmin
 *
 * PHP Version: 5.5+
 *
 * @package EveAdmin
 * @author Josh Grancell <josh@joshgrancell>
 * @copyright 2013-2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 * @since 0.0-dev
 */

function uriChecker() {

	$raw_requests = explode("/", trim($_SERVER['REQUEST_URI'], "/"));
	$requests = array();
	$requests['page'] = FALSE;
	$requests['action'] = FALSE;
	$requests['value'] = NULL;
	$requests['value_2'] = NULL;

	if(isset($raw_requests[0])) {
		$requests['page'] = $raw_requests[0];
	}

	if(isset($raw_requests[1])) {
		$requests['action'] = $raw_requests[1];
	}

	if(isset($raw_requests[2])) {
		$requests['value'] = $raw_requests[2];
	}

	if(isset($raw_requests[3])) {
		$requests['value_2'] = $raw_requests[3];
	}
	return $requests;

}

/**
  * Loads the correct /core/ file page based on the URI requested. If the correct page cannot be found, loads the 404 catch page
  *
  *	@param string $page request uri passed from the uriChecker() function
  * @access public
  * @return string $require_this a string of a file name to be required by the index file
 */

function bootstrapper($request) {
	if(file_exists(DOCUMENT_ROOT.'/core/'.$request['page'].'.php')) {
		$require_this = DOCUMENT_ROOT.'/core/'.$request['page'].'.php';
	} elseif($request['page'] == 'modules' AND file_exists(DOCUMENT_ROOT.'/modules/'.$request['action'].'.php')) {
		$require_this = DOCUMENT_ROOT.'/modules/'.$request['action'].'.php';
	} elseif($request['page'] == NULL) {
		$require_this = DOCUMENT_ROOT.'/core/dashboard.php';
	} else {
		$require_this = DOCUMENT_ROOT.'/core/error.notfound.php';
	}

	return($require_this);
}

function skillCountdownTimer($id, $datetime) {
	?>
	<script type="text/javascript">
		window.onload = function() {
	      $('#<?php echo $id; ?>').countdown('<?php echo $datetime; ?>', function(event) {
	      	if(event.offset.totalDays >= 1) {
	        	$(this).html(event.strftime('%-D day%!D, %-H hour%!H, %-M minute%!M'));
	        } else if (event.offset.hours >= 1 ) {
	        	$(this).html(event.strftime('%-H hour%!H and %-M minute%!M'));
	        } else {
	        	$(this).html(event.strftime('%-M minute%!M'));
	        }
	      });
		};
	</script>
	<?php
}

function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
	    $ini += strlen($start);
	    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}