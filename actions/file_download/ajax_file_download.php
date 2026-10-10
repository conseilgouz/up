<?php

defined('_JEXEC') or die;

/**
  /* @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class File_Download extends Lomart\Plugin\Content\Up\Extension\Up
{
    public static function goAjax($input)
    {
        $actionName = 'file_download';  // v4
        $action = new $actionName($actionName);
        $data = $input->get('data', '', 'string');
        $session = Factory::getApplication()->getSession();

        $output = array();
        parse_str($data, $output);
        $up = $output['upid'];

        if (!isset($output['file'])) {
            return UpHelper::lang($action,'en=Error : wrong format;fr=Erreur : format incorrect');
        }
        if (isset($output['md5'])) {
            $pass = $session->get($up.'filedownload.password');
            // clear password : not needed anymore
            if (!password_verify($output['pwd'], $pass)) {
                return UpHelper::lang($action, 'en=Erreur : wrong password;fr=Erreur : mot de passe incorrect');
            }
        }
        $custom = (file_exists('plugins/content/up/actions/file_download/' . 'custom/updownload.cfg') === true) ? 'custom/' : '';
        $cfg = parse_ini_file('plugins/content/up/actions/file_download/' . $custom . 'updownload.cfg');
        $extensions = array_map('trim', explode(',', $cfg['extensions']));
        $ext_blacklist = array('exe', 'bat', 'cmd', 'com', 'php', 'dll', 'cfg', 'sql', 'ini', 'inc', 'py', 'cgi', 'jsp', 'sh', 'pl');

        $fileid = $output['file'];
        $file = $session->get($up.$fileid);
        $fileName = basename($file);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions) || in_array($ext, $ext_blacklist)) {
            return UpHelper::lang($action, 'en=Error : wrong file type;fr=Erreur : type fichier incorrect');
        }
        $subdir = Uri::base();

        $filecls = OutputFilter::stringURLSafe('up-cls-' . str_replace('.', '-', $fileName));
        $out = "";
        if (strpos($file, '../') !== false) {
            return  'Error :  file error';
        }
        $url = JPATH_ROOT . '/' . rtrim($cfg['root'], '/') . '/' . $file;
        if (!is_file($url)) {
            return  'Error :  file not found';
        }
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
        $out .= 'ok,' . $subdir . ',' . rtrim($cfg['root'], '/') . ',' . $file . ',' . $nb . ',' . $time;
        return $out;
    }

}
