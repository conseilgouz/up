<?php

/**
 * afficher/cacher un bloc HTML à l'aide d'un bouton 'lire la suite'
 *
 * syntaxe:
 * {up readmore=texte bouton | textless=replier} contenu caché {/up readmore}
 * 
 *
 * @author   Lomart
 * @version  UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit   script de <a href="https://www.skyminds.net/jquery-script-toggle-pour-afficher-et-cacher-de-multiples-blocs-html/#read" traget="_blank">Matt</a>
 * @tags   layout-dynamic
 */
/*
 * v1.63 - correction valeur defaut pour bouton
 * v2.6 - ajout option panel-style pour mettre en évidence le contenu
 * v2.9 - ajout options textmore-class et textless-class pour styler le bloc inline du bouton
 * v3.0 - suppression ancre pour cursor:pointer + ajout textmore-style et textless-style
 * v3.1 - refonte complete
 */
defined('_JEXEC') or die();

class readmore extends upAction
{

    function init()
    {
        $this->load_file('readmore.js');
        $this->load_file('readmore.css');
        return true;
    }

    function run()
    {

        // cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // texte bouton OUVRIR (idem textmore)
            'btn-position' => 'after', // emplacement des boutons. before=au-dessus, after=au-dessous
            /* [st-more] Texte et style des boutons */
            'textmore' => 'lire la suite', // texte bouton OUVRIR
            'textmore-style' => '', // classe et style pour le bouton OUVRIR
            'textmore-class' => '', // classe et style pour le bouton OUVRIR
            'textless' => 'replier', // texte bouton FERMER
            'textless-style' => '', // classe et style pour le bouton FERMER
            'textless-class' => '', // classe et style pour le bouton FERMER
            /* [st-panel] Style pour le panneau contenu */
            'panel-style' => '', // classes et style pour le contenu (v2.6)
            'panel-visible' => '0', // hauteur visible du contenu quand masqué (px ou sélecteur CSS)
            'panel-actif' => '', // événement javascript sur la partie visible du contenu pour dérouler/ enrouler le contenu. 
            'panel-overlay' => '', // affiche un dégradé pour masque le bas du panel-visible. (vide=style standard ou règles CSS)
            'panel-speed' => '', // vitesse d'apparition du contenu. Par défaut: 750ms

            /* [st-css] Style CSS des boutons */
            'id' => '', // identifiant
            'class' => 'bg-grisPale bg-gris bg-hover-grisClair p1 tc', // classe(s) pour les boutons OUVRIR et FERMER
            'style' => '', // idem
            'css-head' => '' // règles CSS ajoutées dans le HEAD
        );

        // ============================================
        // fusion et controle des options
        // ============================================

        $options = $this->ctrl_options($options_def);

        if ($options[__class__] != '') { // v2.9
            $options['textmore'] = $options[__class__];
        }

        $btnAfter = (strtolower($options['btn-position']) == 'after');

        // ============================================
        // === styles
        // ============================================

        // --- le bloc boutons
        // non pris en compte si vide ou 0
        $options['class'] = ($options['class']=='1') ? '' : $options['class'];
        $options['style'] = ($options['style']=='1') ? '' : $options['style'];
        if ($options['class'] || $options['style']) {
            $attr_btns['class'] = 'uprm-btns';
            $this->get_attr_style($attr_btns, $options['class'], $options['style']);
        }
        // --- bouton MORE
        $textmore = $this->get_bbcode($options['textmore']);
        $attr_btn_more['class'] = 'uprm-btn-more';
        if ($options['textmore-class'] || $options['textmore-style']) {
            $attr_btnmore_span = array();
            $this->get_attr_style($attr_btnmore_span, $options['textmore-class'], $options['textmore-style']);
            $textmore = $this->set_attr_tag('span', $attr_btnmore_span, $textmore);
        }

        // --- bouton LESS
        $attr_btn_less['class'] = 'uprm-btn-less inactive';
        $textless = $this->get_bbcode($options['textless']);
        if ($options['textless-class'] || $options['textless-style']) {
            $attr_btnless_span = array();
            $this->get_attr_style($attr_btnless_span, $options['textless-class'], $options['textless-style']);
            $textless = $this->set_attr_tag('span', $attr_btnless_span, $textless);
        } else {}

        // --- PANEL
        $attr_panel['class'] = 'uprm-panel inactive';
        $this->get_attr_style($attr_panel, $options['panel-style']);
        if ($options['panel-speed'])
            $this->add_str($options['css-head'], '#id .uprm-panel[transition:height ' . (int) $options['panel-speed'] . 'ms ease-out;]');

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // ============================================
        // === code JS
        // ============================================

        // === Paramétres JS
        $js_options = array();
        if (! empty($options['panel-visible'])) {
            if ((int) $options['panel-visible'] !== 0) {
                $js_options['panelMinHeight'] = (int) $options['panel-visible'] . 'px';
            } else {
                $js_options['panelMinSelector'] = $options['panel-visible'];
            }
        }
        $js_events= ',click,dblclick,mouseenter';
        $js_options['panelEvent'] = $this->ctrl_argument($options['panel-actif'],$js_events);

        $js_params = $this->json_arrtostr($js_options);

        $js_code = 'readmore("#' . $options['id'] . '", ' . $js_params . ')';
        $this->load_js_code($js_code);

        // === le contenu
        $overlay = '';
        if (! empty($options['panel-overlay'])) {
            $attr_overlay['class'] = 'uprm-overlay';
            if ($options['panel-overlay'] != '1')
                $this->get_attr_style($attr_overlay, $options['panel-overlay']);
            $overlay = $this->set_attr_tag('div', $attr_overlay, true);
        }

        $panel = $this->set_attr_tag('div', $attr_panel);
        $panel .= $overlay;
        $panel .= $this->content;
        $panel .= '</div>';

        // === retour HTML
        $out = '<div id="' . $options['id'] . '">';
        if ($btnAfter)
            $out .= $panel;
        $out .= $this->set_attr_tag('_div', ($attr_btns ?? [])); // si class pour btns
        $out .= $this->set_attr_tag('a', $attr_btn_more, $textmore);
        $out .= $this->set_attr_tag('a', $attr_btn_less, $textless);
        $out .= (isset($attr_btns)) ? '</div>' : '';  // si class pour btns
        if (! $btnAfter)
            $out .= $panel;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
