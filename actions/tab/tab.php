<?php

/**
 * affiche du contenu dans des panneaux avec onglets en haut, à gauche ou à droite.
 * Mode responsive avec gestion de l'espacement vertical
 *
 * {up tab}
 * < h4>texte onglet< /h4>
 * < p>texte du panneau< /p>
 * < img src="..">
 * {/up tab}
 *
 * Sur mobile ou sur demande, l'affichage est en mode accordion
 *
 * @author    Lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    Script de <a href="http://www.jqueryscript.net/accordion/Responsive-Multipurpose-Tabs-Accordion-Plugin-With-jQuery.html" target="_blank">bhaveshcmedhekar</a>
 * script JS adapté par Lomart pour gestion répartition verticale
 * @tags  layout-dynamic
 */
/*
 * v1.33 - correction bug sur forcage accordion. Suppression espace sous onglets. CSS ul,li, prise en charge attributs dans regex. (merci woluweb)
 * v1.62 - ajout option auto par pleconte et correction regex
 * v1.63 - correction regex pour balise titre avec attributs
 * v1.7 - ajout option css-head, espace-vertical et content_display.
 * - ajout fichier SCSS et variables pour personnaliser les couleurs
 * - modif CSS: contenu = width 100%
 * v2.6 - auto : valeur minimum de 999 ms
 * - fix : erreur dans fichier css
 * v2.9 - ajout option preserve-tag
 * v5.1 - correction css pour fond des flèches transparentes
 * v5.2 - ajout option filter
 */
defined('_JEXEC') or die();

class tab extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('tab.css');
        $this->load_file('jquery.ttpanel.js');
        $this->load_file('jquery.multipurpose_tabcontent.min.js');
        return true;
    }

    public function run()
    {
        // cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        $options_def = array(
            __class__ => 'tab', // tab ou accordion
            'auto' => '', // 0 ou delai en millisecondes pour changement automatique d'onglet
            /* [st-title] Style des titres (onglets) */
            'title-tag' => 'h4', // balise utilisée pour les titres onglets
            'title-style' => '', // classe(s) et style inline onglets
            'title-class' => '', // classe(s) onglets (obsolète)
            /* [st-content] Style des panneaux de contenu */
            'content-style' => '', // classe(s) et style inline contenu
            'content-class' => '', // classe(s) contenu (obsolète)
            'espace-vertical' => '1', // 0, 1 ou 2 : niveau des éléments à répartir
            /* [st-main] Style du bloc principal */
            'id' => '', // identifiant
            'style' => '', // classe(s) et style(s) bloc principal
            'class' => '', // classe(s) bloc principal (obsolète)
            'css-head' => '', // règles CSS mises dans le head
            /* [st-divers] Divers */
            'filter' => '' // conditions. Voir doc action filter
        );

        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour configuration */
            'side' => '', // left ou right
            'active_tab' => '', // 1 a N
            'plugin_type' => '', // accordion (interne, ne pas modifier)
            'content_display' => 'block' // interne, ne pas modifier, défini par espace-vertical
        );

        // -- recup paramétre principal --> plugin_type
        if ($this->options_user[__class__] !== true) {
            $this->options_user['plugin_type'] = $this->options_user[__class__];
        }

        // === Mise place alignement vertical
        $attr_content['class'] = 'content_wrapper';
        if (isset($this->options_user['espace-vertical'])) {
            if ($this->options_user['espace-vertical'] > 0) {
                $this->options_user['content_display'] = 'flex';
                $attr_content['class'] .= ' fg-row fg-vspace-between-' . (int) $this->options_user['espace-vertical'];
                $attr_content['class'] .= ' m-child-raz-' . (int) $this->options_user['espace-vertical'];
            }
        }
        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }


        // =========== le code JS
        // les options saisis par l'utilisateur concernant le script JS
        $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        $js_params = $this->json_arrtostr($js_options);

        $js_code = '$("#' . $options['id'] . '").champ(';
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);

        // JS pour rotation auto
        $delay = (int) $options['auto'];
        if ($delay > 999) { // auto défilement des tabs
            $js_code = '$(function () {$("#' . $options['id'] . ' .tab_list").timerTabPanel({timeInterval:' . $delay . '});});';
            $this->load_jquery_code($js_code);
        }
        // === le code HTML
        // --- STYLES
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = 'tab_wrapper';
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        $attr_title['class'] = 'tab_list';
        $this->get_attr_style($attr_title, $options['title-class'], $options['title-style']);

        $this->get_attr_style($attr_content, $options['content-class'], $options['content-style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // -- titre + contenu RESTE A REPRENDRE STYLE DU H4

        $tag = $options['title-tag'];
        // $regex_title = '#<' . $tag . '.*>(.*)</' . $tag . '>#siU';
        $regex_title = '#(<' . $tag . '.*>)(.*)</' . $tag . '>#siU';
        preg_match_all($regex_title, $this->content, $array_title);
        $regex_text = '#</' . $tag . '>(.*)<' . $tag . '.*>#siU';
        preg_match_all($regex_text, $this->content . '<' . $tag . '>', $array_txt);
        $nb = count($array_title[0]);

        // ==== code retourne
        $out = $this->set_attr_tag('div', $attr_main);
        // --- les onglets
        $out .= $this->set_attr_tag('ul', $attr_title);
        $active = ' class="active"';
        for ($i = 0; $i < $nb; $i++) {
            $attr = $this->get_attr_tag($array_title[1][$i]);
            $attr['id'] = (empty($attr['id'])) ? 'tab_' . ($i + 1) : $attr['id'];
            $out .= $this->set_attr_tag('li', $attr, $array_title[2][$i]);
            $active = '';
        }
        $out .= '</ul>';
        // --- les contenus
        $out .= $this->set_attr_tag('div', $attr_content);
        $active = ' active';
        for ($i = 0; $i < $nb; $i++) {
            $out .= '<div class="tab_content' . $active . '">';
            $out .= $array_txt[1][$i];
            $out .= '</div>';
            $active = '';
        }
        $out .= '</div>';

        $out .= '</div>';

        // on charge le code de gestion de l'ancre pour le 1er tab
        if ($this->firstInstance) {
            $js = "var hash = window.location.hash;";
            $js .= "var anchor = $(hash);";
            $js .= "if (anchor.length >= 0){ $(anchor).click(); }";
            $out .= $this->load_jquery_code($js, false);
        }
        return $out;
    }

    // run
}

// class
