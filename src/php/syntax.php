<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
/** Include the Gliffy Client Library code.  Assumes we have a config.php object in place */
require_once("Gliffy.php");
require_once("GliffyLog.php");

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_gliffy extends DokuWiki_Syntax_Plugin {

    private $_logger;

    public function syntax_plugin_gliffy() {
        global $_GLIFFY_logLevel;
        global $_GLIFFY_logTo_error_log;
        $logger = new GliffyLog($_GLIFFY_logLevel,$_GLIFFY_logTo_error_log);
        $this->_logger = new GliffyLog($_GLIFFY_logLevel,$_GLIFFY_logTo_error_log);

        $this->_logger->debug("Created gliffy syntax plugin");
    }

   /**
    * Get an associative array with plugin info.
    *
    * <p>
    * The returned array holds the following fields:
    * <dl>
    * <dt>author</dt><dd>Author of the plugin</dd>
    * <dt>email</dt><dd>Email address to contact the author</dd>
    * <dt>date</dt><dd>Last modified date of the plugin in
    * <tt>YYYY-MM-DD</tt> format</dd>
    * <dt>name</dt><dd>Name of the plugin</dd>
    * <dt>desc</dt><dd>Short description of the plugin (Text only)</dd>
    * <dt>url</dt><dd>Website with more information on the plugin
    * (eg. syntax description)</dd>
    * </dl>
    * @param none
    * @return Array Information about this plugin class.
    * @public
    * @static
    */
    function getInfo(){
        return array(
            'author' => 'me',
            'email'  => 'me@somewhere.com',
            'date'   => '20yy-mm-dd',
            'name'   => 'Test Plugin',
            'desc'   => 'Testing 1, 2, 3 ...',
            'url'    => 'http://www.dokuwiki.org/plugin:test',
        );
    }

   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType(){
        return 'substition';
    }

   /**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>6</tt>.
    * @public
    * @static
    */
    function getSort(){
        return 999;
    }

   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<diag.*/>',$mode,'plugin_gliffy');
    }

   /**
    * Handler to prepare matched data for the rendering process.
    *
    * <p>
    * The <tt>$aState</tt> parameter gives the type of pattern
    * which triggered the call to this method:
    * </p>
    * <dl>
    * <dt>DOKU_LEXER_ENTER</dt>
    * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
    * <dt>DOKU_LEXER_MATCHED</dt>
    * <dd>a pattern set by <tt>addPattern()</tt></dd>
    * <dt>DOKU_LEXER_EXIT</dt>
    * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
    * <dt>DOKU_LEXER_SPECIAL</dt>
    * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
    * <dt>DOKU_LEXER_UNMATCHED</dt>
    * <dd>ordinary text encountered within the plugin's syntax mode
    * which doesn't match any pattern.</dd>
    * </dl>
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    function handle($match, $state, $pos, &$handler){
        $this->_logger->debug("*** match: " . $match);
        $diagramToDisplay = substr($match, 6, -2);
        $this->_logger->debug("*** diagramToDisplay: " . $diagramToDisplay);

        return array($state, $diagramToDisplay);
    }

   /**
    * Handle the actual output creation.
    *
    * <p>
    * The method checks for the given <tt>$aFormat</tt> and returns
    * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
    * contains a reference to the renderer object which is currently
    * handling the rendering. The contents of <tt>$aData</tt> is the
    * return value of the <tt>handle()</tt> method.
    * </p>
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml') {
	        list($state,$diagramToDisplay) = $data;
            global $_GLIFFY_root;

            session_start;
            session_register("GliffyObj");
            if (empty($GliffyObj)) {
                $GliffyObj = new Gliffy("lees");
            }

            $GliffyObj->updateToken();
            $diagramId = $this->getDiagramId($diagramToDisplay,$GliffyObj);

            $imageName = (string)$diagramId . ".png";
            $fullImagePath = "/var/lib/dokuwiki/data/media/gliffy/" . $imageName;

            if (!file_exists($fullImagePath)) {
                $count = 1;
                while ($count <= 5) {
                    try {
                        $GliffyObj->getDiagramAsImage($diagramId,Gliffy::MIME_TYPE_PNG,$fullImagePath);
                        break;

                    } catch (Exception $ex) {
                        $count += 1;
                    }
                }
            }

            $renderer->doc .= "<br/><img src=\"/_media/gliffy:$imageName\"><br/>";

            // It's a best priactice to wrap the editor in your own page for two reasons:
            // 1 - The OAuth security policy ensures that a given URL can never be requested more than once.  Wrapping the editor
            //     will enable you to ensure that your users always get a valid and fresh URL
            // 2 - By wrapping the editor, your users will not be confused and think that they are leaving your site.
            $renderer->doc .= "<a href=\"gliffy/gliffyWrapper.php?did=" .  $diagramId . "\" target=\"gliffy_" . $diagramId . "\">Edit Diagram</a><br/>";
            $renderer->doc .= "<a href=\"gliffy/updateDiagram.php?did=" .  $diagramId . "\" target=\"gliffy_" . $diagramId . "\">Update Diagram</a><br/>";

            return true;
        }
        return false;
    }

	function getDiagramId($name, $gliffy) {
        $diagrams = $gliffy->getDiagrams();
        foreach ($diagrams as $diagram) {
            if ($diagram->name === $name) {
                return (string)$diagram->id;
            }
        }
	}
}

?>
