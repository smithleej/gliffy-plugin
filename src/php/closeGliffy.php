<?php

# Include the Gliffy Client Library code.  Assumes we have a config.php object in place
$gliffyPath = 'src';
set_include_path( get_include_path() . PATH_SEPARATOR . $gliffyPath);
require_once("Gliffy.php");
require_once("GliffyLog.php");

global $_GLIFFY_logLevel;
global $_GLIFFY_logTo_error_log;
$logger = new GliffyLog($_GLIFFY_logLevel,$_GLIFFY_logTo_error_log);
$logger->debug("*** closeGliffy called");

session_start;
session_register("GliffyObj");
if (empty($GliffyObj)) {
    $logger->debug("*** gliffyObj empty");
    $GliffyObj = new Gliffy("lees");
}

$logger->debug("*** updating token");
$GliffyObj->updateToken();
$logger->debug("*** updated token");

$diagramId = $_GET['diagramId'];
$imageName = (string)$diagramId . ".png";
$fullImagePath = "/var/lib/dokuwiki/data/media/gliffy/" . $imageName;
$count = 1;
while ($count <= 5) {
    try {
        $logger->debug("*** getting image");
        $GliffyObj->getDiagramAsImage($diagramId,Gliffy::MIME_TYPE_PNG,$fullImagePath);
        $logger->debug("*** got image");
        break;

    } catch (Exception $ex) {
        $logger->debug("*** error getting image, retry attempt: " . $count);
        $count += 1;
    }
}

?>
<html>
    <head>
        <script type="text/javascript">
            function close() {
                //Close the gliffyWrapper.php window since the user is done editing the drawing
                window.parent.close();
            }
        </script>
    </head>
    <body onload="close();">
    </body>
</html>
