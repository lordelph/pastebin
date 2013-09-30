<?php
/*************************************************************************************
 * erlang.php
 * --------
 * Author: Uwe Dauernheim (uwe@dauernheim.net)
 * Copyright: (c) 2008 Uwe Dauernheim (http://www.kreisquadratur.de/)
 * Release Version: 1\.0\.0
 * Date Started: 2008-09-27
 *
 * Erlang language file for GeSHi.
 *
 * CHANGES
 * -------
 * 2008-09-27 (1.0.0)
 *   [ ] First Release
 *
 * 2008-09-28 (1.0.0.1)
 *   [!] Bug fixed with keyword module.
 *   [+] Added more function names   
 *
 * TODO (updated 2008-09-27)
 * -------------------------
 *   [!] Stop ';' from being transformed to '<SEMI>'
 * 
 ************************************************************************************/

$language_data = array (
    'LANG_NAME' => 'Erlang',
    'COMMENT_SINGLE' => array(1 => '%'),
    'CASE_KEYWORDS' => GESHI_CAPS_NO_CHANGE,
    'QUOTEMARKS' => array('"'),
    'HARDQUOTE' => array("'", "'"), 
    'HARDESCAPE' => array('\\\'',), 
    'ESCAPE_CHAR' => '\\',
    'KEYWORDS' => array(
        1 => array(
            'module', 'export', 'import', 'author', 'behaviour'
            ),
        2 => array(
            'case', 'of', 'if', 'end', 'receive', 'after'
        	),
        3 => array(
        	// erlang
        	'set_cookie', 'get_cookie', 
            // io
            'format', 'fwrite', 'fread', 
            // gen_tcp
            'listen', 'accept', 'close', 
            // gen_server
            'call', 'start_link'
        	)
        ),
    'SYMBOLS' => array(
        ':', '=', '!', '|'
        ),
    'CASE_SENSITIVE' => array(
        GESHI_COMMENTS => false,
        1 => true,
        2 => true,
        3 => true
        ),
    'STYLES' => array(
        'KEYWORDS' => array(
            1 => 'color: #b1b100;',
            2 => 'color: #b1b100;',
            3 => 'color: #000066;'
            ),
        'COMMENTS' => array(
            1 => 'color: #666666; font-style: italic;',
            2 => 'color: #009966; font-style: italic;',
            3 => 'color: #0000ff;',
            4 => 'color: #cc0000; font-style: italic;',
            5 => 'color: #0000ff;',
            'MULTI' => 'color: #666666; font-style: italic;'
            ),
        'ESCAPE_CHAR' => array(
            0 => 'color: #000099; font-weight: bold;',
            'HARD' => 'color: #000099; font-weight: bold;'
            ),
        'BRACKETS' => array(
            0 => 'color: #009900;'
            ),
        'STRINGS' => array(
            0 => 'color: #ff0000;',
            'HARD' => 'color: #ff0000;'
            ),
        'NUMBERS' => array(
            0 => 'color: #cc66cc;'
            ),
        'METHODS' => array(
            1 => 'color: #006600;',
            2 => 'color: #006600;'
            ),
        'SYMBOLS' => array(
            0 => 'color: #339933;'
            ),
        'REGEXPS' => array(
            0 => 'color: #0000ff;',
            4 => 'color: #009999;',
            ),
        'SCRIPT' => array(
            )
        ),
    'URLS' => array(
        1 => '',
        2 => '',
        3 => 'http://www.erlang.org/doc/man/{FNAMEL}.html'
        ),
    'OOLANG' => true,
    'OBJECT_SPLITTERS' => array(
        1 => '-&gt;',
        2 => ':'
        ),
    'REGEXPS' => array(
        // Variable
        0 => '[A-Z][_a-zA-Z0-9]*',
        // File Descriptor
        4 => '&lt;[a-zA-Z_][a-zA-Z0-9_]*&gt;'
        ),
    'STRICT_MODE_APPLIES' => GESHI_NEVER,
    'TAB_WIDTH' => 4
);

?>
