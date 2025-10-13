<?php

defined('_JEXEC') or die;

/**
  /* @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\OutputFilter;

class File_Download {

    static function goAjax($input) {
        $actionName = 'file_download';  // v4
        $upPath = 'plugins/content/up/';
        include_once $upPath . 'upAction.php';
        $action = new upAction($actionName);
        $data = $input->get('data', '', 'string');

        $output = array();
        parse_str($data, $output);
        if (!isset($output['action']) || !isset($output['file'])) {
            return $action->lang('en=Error : wrong format;fr=Erreur : format incorrect');
        }
        if ($output['action'] != 'file_download') {
            return $action->lang('en=Error : wrong action;fr=Erreur : action incorrecte');
        }

        if (isset($output['md5'])) {
            if (!password_verify($output['pwd'], $output['md5']))
                return $action->lang('en=Erreur : wrong password;fr=Erreur : mot de passe incorrect');
        }
        $custom = (file_exists('plugins/content/up/actions/file_download/' . 'custom/updownload.cfg') === true) ? 'custom/' : '';
        $cfg = parse_ini_file('plugins/content/up/actions/file_download/' . $custom . 'updownload.cfg');
        $extensions = array_map('trim', explode(',', $cfg['extensions']));
        $ext_blacklist = array('exe', 'bat', 'cmd', 'com', 'php', 'dll', 'cfg', 'sql', 'ini', 'inc', 'py', 'cgi', 'jsp', 'sh', 'pl');

        $action = $output['action'];
        $file = $output['file'];
        $fileName = basename($file);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions) || in_array($ext, $ext_blacklist)) {
            return $action->lang('en=Error : wrong file type;fr=Erreur : type fichier incorrect');
        }
        $subdir = (Uri::base(true) == '') ? '/' : '';
        $filecls = OutputFilter::stringURLSafe('up-cls-' . str_replace('.', '-', $fileName));
        $out = "";

        $url = JPATH_ROOT . '/' . rtrim($cfg['root'], '/') . '/' . htmlentities($file, ENT_QUOTES);
        // url fichier stat
        $url_log = dirname($url) . '/.log/' . basename($url);
        $nb = 0;
        if (file_exists($url_log . '.stat')) {
            list($nb, $time) = explode('|', file_get_contents($url_log . '.stat'));
            $nb = intval($nb);
        }
        $nb++; // ajout de 1 au nombre de hits
        $ret = file_put_contents($url_log . '.stat', $nb . '|' . date('Y-m-d H:i'));
        $makeLogFile = $cfg['logfile'];
        if ($makeLogFile) {
            file_put_contents($url_log . '.log', date('Y-m-d H:i:s') . '|' . $_SERVER['REMOTE_ADDR'] . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        $time = date('d/m/Y H:i');
        $out .= 'ok,' . $subdir . ',' . rtrim($cfg['root'], '/') . ',' . $filecls . ',' . $nb . ',' . $time;
        return $out;
    }

}
