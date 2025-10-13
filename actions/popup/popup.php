<?php

/**
 * Ouvre un popup après un délai ou une position dans la page
 *
 * syntaxe {up popup=5s}contenu popup{/up popup} Ouvre le popup 5 secondes après le chargement de la page 
 * syntaxe {up popup=50%}contenu popup{/up popup} Ouvre le popup à la moitié de la page
 * syntaxe {up popup=#bloc}contenu popup{/up popup} Ouvre le popup quand le haut du bloc #bloc est en haut de la zone visible du navigateur
 * 
 * @author   LOMART
 * @version  UP-3.0 
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    HTML
 *
 * */
defined('_JEXEC') or die();

class popup extends upAction
{

    /**
     * charger les ressources communes à toutes les instances de l'action
     */
    function init()
    {
        $this->load_file('popup.css');
        $this->load_file('popup.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            /*[st-main] Mode et conditions d'exécution*/
            __class__ => '5s', // Délai en sec (15s), position dans la page (50%) ou sélecteur de bloc (#bloc)
            'scroll-offset' => '3', // x = tolérance avant-après pour le scroll. x1-x2 = avant et après
            'filter' => '', // condition pour exécuter l'action
            'cookie-duration' => 0, // nombre de jours de conservation des cookies. 0 pour la session ou -1 pour ignorer

            /* [st-popup] Position et style de la fenêtre surgissante */
            'popup-position' => '', // position YX du popup : TL, TC, TR, CL, CC, CR, BL, BC, BR
            'popup-style' => '', // classe ou style pour la fenetre popup

            /* [st-overlay] Arrière-plan */
            'overlay-lock' => 1, // 1: le popup bloque la navigation sur la page
            'overlay-style' => '', // classe ou style pour masquer/atténuer le contenu

            /* [st-btn] Bouton fermeture popup */
            'close-only-button' => 1, // 1: fermeture uniquement par le bouton, 0: en cliquant hors du popup
            'close-style' => '', // classe ou style pour la fenetre popup
            'close-label' => '&times;', // texte ou symbole pour le bouton. BBCode et action UP admis

            /* [st-anim] Animation */
            'animation-in' => '', // classe unique pour animation ouverture du popup
            'animation-out' => '', // classe unique pour animation fermeture du popup
            'animation-target' => 'popup', // popup ou overlay: cible de l'animation

            /* [st-div] Divers */
            'id' => '', // id genérée automatiquement par UP. A préciser pour les cookies dans les modules
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        $options = $this->ctrl_options($options_def);

        // === doit-on exécuter ?
        if ($this->filter_ok($options['filter']) !== true)
            return '';

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // =============================
        // =========== le code JS
        // =============================

        if (substr($options[__class__], - 1) == '%') {
            $js_options['activationValue'] = (int) $options[__class__];
            $js_options['activationMode'] = 'scroll';
        } elseif (strtolower(substr($options[__class__], - 1)) == 's' && (int) $options[__class__] > 0) {
            $js_options['activationValue'] = ((int) $options[__class__] * 1000);
            $js_options['activationMode'] = 'time';
        } else {
            $js_options['activationValue'] = $options[__class__];
            $js_options['activationMode'] = 'position';
        }
        $scroll = explode('-', $options['scroll-offset']);
        $js_options['scrollOffsetTop'] = $scroll[0];
        $js_options['scrollOffsetBottom'] = (isset($scroll[1])) ? $scroll[1] : $scroll[0];

        $js_options['bodyBlocked'] = $options['overlay-lock'];

        $js_options['cookieName'] = 'popup-' . $options['id'];
        $js_options['cookieDuration'] = $options['cookie-duration'];

        $js_options['animIn'] = $options['animation-in'];
        $js_options['animOut'] = $options['animation-out'];
        $js_options['animTarget'] = $options['animation-target'];

        $js_options['closeOnlyButton'] = $options['close-only-button'];

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options);

        // -- initialisation
        $js_code = 'popup("#' . $options['id'] . '", ' . $js_params . ')';
        $this->load_js_code($js_code);

        // =============================
        // =========== Le CSS
        // =============================

        // ---- position fenêtre sous la forme
        // tl tc tr hg hc hd
        // cl cc cr ou cg cc cd
        // bl bc br bg bc bd
        if ($options['popup-position']) {
            $pos = strtr(strtolower($options['popup-position']), 'hgd', 'tlr');
            $poslist = 'cc,tl,tc,tr,cl,cr,bl,bc,br';
            $pos = $this->ctrl_argument($pos, $poslist);

            $align = array(
                't' => 'flex-start',
                'b' => 'flex-end',
                'l' => 'flex-start',
                'r' => 'flex-end',
                'c' => 'center'
            );
            $css = '#id .popup-overlay[';
            $css .= 'align-items:' . $align[$pos[0]] . ';'; // Y
            $css .= 'justify-content:' . $align[$pos[1]] . ';'; // X
            $css .= ']';
            $this->load_css_head($css);
        }

        // -- l'animation
        $target = strtolower($options['animation-target']);
        $animPopup = ($target == 'popup') ? $options['animation-in'] : '';
        $animOverlay = ($target != 'popup') ? $options['animation-in'] : '';

        // -- l'overlay
        $attr_main['id'] = $options['id'];
        $attr_main['style'] = 'display:none';

        $attr_overlay['class'] = 'popup-overlay';
        $this->get_attr_style($attr_overlay, $options['overlay-style'], $animOverlay);

        // -- le popup
        $attr_popup['class'] = 'popup-content';
        $this->get_attr_style($attr_popup, $options['popup-style'], $animPopup);

        // -- le bouton close
        $attr_close['class'] = 'popup-close';
        $this->get_attr_style($attr_close, $options['close-style']);

        // ======================================
        // ======== le code HTML en retoour
        // ======================================
        $html[] = $this->set_attr_tag('div', $attr_main);
        $html[] = $this->set_attr_tag('div', $attr_overlay);
        $html[] = $this->set_attr_tag('div', $attr_popup);
        $html[] = $this->set_attr_tag('div', $attr_close, $this->get_bbcode($options['close-label']));
        $html[] = $this->content;
        $html[] = '</div>'; // popup
        $html[] = '</div>'; // overlay
        $html[] = '</div>'; // main

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
