<?php

/**
 * une image clicable et responsive
 *
 * syntaxe {up imagemap=chemin_image}
 *     < area target="" alt="" title="" href="" coords="" shape="">;
 *     OU pour prévenir l'effacement par editeur (remplacer les <> par [])
 *     [area target="" alt="" title="" href="" coords="" shape=""];
 * {/up imagemap}
 *
 * utiliser un générateur en ligne pour définir les zones : <a href="https://www.image-map.net/">www.image-map.net</a>
 *
 * @author   LOMART
 * @version  UP-1.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/stowball/jQuery-rwdImageMaps" target"_blank">script RWD Image Maps de stowball</a>
 * @tags  image
 */

/*
 * v2.3 - possibilité de saisie du contenu (areas) en bbcode pour eviter effacement par editeur
 */

defined('_JEXEC') or die;

class imagemap extends upAction {

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     * @return true
     */
    function init() {
        $this->load_file('jquery.rwdImageMaps.min.js');
        $this->load_jquery_code('$(\'img[usemap]\').rwdImageMaps();');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // si cette action a obligatoirement du contenu
        if (!$this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // le chemin et nom de l'image
            /* [st-css] Style CSS*/
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '' // style inline ajouté au bloc principal
        );
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === le code HTML
        // -- le tag img
        $img_tag['src'] = $options[__class__];
        $img_tag['usemap'] = '#' . $options['id'];
        $img_tag['class'] = $options['class'];
        $img_tag['style'] = $options['style'];

        // -- les area. si saisi dans un éditeur WYSIWYG, il faut décoder
        // v2.3 : pour eviter effacemment, on peut remplacer les <> par [] (bbcode)
        $content = strip_tags($this->content);
        $content = $this->get_bbcode($content, '+area');
        // supprimer les espaces de présentaion du shortcode
        $content = preg_replace('#\>[\xA0| |\xC2]*\<#', '><', $content);
        $content = substr($content, strpos($content, '<'));

        // -- le code en retour
        $out = $this->set_attr_tag('img', $img_tag);
        $out .= '<map name="' . $options['id'] . '">';
        $out .= $content;  // le contenu entre les shortcodes ouvrant et fermant
        $out .= '</map>';

        return $out;
    }

// run
}

// class
