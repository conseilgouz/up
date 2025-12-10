<?php

/**
 * Charge un module à l'emplacement du shortcode
 *
 * syntaxe {up loadmodule=id_or_position_or_type_or_title}
 *
 * @version  UP-5.2
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author  LOMART
 * @tags    Expert
 *
 */
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class loadmodule extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // nom ou id du module
            'filter' => '', // conditions. Voir doc action filter
            'tag' => 'div', // balise du bloc principal
            'id' => '',
            'class' => '', // classe(s) ou style pour bloc
            'style' => '', // classe(s) ou style pour bloc
            'css-head' => '' // style ou class2style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // === Chargement du module
        $document = Factory::getApplication()->getDocument();
        $renderer = $document->loadRenderer('module');
        $params   = ['style' => 'none'];

        $find = $options[__CLASS__];
        if (is_numeric($find) === true) {
            $modules  = ModuleHelper::getModuleById($find);
        } else {
            // recherche position
            $modules  = ModuleHelper::getModules($find);
            // recherche type + titre
            if (empty($modules)) {
                list($name, $title) = array_map('trim', explode(',', $find));
                $modules  = ModuleHelper::getModule($name, $title);
                if (empty($modules)) {
                    $name = 'mod_' . $name;
                    $modules  = ModuleHelper::getModule($name, $title);
                }
            }
            // recherche titre seul
            if (empty($modules->id)) {
                $id = $this->get_db_value('id', 'modules', 'title=lower('. $find . ')');
                $modules  = ModuleHelper::getModuleById(strval($id));
            }
        }

        ob_start();

        if ($modules->id > 0) {
            $out = $renderer->render($modules, $params);
        }
        ob_get_clean();

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        if (!empty($options['tag'])) {
            $out = UpHelper::set_attr_tag($this,$options['tag'], $attr_main, $out);
        }

        return $out;
    }

    // run
}

// class
