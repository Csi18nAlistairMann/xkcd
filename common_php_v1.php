<?php

//
// Each translation appears within a balloon, and each balloon needs
// to be identified. Specifying balloon identifiers here allows us,
// with frame_array above, to determine which identifiers are active
// for this xkcd, and which are unused.
//  The code here is for upto 8 eight frames, with upto 48 text 
// balloons in the first frame, and upto 4 text balloons in the 
// remainder. The code here can be C&Pd to 99 text balloons and
// upto 9 frames. Beyond there, tabindex would need to be reworked.
$balloon_names = array(array('fr0001', 'fr0002'),
		       array('fr0101', 'fr0102', 'fr0103', 'fr0104', 'fr0105', 'fr0106', 'fr0107', 'fr0108', 
			     'fr0109', 'fr0110', 'fr0111', 'fr0112', 'fr0113', 'fr0114', 'fr0115', 'fr0116',
			     'fr0117', 'fr0118', 'fr0119', 'fr0120', 'fr0121', 'fr0122', 'fr0123', 'fr0124', 
			     'fr0125', 'fr0126', 'fr0127', 'fr0128', 'fr0129', 'fr0130', 'fr0131', 'fr0132',
			     'fr0133', 'fr0134', 'fr0135', 'fr0136', 'fr0137', 'fr0138', 'fr0139', 'fr0140', 
			     'fr0141', 'fr0142', 'fr0143', 'fr0144', 'fr0145', 'fr0146', 'fr0147', 'fr0148'),
		       array('fr0201', 'fr0202', 'fr0203', 'fr0204'),
		       array('fr0301', 'fr0302', 'fr0303', 'fr0304'),
		       array('fr0401', 'fr0402', 'fr0403', 'fr0404'),
		       array('fr0501', 'fr0502', 'fr0503', 'fr0504'),
		       array('fr0601', 'fr0602', 'fr0603', 'fr0604'),
		       array('fr0701', 'fr0702', 'fr0703', 'fr0704'),
		       array('fr0801', 'fr0802', 'fr0803', 'fr0804')
		       );
//
// UTF-8 bc unicode and reasons; head and lack of style amirite
header("Content-Type: text/html; charset=utf-8");
include_once('../head_and_style.html');

//
// This is the website itself communicating with the backend.
function post_a_new($un, $pw, $apikey, $root, $lang, $vis, $uplds_arr, $name, $opt_ignore_blanks, $thisPageBelongsToSID) {
  if (!array_key_exists($name, $uplds_arr)) { 
    // handle translations not supplied as they repeat earlier uses
    return '';
  } else {
    $translation = $uplds_arr[$name];
  }
  if ($opt_ignore_blanks === 'ignore' && $translation == '') {
    return '';
  }

  $newmark = $root . $name;
  $errno = $errstr = '';
  $repost = false;
  $fp = fsockopen("tls://rest.mpsvr.com", 443, $errno, $errstr, 10);
  if (!$fp) {
    $error = true;
    //    echo "$errstr ($errno)<br />\n";
  } else {
    $error = false;
    $x = json_encode(array('csi18n_xlate_resource' => array('newmark' => $newmark,
							    'language' => $lang,
							    'visibility' => $vis,
							    'translation' => $translation)));
    $x .= "\r\n\r\n";

    $out = "POST /newmarks/$thisPageBelongsToSID/$newmark HTTP/1.1\r\n";
    $out .= "Host: rest.mpsvr.com\r\n";
    $out .= "X-APIKey: $apikey\r\n";
    $out .= "Authorization: Basic " . base64_encode("$un:$pw") . "\r\n";
    $out .= "Content-Type: application/json;v=1.0\r\n";
    $out .= "Content-Length: " . mb_strlen($x) . "\r\n";
    $out .= "Connection: close\r\n\r\n";

    $out .= $x;
    fwrite($fp, $out);
    $received = '';
    while (!feof($fp)) {
      $received .= fgets($fp, 128);
    }
    fclose($fp);

    list($headers, $body) = explode("\r\n\r\n", $received, 2);
    $rv = mb_substr($headers, 9, 3);
    $headers_arr = explode("\r\n", $headers);
    switch($rv){
      //    case (404) : echo "404 - No resource found at that URI"; break;
      //    case (409) : echo "409 - Conflict. Update identical to current"; break;
    case (201) : //201 created ok
    case (301) : //301 moved. POSTing via CORS? Be aware! http://csi18n.mpsvr.com/index.php/Technical_reference#POSTing_duplicates_and_CORS
      $repost = true;
      break;
    case (401) : //401 Unauthorised
      return 401;
    default: 
      echo mb_substr($headers_arr[0], 9); break;
    }
  }
  if ($error === true) {
    echo "Unhandled error connecting to backend<br>\n";
  }
  $loc = '';
  foreach($headers_arr as $header) {
    if (mb_substr($header, 0, 10) === 'Location: ') {
      $loc = mb_substr($header, 10);
    }
    if (mb_substr($header, 0, 20) === 'X-CORS-201-not-301: ') {
      $repost = true;
    }
  }
  return $loc;
}

//
// We need to know the uploader's SID is in order to construct the 
// permalink. We also need to know the record's CRID for the same
// reason.
// This code makes me want to wash with wire wool. Never return more 
// than one value from a subroutine, kids
function handlePOSTResponse_v2($rv, &$qs_crid_arr, &$uploadersid) {
  if ($rv === '') {
    // handle problems and repeating translations. Maintain query
    // string place meaning by adding an empty crid
    $qs_crid_arr[] = '';
    return; 
  }
  if ($rv >= 400) return;
  if (preg_match('|.*/([^/]*)$|', $rv, $matches) === 1) {
    $qs_crid_arr[] = $matches[1];
    preg_match('|.*/xlates/\d+,([^/]*)/.*$|', $rv, $matches);
    if ($uploadersid === '') {
      $uploadersid = $matches[1];
    }
  }
  return $rv;
}

/////////////
/////////////

//
// main body of code
//

/////////////
/////////////

$un = $pw = $lang = $opt_ignore_blanks = null;

//
// Discover our visitor's AL for later use, specifically
// also obtain user's most likely lang as first highest 
// priority lang in the AL string: we'll use it to preload
// the upload form.
// 
$al = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$al_arr = explode(',', $al);
$al_pref_arr = array();
$maxq = 0;
$seen_en = $seen_any_en = false;
foreach($al_arr as $l) {
  $l_arr = explode(';', $l);
  $lang = array_shift($l_arr);
  $q = 1;
  foreach($l_arr as $param) {
    $param_arr = explode('=', $param);
    if (sizeof($param_arr) === 2) {
      if ($param_arr[0] === 'q') {
	$q = $param_arr[1];
      }
    }
  }
  $q = (int) ($q * 1000);
  if ($q > $maxq) {
    $maxq = $q;
  }
  $al_pref_arr[$q][] = $lang;
  if ($lang === 'en') {
    $seen_en = true;
  } elseif (mb_substr($lang, 0, 3) === 'en-') {
    $seen_any_en = true;
  }
}
$probable_lang = $al_pref_arr[$maxq][0]; //$first_lang;
$probable_lang = htmlentities($probable_lang);
// 
// Does everyone use en? No: 
// Internet Explorer v11/Win8 only provides "en-GB" here, so user gets
// British English but broad English "en" is ignored.
// Safari 8.0.3/OSXv10.10.2 only provides "en-us" here, so even though 
// machine, install and user is British, British English is ignored, as
// is broad English, AND standard American English (where
// uppercase subtags are recommended per IETF so en-US not en-us)
//
// What I'll do is, if en-* appears and en doesn't, is add en;q=0.001.
if ($seen_any_en === true && $seen_en === false) {
  $hal_lang = "$al,en;q=0.001";
} else {
  $hal_lang = $al;
}

// 
// investigate if user is trying to upload for a permalink
// ta_ as in textarea
//-- Doc: handle POSTS
if (array_key_exists('ignore_blanks', $_POST)) $opt_ignore_blanks = $_POST['ignore_blanks'];
if (array_key_exists('u_username', $_POST)) $un = $_POST['u_username'];
if (array_key_exists('u_password', $_POST)) $pw = $_POST['u_password'];
if (array_key_exists('u_languagecode', $_POST)) $lang = urlencode($_POST['u_languagecode']);

$upload_flds_arr = array();

if ($frame_array[0] > 0 && array_key_exists('ta_fr0001', $_POST)) $upload_flds_arr['fr0001'] = $_POST['ta_fr0001'];
if ($frame_array[0] > 0 && array_key_exists('ta_fr0002', $_POST)) $upload_flds_arr['fr0002'] = $_POST['ta_fr0002'];

if ($frame_array[1] >  0 && array_key_exists('ta_fr0101', $_POST)) $upload_flds_arr['fr0101'] = $_POST['ta_fr0101'];
if ($frame_array[1] >  1 && array_key_exists('ta_fr0102', $_POST)) $upload_flds_arr['fr0102'] = $_POST['ta_fr0102'];
if ($frame_array[1] >  2 && array_key_exists('ta_fr0103', $_POST)) $upload_flds_arr['fr0103'] = $_POST['ta_fr0103'];
if ($frame_array[1] >  3 && array_key_exists('ta_fr0104', $_POST)) $upload_flds_arr['fr0104'] = $_POST['ta_fr0104'];
if ($frame_array[1] >  4 && array_key_exists('ta_fr0105', $_POST)) $upload_flds_arr['fr0105'] = $_POST['ta_fr0105'];
if ($frame_array[1] >  5 && array_key_exists('ta_fr0106', $_POST)) $upload_flds_arr['fr0106'] = $_POST['ta_fr0106'];
if ($frame_array[1] >  6 && array_key_exists('ta_fr0107', $_POST)) $upload_flds_arr['fr0107'] = $_POST['ta_fr0107'];
if ($frame_array[1] >  7 && array_key_exists('ta_fr0108', $_POST)) $upload_flds_arr['fr0108'] = $_POST['ta_fr0108'];

if ($frame_array[1] >  8 && array_key_exists('ta_fr0109', $_POST)) $upload_flds_arr['fr0109'] = $_POST['ta_fr0109'];
if ($frame_array[1] >  9 && array_key_exists('ta_fr0110', $_POST)) $upload_flds_arr['fr0110'] = $_POST['ta_fr0110'];
if ($frame_array[1] > 10 && array_key_exists('ta_fr0111', $_POST)) $upload_flds_arr['fr0111'] = $_POST['ta_fr0111'];
if ($frame_array[1] > 11 && array_key_exists('ta_fr0112', $_POST)) $upload_flds_arr['fr0112'] = $_POST['ta_fr0112'];
if ($frame_array[1] > 12 && array_key_exists('ta_fr0113', $_POST)) $upload_flds_arr['fr0113'] = $_POST['ta_fr0113'];
if ($frame_array[1] > 13 && array_key_exists('ta_fr0114', $_POST)) $upload_flds_arr['fr0114'] = $_POST['ta_fr0114'];
if ($frame_array[1] > 14 && array_key_exists('ta_fr0115', $_POST)) $upload_flds_arr['fr0115'] = $_POST['ta_fr0115'];
if ($frame_array[1] > 15 && array_key_exists('ta_fr0116', $_POST)) $upload_flds_arr['fr0116'] = $_POST['ta_fr0116'];

if ($frame_array[1] > 16 && array_key_exists('ta_fr0117', $_POST)) $upload_flds_arr['fr0117'] = $_POST['ta_fr0117'];
if ($frame_array[1] > 17 && array_key_exists('ta_fr0118', $_POST)) $upload_flds_arr['fr0118'] = $_POST['ta_fr0118'];
if ($frame_array[1] > 18 && array_key_exists('ta_fr0119', $_POST)) $upload_flds_arr['fr0119'] = $_POST['ta_fr0119'];
if ($frame_array[1] > 19 && array_key_exists('ta_fr0120', $_POST)) $upload_flds_arr['fr0120'] = $_POST['ta_fr0120'];
if ($frame_array[1] > 20 && array_key_exists('ta_fr0121', $_POST)) $upload_flds_arr['fr0121'] = $_POST['ta_fr0121'];
if ($frame_array[1] > 21 && array_key_exists('ta_fr0122', $_POST)) $upload_flds_arr['fr0122'] = $_POST['ta_fr0122'];
if ($frame_array[1] > 22 && array_key_exists('ta_fr0123', $_POST)) $upload_flds_arr['fr0123'] = $_POST['ta_fr0123'];
if ($frame_array[1] > 23 && array_key_exists('ta_fr0124', $_POST)) $upload_flds_arr['fr0124'] = $_POST['ta_fr0124'];

if ($frame_array[1] > 24 && array_key_exists('ta_fr0125', $_POST)) $upload_flds_arr['fr0125'] = $_POST['ta_fr0125'];
if ($frame_array[1] > 25 && array_key_exists('ta_fr0126', $_POST)) $upload_flds_arr['fr0126'] = $_POST['ta_fr0126'];
if ($frame_array[1] > 26 && array_key_exists('ta_fr0127', $_POST)) $upload_flds_arr['fr0127'] = $_POST['ta_fr0127'];
if ($frame_array[1] > 27 && array_key_exists('ta_fr0128', $_POST)) $upload_flds_arr['fr0128'] = $_POST['ta_fr0128'];
if ($frame_array[1] > 28 && array_key_exists('ta_fr0129', $_POST)) $upload_flds_arr['fr0129'] = $_POST['ta_fr0129'];
if ($frame_array[1] > 29 && array_key_exists('ta_fr0130', $_POST)) $upload_flds_arr['fr0130'] = $_POST['ta_fr0130'];
if ($frame_array[1] > 30 && array_key_exists('ta_fr0131', $_POST)) $upload_flds_arr['fr0131'] = $_POST['ta_fr0131'];
if ($frame_array[1] > 31 && array_key_exists('ta_fr0132', $_POST)) $upload_flds_arr['fr0132'] = $_POST['ta_fr0132'];

if ($frame_array[1] > 32 && array_key_exists('ta_fr0133', $_POST)) $upload_flds_arr['fr0133'] = $_POST['ta_fr0133'];
if ($frame_array[1] > 33 && array_key_exists('ta_fr0134', $_POST)) $upload_flds_arr['fr0134'] = $_POST['ta_fr0134'];
if ($frame_array[1] > 34 && array_key_exists('ta_fr0135', $_POST)) $upload_flds_arr['fr0135'] = $_POST['ta_fr0135'];
if ($frame_array[1] > 35 && array_key_exists('ta_fr0136', $_POST)) $upload_flds_arr['fr0136'] = $_POST['ta_fr0136'];
if ($frame_array[1] > 36 && array_key_exists('ta_fr0137', $_POST)) $upload_flds_arr['fr0137'] = $_POST['ta_fr0137'];
if ($frame_array[1] > 37 && array_key_exists('ta_fr0138', $_POST)) $upload_flds_arr['fr0138'] = $_POST['ta_fr0138'];
if ($frame_array[1] > 38 && array_key_exists('ta_fr0139', $_POST)) $upload_flds_arr['fr0139'] = $_POST['ta_fr0139'];
if ($frame_array[1] > 39 && array_key_exists('ta_fr0140', $_POST)) $upload_flds_arr['fr0140'] = $_POST['ta_fr0140'];

if ($frame_array[1] > 40 && array_key_exists('ta_fr0141', $_POST)) $upload_flds_arr['fr0141'] = $_POST['ta_fr0141'];
if ($frame_array[1] > 41 && array_key_exists('ta_fr0142', $_POST)) $upload_flds_arr['fr0142'] = $_POST['ta_fr0142'];
if ($frame_array[1] > 42 && array_key_exists('ta_fr0143', $_POST)) $upload_flds_arr['fr0143'] = $_POST['ta_fr0143'];
if ($frame_array[1] > 43 && array_key_exists('ta_fr0144', $_POST)) $upload_flds_arr['fr0144'] = $_POST['ta_fr0144'];
if ($frame_array[1] > 44 && array_key_exists('ta_fr0145', $_POST)) $upload_flds_arr['fr0145'] = $_POST['ta_fr0145'];
if ($frame_array[1] > 45 && array_key_exists('ta_fr0146', $_POST)) $upload_flds_arr['fr0146'] = $_POST['ta_fr0146'];
if ($frame_array[1] > 46 && array_key_exists('ta_fr0147', $_POST)) $upload_flds_arr['fr0147'] = $_POST['ta_fr0147'];
if ($frame_array[1] > 47 && array_key_exists('ta_fr0148', $_POST)) $upload_flds_arr['fr0148'] = $_POST['ta_fr0148'];

// frame 02
if ($frame_array[2] > 0 && array_key_exists('ta_fr0201', $_POST)) $upload_flds_arr['fr0201'] = $_POST['ta_fr0201'];
if ($frame_array[2] > 1 && array_key_exists('ta_fr0202', $_POST)) $upload_flds_arr['fr0202'] = $_POST['ta_fr0202'];
if ($frame_array[2] > 2 && array_key_exists('ta_fr0203', $_POST)) $upload_flds_arr['fr0203'] = $_POST['ta_fr0203'];
if ($frame_array[2] > 3 && array_key_exists('ta_fr0204', $_POST)) $upload_flds_arr['fr0204'] = $_POST['ta_fr0204'];

if ($frame_array[3] > 0 && array_key_exists('ta_fr0301', $_POST)) $upload_flds_arr['fr0301'] = $_POST['ta_fr0301'];
if ($frame_array[3] > 1 && array_key_exists('ta_fr0302', $_POST)) $upload_flds_arr['fr0302'] = $_POST['ta_fr0302'];
if ($frame_array[3] > 2 && array_key_exists('ta_fr0303', $_POST)) $upload_flds_arr['fr0303'] = $_POST['ta_fr0303'];
if ($frame_array[3] > 3 && array_key_exists('ta_fr0304', $_POST)) $upload_flds_arr['fr0304'] = $_POST['ta_fr0304'];

if ($frame_array[4] > 0 && array_key_exists('ta_fr0401', $_POST)) $upload_flds_arr['fr0401'] = $_POST['ta_fr0401'];
if ($frame_array[4] > 1 && array_key_exists('ta_fr0402', $_POST)) $upload_flds_arr['fr0402'] = $_POST['ta_fr0402'];
if ($frame_array[4] > 2 && array_key_exists('ta_fr0403', $_POST)) $upload_flds_arr['fr0403'] = $_POST['ta_fr0403'];
if ($frame_array[4] > 3 && array_key_exists('ta_fr0404', $_POST)) $upload_flds_arr['fr0404'] = $_POST['ta_fr0404'];

if ($frame_array[5] > 0 && array_key_exists('ta_fr0501', $_POST)) $upload_flds_arr['fr0501'] = $_POST['ta_fr0501'];
if ($frame_array[5] > 1 && array_key_exists('ta_fr0502', $_POST)) $upload_flds_arr['fr0502'] = $_POST['ta_fr0502'];
if ($frame_array[5] > 2 && array_key_exists('ta_fr0503', $_POST)) $upload_flds_arr['fr0503'] = $_POST['ta_fr0503'];
if ($frame_array[5] > 3 && array_key_exists('ta_fr0504', $_POST)) $upload_flds_arr['fr0504'] = $_POST['ta_fr0504'];

if ($frame_array[6] > 0 && array_key_exists('ta_fr0601', $_POST)) $upload_flds_arr['fr0601'] = $_POST['ta_fr0601'];
if ($frame_array[6] > 1 && array_key_exists('ta_fr0602', $_POST)) $upload_flds_arr['fr0602'] = $_POST['ta_fr0602'];
if ($frame_array[6] > 2 && array_key_exists('ta_fr0603', $_POST)) $upload_flds_arr['fr0603'] = $_POST['ta_fr0603'];
if ($frame_array[6] > 3 && array_key_exists('ta_fr0604', $_POST)) $upload_flds_arr['fr0604'] = $_POST['ta_fr0604'];

if ($frame_array[7] > 0 && array_key_exists('ta_fr0701', $_POST)) $upload_flds_arr['fr0701'] = $_POST['ta_fr0701'];
if ($frame_array[7] > 1 && array_key_exists('ta_fr0702', $_POST)) $upload_flds_arr['fr0702'] = $_POST['ta_fr0702'];
if ($frame_array[7] > 2 && array_key_exists('ta_fr0703', $_POST)) $upload_flds_arr['fr0703'] = $_POST['ta_fr0703'];
if ($frame_array[7] > 3 && array_key_exists('ta_fr0704', $_POST)) $upload_flds_arr['fr0704'] = $_POST['ta_fr0704'];

if ($frame_array[8] > 0 && array_key_exists('ta_fr0801', $_POST)) $upload_flds_arr['fr0801'] = $_POST['ta_fr0801'];
if ($frame_array[8] > 1 && array_key_exists('ta_fr0802', $_POST)) $upload_flds_arr['fr0802'] = $_POST['ta_fr0802'];
if ($frame_array[8] > 2 && array_key_exists('ta_fr0803', $_POST)) $upload_flds_arr['fr0803'] = $_POST['ta_fr0803'];
if ($frame_array[8] > 3 && array_key_exists('ta_fr0804', $_POST)) $upload_flds_arr['fr0804'] = $_POST['ta_fr0804'];

//
// If the user is POSTing back the permalink form, look 
// to see that all elements have been returned. If not, then
// it maybe a corruption - discard it all
$allUploadFieldsPresentedF = false;
if ($un !== null && $pw !== null && $lang !== null) {
  //
  // check running throught the uploads_flds_arr seeing if all the
  // fields named using frame_array and balloon_names are non null.
  $allUploadFieldsPresentedF = true;
  $frame = 0;
  foreach($frame_array as $numBalloons) { // 2, 14, 0, 0, 0 ...
    $frame_balloon_names = $balloon_names[$frame]; //fr0001, fr0002 ...
    for($idx = 0; $idx < $numBalloons; $idx++) {
      if (array_key_exists($frame_balloon_names[$idx], $upload_flds_arr)) {
	// array key will not exist when reusing text elements
	if ($upload_flds_arr[$frame_balloon_names[$idx]] === null) {
	  $allUploadFieldsPresentedF = false;
	  break 2;
	}
      }
    }
    $frame++;
  }

  //
  // Only accept for upload if all fields are presented. 
  if ($allUploadFieldsPresentedF === true) {
    $rv = 200; // skyhook no previous failure
    $apikey = '798e31c43d6b9f03aa504a6f88cb4550'; // apikey used by browser to backend
    //
    // qs_crid_arr is used to push() the POSTed crids as they come back.
    // It's shortly used to generate the permalink
    $qs_crid_arr = array();
    //
    // What's the human's sid? No idea until they successfully
    // POST. uploader_sid stays empty until they can
    $uploadersid = '';

    //-- Doc: perform POSTs
    if ($rv !== 401 && $frame_array[0] > 0) {
      $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0001', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid);
    }
    if ($rv !== 401 && $frame_array[0] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0002', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] >  0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0101', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0102', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0103', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0104', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  4) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0105', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  5) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0106', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  6) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0107', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  7) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0108', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] >  8) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0109', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] >  9) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0110', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 10) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0111', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 11) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0112', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 12) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0113', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 13) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0114', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 14) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0115', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 15) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0116', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] > 16) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0117', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 17) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0118', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 18) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0119', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 19) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0120', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 20) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0121', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 21) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0122', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 22) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0123', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 23) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0124', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] > 24) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0125', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 25) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0126', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 26) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0127', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 27) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0128', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 28) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0129', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 29) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0130', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 30) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0131', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 31) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0132', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] > 32) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0133', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 33) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0134', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 34) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0135', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 35) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0136', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 36) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0137', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 37) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0138', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 38) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0139', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 39) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0140', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[1] > 40) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0141', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 41) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0142', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 42) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0143', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 43) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0144', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 44) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0145', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 45) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0146', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 46) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0147', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[1] > 47) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0148', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    // frame 02
    if ($rv !== 401 && $frame_array[2] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0201', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[2] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0202', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[2] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0203', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[2] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0204', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[3] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0301', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[3] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0302', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[3] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0303', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[3] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0304', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[4] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0401', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[4] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0402', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[4] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0403', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[4] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0404', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[5] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0501', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[5] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0502', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[5] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0503', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[5] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0504', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[6] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0601', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[6] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0602', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[6] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0603', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[6] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0604', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[7] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0701', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[7] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0702', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[7] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0703', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[7] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0704', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv !== 401 && $frame_array[8] > 0) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0801', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[8] > 1) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0802', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[8] > 2) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0803', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }
    if ($rv !== 401 && $frame_array[8] > 3) { $rv = post_a_new($un, $pw, $apikey, $nroot, $lang, 'anonymous', $upload_flds_arr, 'fr0804', $opt_ignore_blanks, $thisPageBelongsToSID);
      handlePOSTResponse_v2($rv, $qs_crid_arr, $uploadersid); }

    if ($rv === 401) {
      //
      // if any of the uploads 401, the following uploads should be skipped ifo this message
      echo "<font color=red>The username and/or password is wrong.</font> Please go back and check them!<br><br>\n";
    } else {
      //
      // construct the permalink
      $crid_qs = '';
      foreach($qs_crid_arr as $crid) {
	$crid_qs .= ",$crid";
      }
      if (mb_strlen($crid_qs) > 1) {
	$crid_qs = mb_substr($crid_qs, 1);
      }
      $link = $_SERVER['SCRIPT_URI'] . "?q=$uploadersid,$lang,$crid_qs";
 
      echo "Your changes are at this link, and you can forward it to others!<br>";
      echo "<a href='$link' target=_blank>$link</a><br>";
      echo "<br>";
    }
  }
}
// 
// We either have or couldn't use 'em, so clear 'em
$un = $pw = $lang = $opt_ignore_blanks = null;
unset($upload_flds_arr);

///////////////////////////////////////////////////////////////
//
// Handle if Permalinks, prepare for JavaScript even if not
//
// JavaScript won't know if user is visiting the basic page, 
// or visiting a permalink, so no need for 
// if (visiting_permalink) {...} else {...}

//
// crid_array used to tell javascript which balloons to
// put text in. 
// First, initialise every balloon: null = "do not use".
$crid_array = array(); 
foreach($balloon_names as $frame) {
  foreach($frame as $name) {
    $crid_array[$name] = null;
  }
}

//
// Second, if the balloon is live according to 
// the frame array, overide the above: '' = obtain semirandom translation
for($idx = 0; $idx < sizeof($frame_array); $idx ++) {
  $numBalloons = $frame_array[$idx];
  for($a = 0; $a < $numBalloons; $a++) {
    $name = $balloon_names[$idx][$a];
    $crid_array[$name] = "''";
  }
}

//
// Third, if the user has used a permalink with specific
// CRIDs, then each specific CRID overides the above
$qs = $_SERVER['QUERY_STRING'];
$qs_arr = explode('=', $qs);
$guest_params = '';
$g_uploadersid = $g_lang = null;
$qs_used = false;
if (sizeof($qs_arr) === 2 && $qs_arr[0] === 'q') {
  $qs_used = true;
  $guest_params = $qs_arr[1];
  $guest_params_arr = explode(',', $guest_params);
  $numTextElements = 0;
  foreach($frame_array as $numBalloons) {
    $numTextElements += $numBalloons;
  }
  $szShouldBe = 2 + $numTextElements; // uploader_sid, language_code plus one crid each text field
  if (sizeof($guest_params_arr) === $szShouldBe) { 
    // then this probably a permalink of form xkcd/dress_colour/q=uploadersid,lang,crid1,crid2...cridN
    $g_uploadersid = $guest_params_arr[0];
    $g_lang = $guest_params_arr[1];
    $placed = 2;
    for($frames = 0; $frames < sizeof($frame_array); $frames++) {
      for($idx = 0; $idx < $frame_array[$frames]; $idx++) {
	$val = $guest_params_arr[$placed];
	if ($val === '') { // when query string has ",,"
	  $val = "''";
	}
	if (!is_numeric($val)) { //when query string as ",words,"
	  $val = "''";
	}
	$crid_array[$balloon_names[$frames][$idx]] = $val;
	if ($placed === $szShouldBe) {
	  break 2;
	}
	$placed++;
      }
    }
  } elseif (sizeof($guest_params_arr) === 1) {
    // then this probably a link of form xkcd/dress_colour/?q=lang
    $g_lang = $guest_params_arr[0];
  }
}

//
// Make sure all relevant CRID assigns took place. If not, reset them all
// and tell the user. Continue as if visiting basic page.
$allRelevantF = true;
for($frames = 0; $frames < sizeof($frame_array); $frames++) {
  for($idx = 0; $idx < $frame_array[$frames]; $idx++) {
    if ($crid_array[$balloon_names[$frames][$idx]] === 'null') {
      $allRelevantF = false;
      break 2;
    }
  }
}

// 
// bound checks
$permalinkF = 'false';
if ($g_uploadersid !== null && $g_lang !== null && $allRelevantF === true) {
  // permalink
  // leave all in place
  $permalinkF = 'true';
} elseif ($g_lang !== null) {
  // language-only link
  // knock out all but language
  $qs_used = false;
  for($idx = 0; $idx < sizeof($frame_array); $idx ++) {
    $numBalloons = $frame_array[$idx];
    for($a = 0; $a < $numBalloons; $a++) {
      $name = $balloon_names[$idx][$a];
      $crid_array[$name] = "''";
    }
  }
  $g_uploader_sid = null;

} else {
  // everything else. Error and knock out all
  if ($qs_used === true) {
    echo "Invalid permalink :-(<br>Here's the original page instead :-D<br>\n";
  }
  $qs_used = false;
  for($idx = 0; $idx < sizeof($frame_array); $idx ++) {
    $numBalloons = $frame_array[$idx];
    for($a = 0; $a < $numBalloons; $a++) {
      $name = $balloon_names[$idx][$a];
      $crid_array[$name] = "''";
    }
  }
  $g_uploader_sid = null;
  $g_lang = null;
}

//
// JavaScript in the browser now needs to be generated according to what
// we know vis-a-vis permalink or not
echo "<script type='text/javascript'>\n";
echo "function getCRIDArrayFromPHP(crid_array) {\n";

// null:    not used in this xkcd
// "''":    obtain from /newmarks/visitsid/newmark according to Accept-Language
// numeric: obtain from /xlaets/visitsid,uploader/newmark/language/visibility/crid
if ($crid_array['fr0001'] !== null) echo " loadArray(crid_array, 0, 'fr0001', ${crid_array['fr0001']});\n";
if ($crid_array['fr0002'] !== null) echo " loadArray(crid_array, 0, 'fr0002', ${crid_array['fr0002']});\n";

if ($crid_array['fr0101'] !== null) echo " loadArray(crid_array, 1, 'fr0101', ${crid_array['fr0101']});\n";
if ($crid_array['fr0102'] !== null) echo " loadArray(crid_array, 1, 'fr0102', ${crid_array['fr0102']});\n";
if ($crid_array['fr0103'] !== null) echo " loadArray(crid_array, 1, 'fr0103', ${crid_array['fr0103']});\n";
if ($crid_array['fr0104'] !== null) echo " loadArray(crid_array, 1, 'fr0104', ${crid_array['fr0104']});\n";
if ($crid_array['fr0105'] !== null) echo " loadArray(crid_array, 1, 'fr0105', ${crid_array['fr0105']});\n";
if ($crid_array['fr0106'] !== null) echo " loadArray(crid_array, 1, 'fr0106', ${crid_array['fr0106']});\n";
if ($crid_array['fr0107'] !== null) echo " loadArray(crid_array, 1, 'fr0107', ${crid_array['fr0107']});\n";
if ($crid_array['fr0108'] !== null) echo " loadArray(crid_array, 1, 'fr0108', ${crid_array['fr0108']});\n";

if ($crid_array['fr0109'] !== null) echo " loadArray(crid_array, 1, 'fr0109', ${crid_array['fr0109']});\n";
if ($crid_array['fr0110'] !== null) echo " loadArray(crid_array, 1, 'fr0110', ${crid_array['fr0110']});\n";
if ($crid_array['fr0111'] !== null) echo " loadArray(crid_array, 1, 'fr0111', ${crid_array['fr0111']});\n";
if ($crid_array['fr0112'] !== null) echo " loadArray(crid_array, 1, 'fr0112', ${crid_array['fr0112']});\n";
if ($crid_array['fr0113'] !== null) echo " loadArray(crid_array, 1, 'fr0113', ${crid_array['fr0113']});\n";
if ($crid_array['fr0114'] !== null) echo " loadArray(crid_array, 1, 'fr0114', ${crid_array['fr0114']});\n";
if ($crid_array['fr0115'] !== null) echo " loadArray(crid_array, 1, 'fr0115', ${crid_array['fr0115']});\n";
if ($crid_array['fr0116'] !== null) echo " loadArray(crid_array, 1, 'fr0116', ${crid_array['fr0116']});\n";

if ($crid_array['fr0117'] !== null) echo " loadArray(crid_array, 1, 'fr0117', ${crid_array['fr0117']});\n";
if ($crid_array['fr0118'] !== null) echo " loadArray(crid_array, 1, 'fr0118', ${crid_array['fr0118']});\n";
if ($crid_array['fr0119'] !== null) echo " loadArray(crid_array, 1, 'fr0119', ${crid_array['fr0119']});\n";
if ($crid_array['fr0120'] !== null) echo " loadArray(crid_array, 1, 'fr0120', ${crid_array['fr0120']});\n";
if ($crid_array['fr0121'] !== null) echo " loadArray(crid_array, 1, 'fr0121', ${crid_array['fr0121']});\n";
if ($crid_array['fr0122'] !== null) echo " loadArray(crid_array, 1, 'fr0122', ${crid_array['fr0122']});\n";
if ($crid_array['fr0123'] !== null) echo " loadArray(crid_array, 1, 'fr0123', ${crid_array['fr0123']});\n";
if ($crid_array['fr0124'] !== null) echo " loadArray(crid_array, 1, 'fr0124', ${crid_array['fr0124']});\n";

if ($crid_array['fr0125'] !== null) echo " loadArray(crid_array, 1, 'fr0125', ${crid_array['fr0125']});\n";
if ($crid_array['fr0126'] !== null) echo " loadArray(crid_array, 1, 'fr0126', ${crid_array['fr0126']});\n";
if ($crid_array['fr0127'] !== null) echo " loadArray(crid_array, 1, 'fr0127', ${crid_array['fr0127']});\n";
if ($crid_array['fr0128'] !== null) echo " loadArray(crid_array, 1, 'fr0128', ${crid_array['fr0128']});\n";
if ($crid_array['fr0129'] !== null) echo " loadArray(crid_array, 1, 'fr0129', ${crid_array['fr0129']});\n";
if ($crid_array['fr0130'] !== null) echo " loadArray(crid_array, 1, 'fr0130', ${crid_array['fr0130']});\n";
if ($crid_array['fr0131'] !== null) echo " loadArray(crid_array, 1, 'fr0131', ${crid_array['fr0131']});\n";
if ($crid_array['fr0132'] !== null) echo " loadArray(crid_array, 1, 'fr0132', ${crid_array['fr0132']});\n";

if ($crid_array['fr0133'] !== null) echo " loadArray(crid_array, 1, 'fr0133', ${crid_array['fr0133']});\n";
if ($crid_array['fr0134'] !== null) echo " loadArray(crid_array, 1, 'fr0134', ${crid_array['fr0134']});\n";
if ($crid_array['fr0135'] !== null) echo " loadArray(crid_array, 1, 'fr0135', ${crid_array['fr0135']});\n";
if ($crid_array['fr0136'] !== null) echo " loadArray(crid_array, 1, 'fr0136', ${crid_array['fr0136']});\n";
if ($crid_array['fr0137'] !== null) echo " loadArray(crid_array, 1, 'fr0137', ${crid_array['fr0137']});\n";
if ($crid_array['fr0138'] !== null) echo " loadArray(crid_array, 1, 'fr0138', ${crid_array['fr0138']});\n";
if ($crid_array['fr0139'] !== null) echo " loadArray(crid_array, 1, 'fr0139', ${crid_array['fr0139']});\n";
if ($crid_array['fr0140'] !== null) echo " loadArray(crid_array, 1, 'fr0140', ${crid_array['fr0140']});\n";

if ($crid_array['fr0141'] !== null) echo " loadArray(crid_array, 1, 'fr0141', ${crid_array['fr0141']});\n";
if ($crid_array['fr0142'] !== null) echo " loadArray(crid_array, 1, 'fr0142', ${crid_array['fr0142']});\n";
if ($crid_array['fr0143'] !== null) echo " loadArray(crid_array, 1, 'fr0143', ${crid_array['fr0143']});\n";
if ($crid_array['fr0144'] !== null) echo " loadArray(crid_array, 1, 'fr0144', ${crid_array['fr0144']});\n";
if ($crid_array['fr0145'] !== null) echo " loadArray(crid_array, 1, 'fr0145', ${crid_array['fr0145']});\n";
if ($crid_array['fr0146'] !== null) echo " loadArray(crid_array, 1, 'fr0146', ${crid_array['fr0146']});\n";
if ($crid_array['fr0147'] !== null) echo " loadArray(crid_array, 1, 'fr0147', ${crid_array['fr0147']});\n";
if ($crid_array['fr0148'] !== null) echo " loadArray(crid_array, 1, 'fr0148', ${crid_array['fr0148']});\n";

// frame 02
if ($crid_array['fr0201'] !== null) echo " loadArray(crid_array, 2, 'fr0201', ${crid_array['fr0201']});\n";
if ($crid_array['fr0202'] !== null) echo " loadArray(crid_array, 2, 'fr0202', ${crid_array['fr0202']});\n";
if ($crid_array['fr0203'] !== null) echo " loadArray(crid_array, 2, 'fr0203', ${crid_array['fr0203']});\n";
if ($crid_array['fr0204'] !== null) echo " loadArray(crid_array, 2, 'fr0204', ${crid_array['fr0204']});\n";

if ($crid_array['fr0301'] !== null) echo " loadArray(crid_array, 3, 'fr0301', ${crid_array['fr0301']});\n";
if ($crid_array['fr0302'] !== null) echo " loadArray(crid_array, 3, 'fr0302', ${crid_array['fr0302']});\n";
if ($crid_array['fr0303'] !== null) echo " loadArray(crid_array, 3, 'fr0303', ${crid_array['fr0303']});\n";
if ($crid_array['fr0304'] !== null) echo " loadArray(crid_array, 3, 'fr0304', ${crid_array['fr0304']});\n";

if ($crid_array['fr0401'] !== null) echo " loadArray(crid_array, 4, 'fr0401', ${crid_array['fr0401']});\n";
if ($crid_array['fr0402'] !== null) echo " loadArray(crid_array, 4, 'fr0402', ${crid_array['fr0402']});\n";
if ($crid_array['fr0403'] !== null) echo " loadArray(crid_array, 4, 'fr0403', ${crid_array['fr0403']});\n";
if ($crid_array['fr0404'] !== null) echo " loadArray(crid_array, 4, 'fr0404', ${crid_array['fr0404']});\n";

if ($crid_array['fr0501'] !== null) echo " loadArray(crid_array, 5, 'fr0501', ${crid_array['fr0501']});\n";
if ($crid_array['fr0502'] !== null) echo " loadArray(crid_array, 5, 'fr0502', ${crid_array['fr0502']});\n";
if ($crid_array['fr0503'] !== null) echo " loadArray(crid_array, 5, 'fr0503', ${crid_array['fr0503']});\n";
if ($crid_array['fr0504'] !== null) echo " loadArray(crid_array, 5, 'fr0504', ${crid_array['fr0504']});\n";

if ($crid_array['fr0601'] !== null) echo " loadArray(crid_array, 6, 'fr0601', ${crid_array['fr0601']});\n";
if ($crid_array['fr0602'] !== null) echo " loadArray(crid_array, 6, 'fr0602', ${crid_array['fr0602']});\n";
if ($crid_array['fr0603'] !== null) echo " loadArray(crid_array, 6, 'fr0603', ${crid_array['fr0603']});\n";
if ($crid_array['fr0604'] !== null) echo " loadArray(crid_array, 6, 'fr0604', ${crid_array['fr0604']});\n";

if ($crid_array['fr0701'] !== null) echo " loadArray(crid_array, 7, 'fr0701', ${crid_array['fr0701']});\n";
if ($crid_array['fr0702'] !== null) echo " loadArray(crid_array, 7, 'fr0702', ${crid_array['fr0702']});\n";
if ($crid_array['fr0703'] !== null) echo " loadArray(crid_array, 7, 'fr0703', ${crid_array['fr0703']});\n";
if ($crid_array['fr0704'] !== null) echo " loadArray(crid_array, 7, 'fr0704', ${crid_array['fr0704']});\n";

if ($crid_array['fr0801'] !== null) echo " loadArray(crid_array, 8, 'fr0801', ${crid_array['fr0801']});\n";
if ($crid_array['fr0802'] !== null) echo " loadArray(crid_array, 8, 'fr0802', ${crid_array['fr0802']});\n";
if ($crid_array['fr0803'] !== null) echo " loadArray(crid_array, 8, 'fr0803', ${crid_array['fr0803']});\n";
if ($crid_array['fr0804'] !== null) echo " loadArray(crid_array, 8, 'fr0804', ${crid_array['fr0804']});\n";
echo "}\n";
//
// JS needs a copy of the frame_array rewritten into JS syntax
$js_frame_array = '';
reset($frame_array);
foreach($frame_array as $element) {
  $js_frame_array .= ', ' . $element;
}
$js_frame_array = substr($js_frame_array, 2);

// 
// also teach JavaScript about defaults it'll have
// derived from this file
echo "var global_g_visitsid = '$thisPageBelongsToSID';  // SID of user who created the page
var global_g_uploadersid = '$g_uploadersid';
var global_g_lang = '$g_lang';
var global_container_width = '$container_width';  // width of xkcd image
var global_container_height = '$container_height';// height of grey box
var global_frame_array = [$js_frame_array]; // el[0] is always 2, el[1-8] how many translations in each frame
var global_permalink = '$permalinkF';
</script>\n";

//
// include javascript common to all pages (implementing service
// and translation menus, for example)
include_once('common_javascript_v3.html');

//
// Include per-xkcd styles, javascript and body
include_once('index.html');
?>
