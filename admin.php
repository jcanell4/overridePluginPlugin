<?php
/*
 * The code located between the marks [START: IOC] i [END: IOC] is used 
 * exclusively for IOC's applications. Changes are absolutely compliant 
 * to be used bay other plugins and templates.
 */
/**
 * Plugin management functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 * @author of IOC changes: Josep Cañellas <jcanell4@ioc.cat> & Eduard Latorre <eduardo.latorre@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// todo
// - maintain a history of file modified
// - allow a plugin to contain extras to be copied to the current template (extra/tpl/)
// - to images (lib/images/) [ not needed, should go in lib/plugin/images/ ]

require_once(DOKU_PLUGIN."/plugin/classes/ap_manage.class.php");

//--------------------------[ GLOBALS ]------------------------------------------------
// note: probably should be dokuwiki wide globals, where they can be accessed by pluginutils.php
// global $plugin_types;
// $plugin_types = array('syntax', 'admin');

// plugins that are an integral part of dokuwiki, they shouldn't be disabled or deleted
global $plugin_protected;
$plugin_protected = array('acl','plugin','config','info','usermanager','revert');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_plugin extends DokuWiki_Admin_Plugin {
    //[START: IOC]
    var $needRefresh=false;
    var $allowedRefresh=true;
    //[END: IOC] 

    var $disabled = 0;
    var $plugin = '';
    var $cmd = '';

    /**
     * @var ap_manage
     */
    var $handler = null;

    var $functions = array('delete','update',/*'settings',*/'info');  // require a plugin name
    var $commands = array('manage','download','enable');              // don't require a plugin name
    var $plugin_list = array();

    var $msg = '';
    var $error = '';

    function admin_plugin_plugin() {
        $this->disabled = plugin_isdisabled('plugin');
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 20;
    }

    /**
     * handle user request
     */
    function handle() {
        global $INPUT;
        // enable direct access to language strings
        $this->setupLocale();

        $fn = $INPUT->param('fn');
        if (is_array($fn)) {
            $this->cmd = key($fn);
            $this->plugin = is_array($fn[$this->cmd]) ? key($fn[$this->cmd]) : null;
        } else {
            $this->cmd = $fn;
            $this->plugin = null;
        }
        $this->_get_plugin_list();

        // verify $_REQUEST vars
        if (in_array($this->cmd, $this->commands)) {
            $this->plugin = '';
        } else if (!in_array($this->cmd, $this->functions) || !in_array($this->plugin, $this->plugin_list)) {
            $this->cmd = 'manage';
            $this->plugin = '';
        }

        if(($this->cmd != 'manage' || $this->plugin != '') && !checkSecurityToken()){
            $this->cmd = 'manage';
            $this->plugin = '';
        }

        // create object to handle the command
        $class = "ap_".$this->cmd;
        @require_once(DOKU_PLUGIN."/plugin/classes/$class.class.php");
        if (!class_exists($class)){
            $class = 'ap_manage';
        }

        $this->handler = new $class($this, $this->plugin);
        
        //[START: IOC]
        $this->needRefresh=false;
        //[END: IOC] 
        $this->msg = $this->handler->process();

    }

    /**
     * output appropriate html
     */
    function html() {
        // enable direct access to language strings
        $this->setupLocale();
        $this->_get_plugin_list();

        if ($this->handler === null) $this->handler = new ap_manage($this, $this->plugin);

        ptln('<div id="plugin__manager">');
        $this->handler->html();
        ptln('</div><!-- #plugin_manager -->');
    }

    /**
     * Returns a list of all plugins, including the disabled ones
     */
    function _get_plugin_list() {
        if (empty($this->plugin_list)) {
            $list = plugin_list('',true);     // all plugins, including disabled ones
            sort($list);
            trigger_event('PLUGIN_PLUGINMANAGER_PLUGINLIST',$list);
            $this->plugin_list = $list;
        }
        return $this->plugin_list;
    }

    //[START: IOC]
    function setAllowedRefresh($value=true){
        $ret = $this->allowedRefresh;
        $this->allowedRefresh=$value;
        return $ret;
    }

    function preventRefresh(){
        $ret = $this->allowedRefresh;
        $this->allowedRefresh=false;
        return $ret;
    }

    function forceRefresh(){
        $this->needRefresh = true;
    }
    
    function isRefreshNeeded(){
        return $this->needRefresh;
    }
    //[END: IOC] 

}






