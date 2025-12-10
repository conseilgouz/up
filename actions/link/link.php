<?php

/**
 * Affiche un lien vers une URL, un numéro de téléphone ou un mail
 *
 * syntaxe {up link=URL | label=label | blank | class=...}
 *
 * @author  LOMART
 * @version  UP-2.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 *
 */

/*
 * v2.9 :
 * - support phone et protocole (ex:skype)
 * - ajout option filter (phone si mobile)
 *
 */

defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class link extends Lomart\Plugin\Content\Up\Extension\Up
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
            __class__ => '', // URL ou EMAIL pour l'attribut href
            'label' => '', // texte alternatif pour le lien
            'blank' => '0', // ouvre le lien dans un nouvel onglet
            /* [st-icon] Icône */
            'icon' => '0', // icone affichée devant le lien : 0=pas d'icône, 1=icône par defaut, Unicode, fonticon ou image
            'icon-style' => '', // classes et styles pour l'icône
            'icon-url' => 'Ux1F517', // Icone par defaut pour les URL
            'icon-phone' => 'Ux260E', // Icone par defaut pour les téléphone - v2.9
            'icon-mail' => 'Ux2709', // Icone par defaut pour les MAIL
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-divers] Divers */
            'font-prefix' => 'icon-', // pour icomoon ou 'fa fa-' pour font-awesome (a mettre dans pref.ini)
            'filter' => '', // conditions. Voir doc action filter (v2.9)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // le lien
        $link = trim($options['link']);
        if (strpos($link, '@') !== false) { // EMAIL
            $type = '-mail';
            if (strpos($link, ':') === false) {
                $link = 'mailto:' . $link;
            }
        } elseif ($link[0] == '+') { // PHONE
            $type = '-phone';
            $link = 'tel:' . $link;
        } elseif (strpos($link, '//') !== false || strpos($link, ':') > 2) { // URL ou Protocol
            $type = '-url';
        } else { // URL
            $type = '-url';
            $link = '//' . trim($link, '//');
        }

        // le texte affiché
        $label = $options['label'];
        if (empty($label)) {
            $label = $options['link'];
            if (strpos($label, '//')) {
                $label = substr($label, (strpos($label, '//') + 2));
            }
        }

        // $label = str_replace('@', '[at]', $label);
        $icon = '';
        if ($options['icon']) {
            if ($options['icon'] == '1') {
                $icon = $options['icon' . $type];
            } else {
                $icon = $options['icon'];
            }
            if (strtoupper(substr($icon, 0, 2)) == 'UX') {
                $icon = '&#' . substr($icon, 1) . ';';
            } elseif (UpHelper::preg_string($this,'#.(png|jpg|gif)#i', $icon)) {
                $icon = '<img src="' . $icon . '">';
            } else {
                $options['icon-style'] .= ';' . $options['font-prefix'] . $icon;
                $icon = ' ';
            }
            UpHelper::get_attr_style($this,$attr_icon, $options['icon-style']);
            $icon = UpHelper::set_attr_tag($this,'span', $attr_icon, $icon);
            $label = '&nbsp;' . $label;
        }
        if ($icon == '' && ($options['class'] || $options['style'])) {
            $label = UpHelper::set_attr_tag($this,'span', $attr_main, $label);
        }

        $label = $icon . trim($label, '/');

        // code en retour
        $attr_main['href'] = $link;
        $attr_main['id'] = $options['id'];
        if ($options['blank']) {
            $attr_main['target'] = '_blank';
        }

        $html[] = UpHelper::set_attr_tag($this,'a', $attr_main, $label);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
