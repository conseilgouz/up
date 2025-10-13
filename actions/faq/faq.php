<?php

/**
 * accordion très simple
 *
 * syntaxe : une alternance de titres pour les onglets en H4 et de contenu HTML
 * {up faq}
 * -- titre en H4
 * -- contenu HTML
 * {/up faq}
 *
 *
 * @author    lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="http://jsfiddle.net/ryanstemkoski/6gbq0yLv/" target="_blank">ryans temkoski</a>
 * @tags layout-dynamic
 */

/*
 * v1.33 - ajout classe active sur titre ouvert
 * v2.5 - ajout option css-head
 * - modification nom des classes pour identifier chaque onglet
 * v2.9 - ajout option title-tag-preserve
 * v5.1 - ajout option filter
 * - class et style confondu
 */
defined('_JEXEC') or die();

class faq extends upAction
{

    function init()
    {
        // ===== Ajout dans le head (une seule fois)
        $this->load_file('faq.css');

        // -- le JS
        $this->load_file('/plugins/content/up/assets/js/faq.js');
    }

    function run()
    {
        // cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // contenu obligatoire
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // ===== valeur paramétres par défaut (hors JS)
        // il est indispensable de tous les définir ici
        $options_def = array(
            $this->name => '', // aucun argument nécessaire
            /* [st-title] Définition des titres des onglets */
            'title-tag' => 'h4', // pour utiliser une autre balise pour les titres
            'title-tag-preserve' => '0', // 1 pour conserver 'title-tag' au lieu de div
            'title-class' => '', // classe et/ou style inline pour le titre (onglet)
            'title-style' => '', // classe et/ou style inline pour le titre
            /* [st-content] Définition des panneaux */
            'content-class' => '', // classe et/ou style inline pour le contenu
            'content-style' => '', // classe et/ou style inline pour le contenu
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'css-head' => '', // style ajouté dans le HEAD
            /* [st-divers] Divers */
            'filter' => '' // conditions. Voir doc action filter
        );
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === code spécifique à l'action
        // qui doit retourner le code pour remplacer le shortcode
        // <div id="upfaq">
        // <div class="upfaq-button">Button 1</div>
        // <div class="upfaq-content">Content<br />More Content<br /></div>
        // <div class="upfaq-button">Button 2</div>
        // <div class="upfaq-content">Content</div>
        // </div>
        // -- les styles
        $attr_title['class'] = 'upfaq-button';
        $this->get_attr_style($attr_title, $options['title-class'], $options['title-style']);
        $attr_title_bak = $attr_title;

        $attr_content['class'] = 'upfaq-content';
        $this->get_attr_style($attr_content, $options['content-class'], $options['content-style']);
        $attr_content_bak = $attr_content;

        // -- titre + contenu RESTE A REPRENDRE STYLE DU H4
        $tag = $options['title-tag'];
        $regex_title = '#<' . $tag . '.*>(.*)</' . $tag . '>#siU';
        preg_match_all($regex_title, $this->content, $array_title);
        $regex_text = '#</' . $tag . '>(.*)<' . $tag . '.*>#siU';
        preg_match_all($regex_text, $this->content . '<' . $tag . '>', $array_txt);
        $nb = count($array_title[1]);

        // -- code retour
        $title_tag = ($options['title-tag-preserve']) ? $options['title-tag'] : 'div';
        $out = '<div class="upfaq" id="' . $options['id'] . '">';
        for ($i = 0; $i < $nb; $i ++) {
            $attr_title['class'] .= ' upfaq-title-' . ($i + 1);
            $attr_content['class'] .= ' upfaq-content-' . ($i + 1);
            $out .= $this->set_attr_tag($title_tag, $attr_title, $array_title[1][$i]);
            $out .= $this->set_attr_tag('div', $attr_content, $array_txt[1][$i]);
            $attr_title = $attr_title_bak;
            $attr_content = $attr_content_bak;
        }
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
