<?php

defined('_JEXEC') or die();

/**
 * /* @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

class Ajax_View
{
    public static function goAjax($input)
    {
        $actionName = 'ajax_view';
        $upPath = 'plugins/content/up/';
        include_once $upPath . 'upAction.php';
        $action = new upAction($actionName);
        $data = $input->get('data', '', 'string');

        parse_str($data, $output);
        if (isset($output['action']) && $output['action'] != $actionName) {
            return $action->lang('en=Error : wrong action;fr=Erreur : action incorrecte');
        }
        if (! isset($output['content'])) {
            return $action->lang('en=Error : no arg content;fr=Erreur : aucun argument content');
        }

        if (isset($output['md5'])) {
            if (! password_verify($output['pwd'], $output['md5'])) {
                return $action->lang('en=Erreur : wrong password;fr=Erreur : mot de passe incorrect');
            }
            $key = file_get_contents('plugins/content/up/actions/ajax_view/info.key');
            $output['content'] = openssl_decrypt($output['content'], 'aes128', $key, 0, '1234567812345678');
        }

        switch ($output['type']) {
            case 'iframe':
                $url = Uri::root() . 'index.php?option=com_content&view=article&id=281&catid=2&tmpl=component';
                return '<iframe src="' . $url . '" style="width: 100%;height: 100%" allow="fullscreen">';
                break;
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
                // Article filter
                $model->setState('filter.article_id', (int) $output['content']);

                $items = $model->getItems();
                if (count($items) == 0) {
                    return '<span class="b t-red">' . $output['content'] . ' : ID article non trouvé / not found ...</span>';
                }
                $item = $items[0];
                // recup content
                PluginHelper::importPlugin('content');
                $out = ($item->fulltext == '') ? ($item->introtext) : ($item->fulltext);
                $out = HTMLHelper::_('content.prepare', $out);
                break;

            case 'text':
                $out = file_get_contents($output['content']);
                $out = $action->clean_HTML($out, $output['html'], $output['eol']);
                break;
            case 'image':
                $out = '<img src="' . $action->get_url_relative($output['content']) . '">';
                break;
            default:
                $out = 'Error, Type incorrect';
        }

        return $out;
    }
}
