<?php

/**
 * affiche une date
 *
 * affiche une date définie ou calculée en tenant compte des nouvelles contraintes de PHP8
 *
 * syntaxe {up action=option_principale}
 *
 * @version  UP-3.0
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    EDITOR
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class date extends Lomart\Plugin\Content\Up\Extension\Up
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
            __class__ => '', // date reconnue par la fonction php : strtotime
            'format' => '%A %e %B %Y', // format. ex: %A %e %B %Y
            'locale' => '', // le code pays (en_US) ou NULL=celui en cours
            'timezone' => '', // fuseau horaire. Ex: Europe/Paris ou Atlantic/Reykjavik. vide=celui du serveur
            'id' => '', // identifiant
            'tag' => 'span', // balise HTML pour le bloc principal
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // === Timezone v3.1
        if ($options['timezone']) {
            date_default_timezone_set($options['timezone']);
        }

        // === LA DATE
        $date = trim($options[__class__]);

        // === mise en forme
        $options['format'] = UpHelper::get_bbcode($this,$options['format']);
        $date = UpHelper::up_date_format($this,$date, $options['format'], $options['locale']);

        // attributs du bloc principal
        $attr_main = array();
        if (! empty($options['tag'])) { // v31
            $attr_main['id'] = $options['id'];
        }
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'_' . $options['tag'], $attr_main, $date);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
