<?php defined('BX_DOL') or defined('BX_DOL_INSTALL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    DolphinStudio Dolphin Studio
 * @{
 */

define('BX_DOL_AUDIT_FAIL', 'fail');
define('BX_DOL_AUDIT_WARN', 'warn');
define('BX_DOL_AUDIT_UNDEF', 'undef');
define('BX_DOL_AUDIT_OK', 'ok');

class BxDolStudioToolsAudit extends BxDol
{
    protected $aType2ClassCSS;
    protected $aType2Title;

    protected $sLatestPhp53Version;
    protected $sMinPhpVer;
    protected $aPhpSettings;
    protected $iPhpErrorReporting;

    protected $sMinMysqlVer;
    protected $aMysqlOptimizationSettings;

    protected $aDolphinOptimizationSettings;

    protected $aRequiredApacheModules;

    function __construct()
    {
        parent::__construct();

        $this->aType2ClassCSS = array (
            BX_DOL_AUDIT_FAIL => 'fail',
            BX_DOL_AUDIT_WARN => 'warn',
            BX_DOL_AUDIT_UNDEF => 'undef',
            BX_DOL_AUDIT_OK => 'ok',
        );

        $this->aType2Title = array (
            BX_DOL_AUDIT_FAIL => _t('_sys_audit_title_fail'),
            BX_DOL_AUDIT_WARN => _t('_sys_audit_title_warn'),
            BX_DOL_AUDIT_UNDEF => _t('_sys_audit_title_undef'),
            BX_DOL_AUDIT_OK => _t('_sys_audit_title_ok'),
        );

        $this->sLatestPhp53Version = '5.3.28';
        $this->sMinPhpVer = '5.2.0';
        $this->aPhpSettings = array (
            'allow_url_fopen' => array('op' => '=', 'val' => true, 'type' => 'bool'),
            'allow_url_include' => array('op' => '=', 'val' => false, 'type' => 'bool'),
            'magic_quotes_gpc' => array('op' => '=', 'val' => false, 'type' => 'bool', 'warn' => 1),
            'memory_limit' => array('op' => '>=', 'val' => 128*1024*1024, 'type' => 'bytes', 'unlimited' => -1),
            'post_max_size' => array('op' => '>=', 'val' => 2*1024*1024, 'type' => 'bytes', 'warn' => 1),
            'upload_max_filesize' => array('op' => '>=', 'val' => 2*1024*1024, 'type' => 'bytes', 'warn' => 1),
            'register_globals' => array('op' => '=', 'val' => false, 'type' => 'bool'),
            'safe_mode' => array('op' => '=', 'val' => false, 'type' => 'bool'),
            'short_open_tag' => array('op' => '=', 'val' => true, 'type' => 'bool'),
            'disable_functions' => array('op' => '=', 'val' => ''),
            'php module: curl' => array('op' => 'module', 'val' => 'curl'),
            'php module: gd' => array('op' => 'module', 'val' => 'gd'),
            'php module: mbstring' => array('op' => 'module', 'val' => 'mbstring'),
            'php module: xsl' => array('op' => 'module', 'val' => 'xsl', 'warn' => 1),
            'php module: json' => array('op' => 'module', 'val' => 'json', 'warn' => 1),
            'php module: openssl' => array('op' => 'module', 'val' => 'openssl', 'warn' => 1),
            'php module: zip' => array('op' => 'module', 'val' => 'zip', 'warn' => 1),
            'php module: ftp' => array('op' => 'module', 'val' => 'ftp', 'warn' => 1),
            'php module: oauth' => array('op' => 'module', 'val' => 'oauth', 'warn' => 1),
        );

        $this->sMinMysqlVer = '4.1.2';
        $this->aMysqlOptimizationSettings = array (
            'key_buffer_size' => array('op' => '>=', 'val' => 128*1024, 'type' => 'bytes'),
            'query_cache_limit' => array('op' => '>=', 'val' => 1000000),
            'query_cache_size' => array('op' => '>=', 'val' => 16*1024*1024, 'type' => 'bytes'),
            'query_cache_type' => array('op' => 'strcasecmp', 'val' => 'on'),
            'max_heap_table_size' => array('op' => '>=', 'val' => 16*1024*1024, 'type' => 'bytes'),
            'tmp_table_size' => array('op' => '>=', 'val' => 16*1024*1024, 'type' => 'bytes'),
            'thread_cache_size ' => array('op' => '>', 'val' => 0),
        );

        $this->aDolphinOptimizationSettings = array (
            'DB cache' => array('enabled' => 'sys_db_cache_enable', 'cache_engine' => 'sys_db_cache_engine', 'check_accel' => true),
            'Page blocks cache' => array('enabled' => 'sys_pb_cache_enable', 'cache_engine' => 'sys_pb_cache_engine', 'check_accel' => true),
            'Member menu cache' => array('enabled' => 'always_on', 'cache_engine' => 'sys_mm_cache_engine', 'check_accel' => true),
            'Templates Cache' => array('enabled' => 'sys_template_cache_enable', 'cache_engine' => 'sys_template_cache_engine', 'check_accel' => true),
            'CSS files cache' => array('enabled' => 'sys_template_cache_css_enable', 'cache_engine' => '', 'check_accel' => false),
            'JS files cache' => array('enabled' => 'sys_template_cache_js_enable', 'cache_engine' => '', 'check_accel' => false),
            'Compression for CSS/JS cache' => array('enabled' => 'sys_template_cache_compress_enable', 'cache_engine' => '', 'check_accel' => false),
        );

        $this->aRequiredApacheModules = array (
            'rewrite_module' => 'mod_rewrite',
        );

        if (isset($_GET['action'])) {
            $sOutput = null;
            switch ($_GET['action']) {
                case 'audit_send_test_email':
                    $sOutput = $this->sendTestEmail();
                    break;
                case 'phpinfo':
                    ob_start();
                    phpinfo();
                    $sOutput = ob_get_clean();
                    break;
                case 'phpinfo_popup':
                    $sUrlSelf = bx_js_string($_SERVER['PHP_SELF'], BX_ESCAPE_STR_APOS);
                    $sUrlSelf = bx_append_url_params($sUrlSelf, array('action' => 'phpinfo'));
                    $sOutput = '<iframe width="640" height="480" src="' . $sUrlSelf . '"></iframe>';
                    break;
            }
            if ($sOutput) {
                header('Content-type: text/html; charset=utf-8');
                echo $sOutput;
                exit;
            }
        }
    }

    public function generate()
    {
        ob_start();

        $this->setErrorReporting();

        $this->generateStyles();
        $this->generateJs();

        $this->requirements();

        if (!defined('BX_DOL_INSTALL'))
            $this->siteSetup();

        $this->optimization();

        $this->manualCheck();

        $this->restoreErrorReporting();

        return ob_get_clean();
    }

    public function generateStyles() 
    {
        ?>
<style>
    .ok {
        color:green;
    }
    .fail {
        color:red;
    }
    .warn {
        color:orange;
    }
    .undef {
        color:gray;
    }
</style>
        <?php
    }

    public function generateJs() 
    {
        $sUrlSelf = bx_js_string($_SERVER['PHP_SELF'], BX_ESCAPE_STR_APOS);
        ?>
        <script language="javascript">
            function bx_sys_adm_audit_test_email()
            {
                var sEmail = prompt('<?php echo _t('_Email'); ?>', '<?php echo function_exists('getParam') ? getParam('site_email') : ''; ?>');
                if (null == sEmail || ('string' == (typeof sEmail) && !sEmail.length))
                    return;

                $('#bx-sys-adm-audit-test-email').html('Sending...');
                $.post('<?php echo bx_append_url_params($sUrlSelf, array('action' => 'audit_send_test_email')); ?>&email=' + sEmail, function(data) {
                    $('#bx-sys-adm-audit-test-email').html(data);
                });
            }

            function bx_sys_adm_audit_phpinfo()
            {
                $(window).dolPopupAjax({url: '<?php echo bx_append_url_params($sUrlSelf, array('action' => 'phpinfo_popup')); ?>'});
            }
        </script>
        <?php
    }

    public function checkRequirements($sType = BX_DOL_AUDIT_FAIL)
    {
        $this->setErrorReporting();

        $aRet = array ();
        $aMessages = array ();
        $this->requirementsPHP(false, $aMessages);
        foreach ($aMessages as $sName => $r)
            if ($sType == $r['type'])
                $aRet[] = "$sName = " . $this->format_output($r['params']['real_val'], isset($this->aPhpSettings[$sName]) ? $this->aPhpSettings[$sName] : '') . " - " . $this->getMsgHTML($sName, $r);

        $this->restoreErrorReporting();

        return $aRet;
    }

    protected function requirements()
    {
        echo '<h1>' . _t('_sys_audit_header_requirements') . '</h1>';
        $this->requirementsPHP();
        if (!defined('BX_DOL_INSTALL'))
            $this->requirementsMySQL();
        $this->requirementsWebServer();
        $this->requirementsOS();
        $this->requirementsHardware();
    }

    protected function requirementsPHP($bEcho = true, &$aOutputMessages = null)
    {
        $a = unserialize(file_get_contents("http://www.php.net/releases/index.php?serialize=1"));
        $sLatestPhpVersion = $a[5]['version'];

        if (version_compare(phpversion(), "5.4", ">=") == 1)
            unset($this->aPhpSettings['short_open_tag']);

        $aMessages = array ();

        $sPhpVer = PHP_VERSION;        
        if (empty($sLatestPhpVersion))
            $aVer = array('type' => BX_DOL_AUDIT_UNDEF, 'msg' => _t('_sys_audit_msg_value_checking_failed'), 'params' => array ('real_val' => $sPhpVer));
        elseif (version_compare($sPhpVer, $this->sMinPhpVer, '<'))
            $aVer = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_version_is_incompatible', $this->sMinPhpVer), 'params' => array ('real_val' => $sPhpVer));
        elseif (version_compare($sPhpVer, '5.4.0', '>=') && version_compare($sPhpVer, '6.0.0', '<') && !version_compare($sPhpVer, $sLatestPhpVersion, '>='))
            $aVer = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_version_is_outdated', $sLatestPhpVersion), 'params' => array ('real_val' => $sPhpVer));
        elseif (version_compare($sPhpVer, '5.2.0', '>=') && version_compare($sPhpVer, '5.4.0', '<') && !version_compare($sPhpVer, $this->sLatestPhp53Version, '>='))
            $aVer = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_version_is_outdated', $this->sLatestPhp53Version), 'params' => array ('real_val' => $sPhpVer));
        else
            $aVer = array('type' => BX_DOL_AUDIT_OK, 'params' => array ('real_val' => $sPhpVer));
        $aMessages[_t('_sys_audit_version')] = $aVer;

        foreach ($this->aPhpSettings as $sName => $r) {
            $a = $this->checkPhpSetting($sName, $r);
            if ($a['res'])
                $aMessages[$sName] = array('type' => BX_DOL_AUDIT_OK, 'params' => $a);
            elseif (isset($r['warn']) && $r['warn'])
                $aMessages[$sName] = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_should_be', $r['op'], $this->format_output($r['val'], $r)), 'params' => $a);
            else
                $aMessages[$sName] = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_must_be', $r['op'], $this->format_output($r['val'], $r)), 'params' => $a);
        }

        if (null !== $aOutputMessages)
            $aOutputMessages = $aMessages;

        if ($bEcho) {
            $s = '';
            foreach ($aMessages as $sName => $r) {
                $s .= $this->getBlock($sName, $this->format_output($r['params']['real_val'], isset($this->aPhpSettings[$sName]) ? $this->aPhpSettings[$sName] : ''), $this->getMsgHTML($sName, $r));
            }
            echo $this->getSection('PHP', '', $s);
        }
    }

    protected function requirementsMySQL() 
    {
        $sMysqlVer = BxDolDb::getInstance()->getServerInfo();
        if (preg_match ('/^(\d+)\.(\d+)\.(\d+)/', $sMysqlVer, $m)) {
            $sMysqlVer = "{$m[1]}.{$m[2]}.{$m[3]}";
            if (version_compare($sMysqlVer, $this->sMinMysqlVer, '<'))
                $aMessage = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_version_is_incompatible', $this->sMinMysqlVer));
            else
                $aMessage = array('type' => BX_DOL_AUDIT_OK);
        } else {
            $aMessage = array('type' => BX_DOL_AUDIT_UNDEF, 'msg' => _t('_sys_audit_msg_value_checking_failed'));
        }

        $s = $this->getBlock(_t('_sys_audit_version'), $sMysqlVer, $this->getMsgHTML(_t('_sys_audit_version'), $aMessage));
        echo $this->getSection('MySQL', '', $s);
    }

    protected function requirementsWebServer()
    {
        $s = '';
        foreach ($this->aRequiredApacheModules as $sName => $sNameCompiledName)
            $s .= $this->getBlock($sName, '', $this->checkApacheModule($sName, $sNameCompiledName));

        echo $this->getSection(_t('_sys_audit_section_webserver'), $_SERVER['SERVER_SOFTWARE'], $s);
    }

    protected function requirementsOS() 
    {
        $s = $this->getBlock(php_uname());
        echo $this->getSection(_t('_sys_audit_section_os'), '', $s);
    }

    protected function requirementsHardware()
    {
        $s = $this->getBlock(_t('_sys_audit_msg_hardware_requirements'));
        echo $this->getSection(_t('_sys_audit_section_hardware'), '', $s);
    }

    protected function siteSetup()
    {
        $sDolphinPath = defined('BX_DIRECTORY_PATH_ROOT') ? BX_DIRECTORY_PATH_ROOT : BX_INSTALL_DIR_ROOT;

        $sEmailToCkeckMailSending = function_exists('getParam') ? getParam('site_email') : '';

        $sLatestDolphinVer = file_get_contents("http://rss.boonex.com/");
        if (preg_match ('#<dolphin>([\.0-9]+)</dolphin>#', $sLatestDolphinVer, $m))
            $sLatestDolphinVer = $m[1];
        else
            $sLatestDolphinVer = 'undefined';
       
        $sDolphinVer = getParam('sys_version');
        $aMessage = array('type' => BX_DOL_AUDIT_OK);
        if (!version_compare($sDolphinVer, $sLatestDolphinVer, '>='))
            $aMessage = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_version_is_outdated', $sLatestDolphinVer));

        $s = '';
        $s .= $this->getBlock(_t('_sys_audit_version_dolphin'), $sDolphinVer, $this->getMsgHTML(_t('_sys_audit_version_dolphin'), $aMessage));
        $s .= $this->getBlock(_t('_sys_audit_permissions'), '', _t('_sys_audit_msg_permissions'));
        $s .= $this->getBlock('ffmpeg', '', _t('_sys_audit_msg_ffmpeg', `{$sDolphinPath}flash/modules/global/app/ffmpeg.exe 2>&1`));
        $s .= $this->getBlock(_t('_sys_audit_mail_sending'), '', _t('_sys_audit_msg_mail_sending'));
        $s .= $this->getBlock(_t('_sys_audit_cron_jobs'), '', _t('_sys_audit_msg_cron_jobs', `crontab -l 2>&1`));

        echo '<h1>' . _t('_sys_audit_header_site_setup') . '</h1>';
        echo "<ul>$s</ul>";
    }

    protected function optimization()
    {
        echo '<h1>' . _t('_sys_audit_header_site_optimization') . '</h1>';

        $this->optimizationPhp();

        if (!defined('BX_DOL_INSTALL'))
            $this->optimizationMySQL();

        $this->optimizationWebServer();

        if (!defined('BX_DOL_INSTALL'))
            $this->optimizationDolphin();
    }

    protected function optimizationPhp()
    {
        $s = '';
        $aMessage = array();

        $sAccel = $this->getPhpAccelerator();
        if (!$sAccel)
            $aMessage = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_php_accelerator_missing'));
        else
            $aMessage = array('type' => BX_DOL_AUDIT_OK);
        $s .= $this->getBlock(_t('_sys_audit_php_accelerator'), $sAccel, $this->getMsgHTML(_t('_sys_audit_php_accelerator'), $aMessage));

        $sSapi = php_sapi_name();
        if (0 == strcasecmp('cgi', $sSapi))
            $aMessage = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_php_setup_inefficient'));
        else
            $aMessage = array('type' => BX_DOL_AUDIT_OK);
        $s .= $this->getBlock(_t('_sys_audit_php_setup'), $sSapi, $this->getMsgHTML(_t('_sys_audit_php_setup'), $aMessage));

        echo $this->getSection('PHP', '', $s);
    }

    protected function optimizationMySQL()
    {
        $s = '';
        $oDb = BxDolDb::getInstance();

        foreach ($this->aMysqlOptimizationSettings as $sName => $r) {
            $a = $this->checkMysqlSetting($sName, $r, $oDb);
            $aMessage = array('type' => BX_DOL_AUDIT_OK);
            if (!$a['res'])
                $aMessage = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_must_be', $r['op'], $this->format_output($r['val'], $r)));
            $s .= $this->getBlock($sName, $this->format_output($a['real_val'], $r), $this->getMsgHTML($sName, $aMessage));
        }

        echo $this->getSection('MySQL', '', $s);
    }

    protected function optimizationWebServer()
    {
        $s = '';

        $sName = 'expires_module';
        $sApacheModuleChack = _t('_sys_audit_msg_optimization_apache_module', $sName, $this->checkApacheModule($sName));
        $s .= $this->getBlock(_t('_sys_audit_userside_caching'), '', _t('_sys_audit_msg_userside_caching', $this->getUrlForGooglePageSpeed('LeverageBrowserCaching'), $sApacheModuleChack));

        $sName = 'deflate_module';
        $sApacheModuleChack = _t('_sys_audit_msg_optimization_apache_module', $sName, $this->checkApacheModule($sName));
        $s .= $this->getBlock(_t('_sys_audit_serverside_compression'), '', _t('_sys_audit_msg_serverside_compression', $sApacheModuleChack));

        echo $this->getSection(_t('_sys_audit_section_webserver'), '', $s);
    }

    protected function optimizationDolphin()
    {
        $s = '';      
        foreach ($this->aDolphinOptimizationSettings as $sName => $a) {

            $sVal = ('always_on' == $a['enabled'] || getParam($a['enabled'])) ? 'On' : 'Off';
            if ($a['cache_engine'])
                $sVal .= _t('_sys_audit_msg_dolphin_x_based_cache_engine', getParam($a['cache_engine']));

            if ('always_on' != $a['enabled'] && !getParam($a['enabled']))
                $aMessage = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_dolphin_optimization_fail'));
            elseif ($a['check_accel'] && !$this->getPhpAccelerator() && 'File' == getParam($a['cache_engine']))
                $aMessage = array('type' => BX_DOL_AUDIT_WARN, 'msg' => _t('_sys_audit_msg_dolphin_optimization_warn'));
            else
                $aMessage = array('type' => BX_DOL_AUDIT_OK);

            $s .= $this->getBlock($sName, $sVal, $this->getMsgHTML($sName, $aMessage));
        }

        echo $this->getSection('Dolphin', '', $s);
    }

    protected function manualCheck()
    {
        echo '<a name="manual_audit"></a>';
        echo '<h1>' . _t('_sys_audit_header_manual_audit') . '</h1>';
        echo _t('_sys_audit_msg_manual_audit');
    }

    protected function checkPhpSetting($sName, $a)
    {
        $mixedVal = ini_get($sName);
        $mixedVal = $this->format_input ($mixedVal, $a);

        switch ($a['op']) {
            case 'module':
                $bResult = extension_loaded($a['val']);
                $mixedVal = $bResult ? $a['val'] : '';
                break;
            case '>':
                $bResult = (isset($a['unlimited']) && $mixedVal == $a['unlimited']) ? true : ($mixedVal > $a['val']);
                break;
            case '>=':
                $bResult = (isset($a['unlimited']) && $mixedVal == $a['unlimited']) ? true : ($mixedVal >= $a['val']);
                break;
            case '=':
            default:
                $bResult = ($mixedVal == $a['val']);
        }
        return array ('res' => $bResult, 'real_val' => $mixedVal);
    }

    protected function checkMysqlSetting($sName, $a, $oDb)
    {
        $mixedVal = $oDb->getOption($sName);
        $mixedVal = $this->format_input ($mixedVal, $a);

        switch ($a['op']) {
            case '>':
                $bResult = ($mixedVal > $a['val']);
                break;
            case '>=':
                $bResult = ($mixedVal >= $a['val']);
                break;
            case 'strcasecmp':
                $bResult = 0 == strcasecmp($mixedVal, $a['val']);
                break;
            case '=':
            default:
                $bResult = ($mixedVal == $a['val']);
        }
        return array ('res' => $bResult, 'real_val' => $mixedVal);
    }

    protected function format_output ($mixedVal, $a)
    {
        if (isset($a['type']) && 'bool' == $a['type'])
            return $mixedVal ? 'On' : 'Off';
        else
            return $mixedVal;
    }

    protected function format_input ($mixedVal, $a)
    {
        if (isset($a['type']) && 'bytes' == $a['type']) 
            return $this->format_bytes ($mixedVal);
        else
            return $mixedVal;
    }

    protected function format_bytes($val)
    {
        return return_bytes($val);
    }

    protected function checkApacheModule ($sModule, $sNameCompiledName = '')
    {
        $a = array (
            'deflate_module' => 'mod_deflate',
            'expires_module' => 'mod_expires',
        );
        if (!$sNameCompiledName && isset($a[$sModule]))
            $sNameCompiledName = $a[$sModule];

        if (function_exists('apache_get_modules')) {

            $aModules = apache_get_modules();
            $ret = in_array($sNameCompiledName, $aModules);

        } else {

            $sApachectlPath = trim(`which apachectl`);
            if (!$sApachectlPath)
                $sApachectlPath = trim(`which apache2ctl`);
            if (!$sApachectlPath)
                $sApachectlPath = trim(`which /usr/local/apache/bin/apachectl`);
            if (!$sApachectlPath)
                $sApachectlPath = trim(`which /usr/local/apache/bin/apache2ctl`);
            if (!$sApachectlPath) {
                $aMessage = array('type' => BX_DOL_AUDIT_UNDEF, 'msg' => _t('_sys_audit_msg_apache_module_undef', $sModule));
                return $this->getBlock($sModule, '', $this->getMsgHTML($sModule, $aMessage));
            }
            $ret = (boolean)`$sApachectlPath -M 2>&1 | grep $sModule`;
            if (!$ret)
                $ret = (boolean)`$sApachectlPath -l 2>&1 | grep $sNameCompiledName`;
        }

        $aMessage = array('type' => BX_DOL_AUDIT_OK);
        if (!$ret)
            $aMessage = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_apache_module_fail', $sModule));

        return $this->getBlock('', '', $this->getMsgHTML($sModule, $aMessage), false);
    }


    protected function getPhpAccelerator ()
    {
        $aAccelerators = array (
            'eAccelerator' => array('op' => 'module', 'val' => 'eaccelerator'),
            'APC' => array('op' => 'module', 'val' => 'apc'),
            'XCache' => array('op' => 'module', 'val' => 'xcache'),
        );
        foreach ($aAccelerators as $sName => $r) {
            $a = $this->checkPhpSetting($sName, $r);
            if ($a['res'])
                return $sName;
        }
        return false;
    }

    protected function getUrlForGooglePageSpeed ($sRule)
    {
        $sUrl = urlencode(BX_DOL_URL_ROOT);
        return 'http://pagespeed.googlelabs.com/#url=' . $sUrl . '&mobile=false&rule=' . $sRule;
    }

    protected function sendTestEmail ()
    {
        $sEmailToCkeckMailSending = isset($_GET['email']) ? $_GET['email'] : getParam('site_email');
        $mixedRet = sendMail($sEmailToCkeckMailSending, 'Audit Test Email', 'Sample text for testing<br /><u><b>Sample text for testing</b></u>', '', array(), BX_EMAIL_SYSTEM);
        if (!$mixedRet) {
            $aMessage = array('type' => BX_DOL_AUDIT_FAIL, 'msg' => _t('_sys_audit_msg_mail_send_failed'));
            return $this->getBlock('', '', $this->getMsgHTML(_t('_sys_audit_mail_sending'), $aMessage), false);
        } else {
            return _t('_sys_audit_msg_mail_sent', $sEmailToCkeckMailSending);
        }
    }

    protected function setErrorReporting () 
    {
        if (version_compare(phpversion(), "5.3.0", ">=") == 1)
            $this->iPhpErrorReporting = error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
        else
            $this->iPhpErrorReporting = error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    }

    protected function restoreErrorReporting () 
    {
        error_reporting($this->iPhpErrorReporting);
    }
    
    protected function getSection($sTitle, $sTitleAddon, $sContent) 
    {
        $s = '<b>' . $sTitle . '</b>: ' . $sTitleAddon;
        $s .= '<ul>';
        $s .= $sContent;
        $s .= '</ul>';
        return $s;
    }

    protected function getBlock($sName, $sValue = '', $sMsg = '', $bWrapAsListItem = true) 
    {
        $s = $bWrapAsListItem ? '<li>'  : '';
        if ($sName !== '')
            $s .= "$sName ";
        if ($sValue !== '')
            $s .= " = $sValue ";
        if ($sMsg)
                $s .= ($s ? " - " : '') . $sMsg;
        return $s . ($bWrapAsListItem ? '</li>' : '') . "\n";
    }

    protected function getMsgHTML($sName, $a) 
    {
        $s = '';
        $s .= '<b class="' . $this->aType2ClassCSS[$a['type']]. '">' . $this->aType2Title[$a['type']]. '</b> ';
        if (isset($a['msg']))
            $s .= '(' . $a['msg'] . ')';
        return $s;
    }
}

/** @} */