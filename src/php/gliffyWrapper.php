<?php

# Include the Gliffy Client Library code.  Assumes we have a config.php object in place 
$gliffyPath = 'src'; 
set_include_path( get_include_path() . PATH_SEPARATOR . $gliffyPath); 
require_once("Gliffy.php"); 

# Load a Gliffy context object.
session_start;
session_register("GliffyObj");
if (empty($GliffyObj)) {
    $GliffyObj = new Gliffy("lees");
}
$GliffyObj->updateToken();

# The id of the diagram
$diagramId =  $_REQUEST['did']; 

# Compute the path if the page we want to return to
$returnPage = str_replace( 'gliffyWrapper.php', 'closeGliffy.php?diagramId='.$diagramId,  $_SERVER['PHP_SELF'] );
$returnURL = "http://" . $_SERVER['SERVER_NAME'] . $returnPage;

# Get the diagram editor link
$diagramEditorLink = $GliffyObj->getEditDiagramLink($diagramId, $returnURL ,"Back to WIKI");

?>
<html> 
    <head>
        <script type="text/javascript">
            // After the user is done editing, we want to refresh the page that contains
            // the diagram image so that the new image will show up
            function returnToHomeApplication() {
                opener.location.reload();
            }
        </script>
    </head>
    <body onbeforeunload="returnToHomeApplication();">
        <iframe src ="<?php echo $diagramEditorLink ?>" width="100%" height="100%" frameborder="0" marginheight="0" marginwidth="0">
            <p>Your browser does not support iframes.</p>
        </iframe>
    </body>
</html> 
