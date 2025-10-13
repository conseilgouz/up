<?php

/**
 * Affiche une liste sous forme d'un arbre (treeview)
 *
 * syntaxe {up treeview} liste UL/LI {/up treeview}
 *
 * @version  UP-2.5
 * @author  LOMART
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/other/Smoot-Collapsible-Tree-View-Plugin-with-jQuery-TreeViewJS.html" target"_blank">script TreeViewJS de samarsault</a>
 * @tags    layout-dynamic
 *
 */
/*
 * v2.9 - ajout icônes fichiers
 * v3.0 - ajout option icon-size
 */
defined('_JEXEC') or die();

class treeview extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('treeView.css');
        $this->load_file('treeView.js');
        return true;
    }

    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // aucun argument
            /* [st-icon] icônes et ligne */
            'icon-folder' => 'arrow', // image pour les dossiers (noeuds)
            'icon-file' => '', // image pour éléments feuilles
            'icon-size' => '', // taille de l'icone. Ex: 48px,960:32px,1200:24px
            'custom-icon' => '', // liste des icônes personnalisées pour création CSS inline
            'line' => '0', // ligne devant les items. 1 (ligne par defaut) ou attributs pour border
            /* [st-open] Ouverture automatique et délai */
            'expand-all' => '0', // 1 pour ouvrir tous les noeuds
            'expand' => '0', // 1 pour ouvrir les noeuds de niveau 1
            'delay' => '0', // durée ouverture noeuds en msec
            /* [st-btn] Boutons ouverture et fermeture */
            'btn-open-selector' => '', // sélecteur du lien 'Tout déplier'
            'btn-close-selector' => '', // sélecteur du lien 'Tout déplier'
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $id = $options['id'];
        // === CSS
        $css = $options['css-head'];

        // ligne devant elements d'un dossier
        $options['line'] = ($options['line'] == '1') ? '2px dotted #444' : $options['line'];
        if ($options['line'])
            $css .= '#id.uptv ul {border-left:' . $options["line"] . ';}';

        // taille de l'icône
        if (! empty($options['icon-size'])) {
            $css .= $this->get_icon_size($options['icon-size'], '#' . $options['id']);
        }

        // icone noeud ouvert / ferme
        if (strlen($options['icon-folder']) > 2) {
            $css .= '#id li.tv-close{background-image:url(' . $this->get_url_relative($this->actionPath . 'icons/' . $options["icon-folder"] . '-close.png') . ');}';
            $css .= '#id li.tv-open{background-image:url(' . $this->get_url_relative($this->actionPath . 'icons/' . $options["icon-folder"] . '-open.png') . ');}';
        }
        // icone feuille
        if (strlen($options['icon-file']) > 2)
            $css .= '#id li:not(.tv-close) {background-image: url(' . $this->get_url_relative($this->actionPath . 'icons/' . $options['icon-file'] . ".png") . ');}';

        foreach (explode(',', $options['custom-icon']) as $icon) {
            if (file_exists($this->actionPath . 'icons/' . $icon . '-close.png') !== false) {
                // icônes pour dossier
                $css .= '#id li.' . $icon . '.tv-close{background-image:url(' . $this->get_url_relative($this->actionPath . 'icons/' . $icon . '-close.png') . ');}';
                $css .= '#id li.' . $icon . '.tv-open{background-image:url(' . $this->get_url_relative($this->actionPath . 'icons/' . $icon . '-open.png') . ');}';
            } elseif (file_exists($this->actionPath . 'icons/' . $icon . '.png') !== false) {
                // icone pour feuille
                $css .= '#id li.' . $icon . '{background-image:url(' . $this->get_url_relative($this->actionPath . 'icons/' . $icon . '.png') . ');}';
            } elseif (strpos($icon, '.') !== false) {
                $this->msg_error($icon . 'image not found in up/actions/treeview/icons');
            }
        }
        $this->load_css_head($css);

        // === JS
        $js_params = '{';
        $js_params .= ($options['expand-all']) ? 'expanded:true,' : '';
        $js_params .= ($options['expand']) ? 'expand:true,' : '';
        $js_params .= ($options['delay']) ? 'delay:' . (int) $options['delay'] . ',' : '';
        $js_params .= '}';
        $js = '$("#' . $id . '").treeView(' . $js_params . ');';
        if ($options['btn-open-selector'])
            $js .= '$("' . $options['btn-open-selector'] . '").click(function(){$("#' . $id . '").treeView("expandAll");});';
        if ($options['btn-close-selector'])
            $js .= '$("' . $options['btn-close-selector'] . '").click(function(){$("#' . $id . '").treeView("collapseAll");});';
        $this->load_jquery_code($js);
        // === Icone par item
        // si <li>[nomClasse]item 1 --> <li class="nomClasse">item 1
        $regex = '#\<li(.*)\>\[(.*)\]#U';
        while (preg_match($regex, $this->content, $matches)) {
            $attr = $this->get_attr_tag($matches[0]);
            $attr['class'] .= ' ' . $matches[2];
            $str = $this->set_attr_tag('li', $attr);
            $this->content = preg_replace($regex, $str, $this->content, 1);
        }

        // attributs du bloc principal
        // structure UL principale
        $main_tag_orig = $this->preg_string('#(<ul.*>)#U', $this->content);
        $attr_main = $this->get_attr_tag($main_tag_orig);
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, 'uptv', $options['class'], $options['style']);
        $main_tag_new = $this->set_attr_tag('ul', $attr_main);
        // code en retour
        $this->content = preg_replace('#' . $main_tag_orig . '#', $main_tag_new, $this->content, 1);

        return $this->content;
    }

    // run

    /*
     * get_icon_size($arg)
     * -------------------
     * crée les règles CSS avec mediaquerie pour la taille icone 
     * $arg: 48px, 960:32px, 1200: 24px, ...
     */
    function get_icon_size($arg, $id)
    {
        $out = '';
        $sizes = explode(',', $arg);
        foreach ($sizes as $size) {
            if (strpos($size, ':') === false) {
                $bp = '0';
                $val = trim($size);
            } else {
                list ($bp, $val) = explode(':', $size);
                $bp=(int)$bp;
                $val = trim($val);
            }
            $rules[$bp] = $val;
        }
        ksort($rules);
        foreach ($rules as $bp => $val) {
            $css = $id . '.uptv li{background-size:' . $val . '; padding-left:calc(' . $val . '*1.1); line-height:calc(' . $val . '* 1.1)}';
            if ($bp) {
                $out .= '@media(min-width:' . $bp . 'px){' . $css . '}';
            } else {
                $out .= $css;
            }
        }
        return $out;
    }
    // get_icon_size
}

// class
