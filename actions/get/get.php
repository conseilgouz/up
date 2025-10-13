<?php

/**
 * Retourne l'info correspondante à un mot-clé :
 * userid, username, usergroup
 * url-site, jpath-base
 * separator
 *
 * syntaxe {up jinfo=motclé}
 *
 * @version  UP-5.2
 * @author	 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Groupe pour bouton editeur
 *
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

class get extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // motclé
         );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // recherche de l'info
        $request = strtolower($options[__class__]);
        switch (strtolower($request)) {
            case 'version-joomla':
                $objVersion = new Version();
                $info = $objVersion->getShortVersion();
                break;
            case 'version-php':
                $info = phpversion();
                break;
            case 'user-ip':
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $info = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $info = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $info = $_SERVER['REMOTE_ADDR'];
                }
                break;
            case 'user-id':
                $user = Factory::getApplication()->getIdentity();
                $info = ($user->guest != 1) ? $user->id : 0;
                break;
            case 'user-name':
                $user = Factory::getApplication()->getIdentity();
                $info = ($user->guest != 1) ? $user->name : $this->lang('en=guest;fr=invité');
                break;
            case 'user-username':
                $user = Factory::getApplication()->getIdentity();
                $info = ($user->guest != 1) ? $user->username : $this->lang('en=guest;fr=invité');
                break;
            case 'site-root':
                $info = Uri::root();
                break;
            case 'site-path':
                $info = JPATH_BASE . DIRECTORY_SEPARATOR;
                break;
            case 'ds':
                $info = DIRECTORY_SEPARATOR;
                break;
            case 'up-path':
                $info = $this->upPath;
                break;
            case 'up-actions-path':
                $info = $this->upPath . 'actions/';
                break;
            case 'up-action-path':
                $info = $this->actionPath;
                break;
            case 'up-action-name':
                $info = $this->name;
                break;
        }

        // fonction perso dans le sous-dossier lib
        if (!isset($info)) {
            $request = str_replace('-', '_', $request);
            $perso_lib = $this->actionPath.'lib/'.$request.'.php';
            if (file_exists($perso_lib)) {
                include_once($perso_lib);
                $info = $request();
            }
        }

        // Non trouvé
        if (!isset($info)) {
            $info = $this->msg_inline("Keyword not found : $request");
        }
        return $info;
    }

    // run

}

// class
