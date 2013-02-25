<?php

# Include the Gliffy Client Library code.  Assumes we have a config.php object in place
$gliffyPath = 'src';
set_include_path( get_include_path() . PATH_SEPARATOR . $gliffyPath);
require_once("Gliffy.php");
require_once("GliffyLog.php");

global $_GLIFFY_logLevel;
global $_GLIFFY_logTo_error_log;
$logger = new GliffyLog($_GLIFFY_logLevel,$_GLIFFY_logTo_error_log);
$logger->debug("*** updateDiagram called");

session_start;
session_register("GliffyObj");
if (empty($GliffyObj)) {
    $logger->debug("*** gliffyObj empty");
    $GliffyObj = new Gliffy("lees");
}

$logger->debug("*** updating token");
$GliffyObj->updateToken();
$logger->debug("*** updated token");

$diagramId = $_GET['did'];
$imageName = (string)$diagramId . ".png";
$fullImagePath = "/var/lib/dokuwiki/data/media/gliffy/" . $imageName;
$logger->debug("*** fullImagePath: " . $fullImagePath);
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
            // After the user is done editing, we want to refresh the page that contains
            // the diagram image so that the new image will show up
            function updateDiagramInWiki() {
                opener.location.reload();
            }
        </script>
    </head>
    <body onbeforeunload="updateDiagramInWiki();">
        <h1>Diagram image updated, please close this page.</h1>
    </body>
</html>
