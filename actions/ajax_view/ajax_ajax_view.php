<?php

defined('_JEXEC') or die();

/**
 * /* @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class Ajax_View extends Lomart\Plugin\Content\Up\Extension\Up
{
    public static function goAjax($input)
    {
        $actionName = 'ajax_view';

        $action = new $actionName($actionName);
        $session = Factory::getApplication()->getSession();

        $data = $input->get('data', '', 'string');
        parse_str($data, $output);

        $up = $output['upid'];

        $type = $session->get($up.'ajaxview.type');
        $content = $session->get($up.'ajaxview.content','');
        $html = $session->get($up.'ajaxview.html');
        $eol = $session->get($up.'ajaxview.eol');
        if ( $content == '') {
            return UpHelper::lang($action, 'en=Error : no arg content;fr=Erreur : aucun argument content');
        }
        if ($md5 = $session->get($up.'ajaxview.md5')) {
            if (! password_verify($output['pwd'], $md5)) {
                return UpHelper::lang($action, 'en=Erreur : wrong password;fr=Erreur : mot de passe incorrect');
            }
            $target = $session->get($up.'ajaxview.target');
            $key = file_get_contents('plugins/content/up/actions/ajax_view/info.key');
            $content = openssl_decrypt($target, 'aes128', $key, 0, '1234567812345678');
        }

        switch ($type) {
            case 'artid':
                // =====> RECUP DES DONNEES
                $model = new Joomla\Component\Content\Site\Model\ArticlesModel(array('ignore_request' => true));
                if (is_bool($model)) {
                    return 'Aucune catégorie';
                }
                // Set application parameters in model
                $app = Factory::getApplication();
                $appParams = $app->getParams();
                $model->setState('params', $appParams);
                // Access filter
                $access = ! ComponentHelper::getParams('com_content')->get('show_noauth');
                $user = Factory::getApplication()->getIdentity();
                $authorised = Access::getAuthorisedViewLevels($user->id);
                $model->setState('filter.access', $access);
                $model->setState('filter.viewlevels', $authorised);

                // Article filter
                $model->setState('filter.article_id', (int) $content);

                $items = $model->getItems();
                if (count($items) == 0) {
                    return '<span class="b t-red">' . $content . ' : ID article non trouvé / not found ...</span>';
                }
                $item = $items[0];
                // recup content
                PluginHelper::importPlugin('content');
                $out = ($item->fulltext == '') ? ($item->introtext) : ($item->fulltext);
                $out = HTMLHelper::_('content.prepare', $out);
                break;

            case 'text':
                $ext_whitelist = array('txt','html','csv');
                $ext = strtolower(pathinfo($content, PATHINFO_EXTENSION));
                if (!$ext || !in_array($ext, $ext_whitelist) || strpos($content, './') !== false || strpos($content, '..') !== false) {
                    return  'Error :  Type incorrect';
                }
                $file = JPATH_BASE.'/'.ltrim($content, '/');
                if (!is_file($file)) {
                    return  'Error :  file not found';
                }
                $out = file_get_contents($file);
                $out = UpHelper::clean_HTML($action, $out, $html, $eol);
                break;
            case 'image':
                $ext_whitelist = explode(',', $session->get($up.'ajaxview.extimages'));
                $ext = strtolower(pathinfo($content, PATHINFO_EXTENSION));
                $exts = explode('?', $ext);
                $ext = $exts[0];
                if (!in_array($ext, $ext_whitelist)) {
                    return  'Error : Type incorrect';
                }
                $img = JPATH_BASE.'/'.$content;
                if (count($exts) > 1) {
                    // image type with version
                    $imgs =  explode('?', $img);
                    $img = $imgs[0];
                }
                $file = UpHelper::get_url_relative($action, $content);
                if (!is_file($img)) {
                    return  'Error :  file not found';
                }
                $out = '<img src="' . $file . '">';
                break;
            default:
                $out = 'Error, Type incorrect';
        }
        $session->clear($up.'ajaxview');
        return $out;
    }
}
