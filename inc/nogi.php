<?php
// Closes the frame that inc/head.php opens. Some shells include this file twice (down.php, then end.php): print it once.
if (empty($GLOBALS['lx_frame_closed'])) {
    $GLOBALS['lx_frame_closed'] = true;
    echo "</div></td></tr></table>\n</div>\n";
}
