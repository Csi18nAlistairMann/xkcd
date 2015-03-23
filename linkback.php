<?php
/*
  Not everybody understands ~~house music~~ csi18n is
  designed to be usable in their own projects. Rotate among
  DYK suggestions here
 */
$suggs_arr = array('<a href="http://csi18n.mpsvr.com/index.php/Xkcd">Learn more about this translation experiment!</a>',
		   'Redditors! Found a security flaw? PM me at <a href="http://www.reddit.com/message/compose/?to=Alistair_Mann">/u/Alistair_Mann</a>',
		   'GitHub! <a href="https://github.com/Csi18nAlistairMann/xkcd">A repo of these example pages can be found there</a>', 
		   );
 
$choice = $suggs_arr[mt_rand(0, sizeof($suggs_arr) - 1)];
echo "<font size=2>$choice</font><br>";
?>