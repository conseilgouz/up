<?php

/**
 * Cherche un mot dans la page courante
 *
 * syntaxe {up page-search}
 *
 * @author   LOMART
 * @version  UP-2.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/text/Fast-Live-Search-Plugin-With-Text-Highlighting-Gsearch.html" target"_blank">fork of script Fast Live Search de gurudaths</a>
 * @tags    Widget
 *
 * */

/*
 * v2.8 : fix force int sur abs (lign 85 et 86)
 */
defined('_JEXEC') or die();

class page_search extends upAction
{

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     *
     * @return true
     */
    function init()
    {
        $this->load_file('gsearch.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => 'body', // sélecteur CSS du bloc où rechercher. J3=.article-details OU J4=.com-content-article__body
            /* [st-pos] Position de la zone de recherche */
            'search-top' => '80px', // position verticale de la zone de recherche. positif: top, négatif: bottom
            'search-left' => '80px', // position horizontale de la zone de recherche. positif=left, négatif= right
            /* [st-search] Icone et texte de la zone de recherche */
            'search-icon' => 'loupe-64-red.png', // image pour le bouton. Si le chemin n'est pas indiqué, l'image est dans le dossier de l'action.
            'search-text' => 'lang[en=Search;fr=Rechercher]', // texte indice dans la zone de recherche (Placeholder)
            /* [st-find]  Mise en évidence du résultat */
            'highlight-bg' => 'yellow', // couleur de surlignage des mots trouvés
            /* [st-css] Style CSS */
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // === Chemin de l'icon
        $icon = $options['search-icon'];
        if (dirname($icon) == '.') {
            $icon = '/' . str_replace('\\', '/', $this->actionPath) . $icon;
        }

        // === Script
        $js[] = '$(document).ready(function () {';
        $js[] = '$("#' . $options["id"] . '").GSearch({';
        $js[] = 'content_main: $("' . $options[__class__] . '"),';
        $js[] = 'search_icon: "' . $icon . '",';
        $js[] = 'search_text: "' . $options['search-text'] . '",';
        $js[] = 'background_color:"' . $options['highlight-bg'] . '",';
        $js[] = '});';
        $js[] = '});';
        $this->load_jquery_code(implode(PHP_EOL, $js));

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === la position de la loupe
        $style = 'position:fixed;z-index:99999;';
        $style .= (($options['search-top'] < 0) ? 'bottom' : 'top') . ':' . abs((int) $options['search-top']) . 'px;';
        $style .= (($options['search-left'] < 0) ? 'right' : 'left') . ':' . abs((int) $options['search-left']) . 'px;';

        // === le code HTML
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $style, $options['style'], $options['class']);

        // return implode(PHP_EOL, $html);
        $html = $this->set_attr_tag('span', $attr_main, true);

        return $html;
    }

    // run
}

// class


