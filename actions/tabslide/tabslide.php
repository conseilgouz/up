<?php

/**
 * ajoute des onglets pour ouvrir un panneau sur les bords de la fenêtre
 *
 * syntaxe {up tabslide=btn-text | tabLocation=top}contenu{/up tabslide}
 *
 * utilisation :
 * - un sommaire sur le coté du site
 * - un module connexion
 *
 * @author    Lomart
 * @version   UP-1.0
 * @credit    script de <a href="https://github.com/hawk-ip/jquery.tabSlideOut.js" target="_blank">hawk-ip</a>
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Body
 */

/*
 * v5.1 - force action sur click pour mobile + max-width sur panel
 */
defined('_JEXEC') or die();

use Joomla\CMS\Environment\Browser;

class tabslide extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('up-tabSlideOut.css');
        $this->load_file('jquery.tabSlideOut.min.js');
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
        /* [st-title] l'onglet */
            __class__ => '', // titre de l'onglet
            'tab-style' => '', // classes et styles inline pour onglets
            'tab-class' => '', // classe(s) pour onglet (obsolète)
            /* [st-panel] le panneau */
            'id' => '', // identifiant
            'panel-style' => '', // classes et styles inline pour panneau
            'panel-class' => '', // classe(s) pour panneau (obsolète)
            /* [st-obsolete] Options JS disparues - conservés pour compatibilité */
            'speed' => 300, // OBSOLETE voir bounceSpeed
            'positioning' => 'fixed', // or absolute, so tabs move when window scrolls
            'toggleButton' => '.tab-opener' // not often used
        );

        // ===== param JS
        $js_options_def = array(
        /* [st-tab] paramétres JS : définition de l'onglet */
            'tabLocation' => 'left', // position : left, right, top ou bottom
            'onLoadSlideOut' => 0, // slide out after DOM load
            'clickScreenToClose' => 1, // fermer l'onglet lorsque le reste de l'écran est cliqué
            'tabHandle' => '.handle', // Sélecteur JQuery pour l'onglet, peut utiliser #
            'action' => 'click', // mode ouverture : 'hover' ou 'click'. Forcer à click sur mobile
            'hoverTimeout' => 5000, // sélai en ms pour garder l'onglet ouvert après la fin du survol - uniquement si action = 'hover'
            'offset' => '200px', // distance pour top or left (bottom or right si offsetReverse est vrai)
            'offsetReverse' => 0, // true= aligné a droite ou en bas
            'otherOffset' => null, // si défini, la taille du panneau est définie pour maintenir cette distance à partir du bas ou de la droite (haut ou gauche si offsetReverse)
            'handleOffset' => null, // Si null, détecte la bordure du panneau pour bien aligner la poignée, sinon la distance en px
            'handleOffsetReverse' => 0, // si vrai, poignée alignée avec la droite ou le bas du panneau
            /* [st-anim] paramétres JS : Animation */
            'bounceDistance' => '50px', // distance autorisée pour le rebond
            'bounceTimes' => 4, // nombre de rebonds si 'bounce' est appelé
            'bounceSpeed' => 300, // vitesse d'animation des rebonds
            /* [st-img] paramétres JS : image pour l'onglet */
            'pathToTabImage' => null, // image facultative à afficher dans l'onglet
            'imageHeight' => null, // hauteur image
            'imageWidth' => null, // largeur image
            /* [st-expert] paramétres JS : pour expert */
            'onOpen' => function () {}, // appelé après l'ouverture
            'onClose' => function () {}, // appelé après la fermeture
            'onSlide' => function () {} // appelé après l'ouverture ou la fermeture
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // l'action hover n'est pas disponible sur mobile v5.1
        if (isset($this->options_user['action'])) {
            $browser = Browser::getInstance();
            if ($browser->isMobile()) {
                $options['action'] = 'click';
            }
        }

        // les options saisis par l'utilisateur concernant le script JS
        $js_options = $this->only_using_options($js_options_def);
        $js_params = $this->json_arrtostr($js_options);

        // -- ajout code initialisation JS
        $js_code = '$("#' . $options['id'] . '").tabSlideOut(';
        $js_code .= $js_params;
        $js_code .= ');';
        // ajout du code dans head
        $this->load_jquery_code($js_code);

        // ===== STYLER

        $attr_tab['class'] = 'handle';
        $this->get_attr_style($attr_tab, $options['tab-class'], $options['tab-style']);

        $attr_panel['id'] = $options['id'];
        $this->get_attr_style($attr_panel, $options['panel-class'], $options['panel-style']);

        // === code spécifique à l'action
        // qui doit retourner le code pour remplacer le shortcode

        $out = $this->set_attr_tag('div', $attr_panel);
        $out .= $this->set_attr_tag('a', $attr_tab);
        $out .= ($options[__class__] == 1) ? "" : $options[__class__];
        $out .= '</a>';
        $out .= '<div style="max-width:85vw">'.$this->content.'</div>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
