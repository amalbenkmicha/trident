<?php
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    DolphinStudio Dolphin Studio
 * @{
 */
defined('BX_DOL') or die('hack attempt');

class BxDolStudioJson {
    public function __construct() {
    	if(isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error ('Multiple instances are not allowed for the class: ' . get_class($this), E_USER_ERROR);

    }

	public function __clone() {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

	static function getInstance() {
        if(!isset($GLOBALS['bxDolClasses'][__CLASS__])) {
            $GLOBALS['bxDolClasses'][__CLASS__] = new BxDolStudioJson();
        }

        return $GLOBALS['bxDolClasses'][__CLASS__];
    }

    public function load($sUrl, $aParams = array()) {
    	$sContent = bx_file_get_contents($sUrl, $aParams);
    	if(empty($sContent))
    		return false;

		//--- Uncomment to debug
		//echo $sContent; exit;
		$mixedResult = json_decode($sContent, true);
		if(is_null($mixedResult))
			return false;

		return $mixedResult;
    }
}
/** @} */