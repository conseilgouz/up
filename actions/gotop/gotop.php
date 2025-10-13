<?php

/**
 * Affiche un bouton avec indicateur de position pour revenir en haut de page.
 *
 * syntaxe {up gotop}
 *
 * @version  UP-2.9
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script Ivan Grozdic de https://codepen.io/ig_design/pen/yrwgwO</a>
 * @tags    Expert
 *
 */
defined('_JEXEC') or die();

class gotop extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('gotop.css');
        $this->load_file('gotop.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // texte ou caractère unicode pour la flèche. ex \25b2, \21ea ou top
            'circle-bgcolor' => '', // couleur de fond du cercle
            'circle-color' => '', // couleur de la bordure du cercle
            'circle-color-active' => '', // couleur de la bordure pour la partie activé
            'icon-color' => '', // couleur pour l'icone ou le texte
            'icon-color-hover' => '', // couleur pour l'icone ou le texte lors du survol
            /* [st-divers] Divers */
            'id' => '', // identifiant
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $css = '';
        $css .= (empty($options[__class__])) ? '' : '--progress-icon:"' . $options[__class__].'";';
        $css .= ($options['circle-bgcolor']) ? '--progress-circle-bgcolor:' . $options['circle-bgcolor'].';' : '';
        $css .= ($options['circle-color']) ? '--progress-circle-color:' . $options['circle-color'] .';' : '';
        $css .= ($options['circle-color-active']) ? '--progress-circle-color-active:' . $options['circle-color-active'].';' : '';
        $css .= ($options['icon-color']) ? '--progress-icon-color:' . $options['icon-color'] .';' : '';
        $css .= ($options['icon-color-hover']) ? '--progress-icon-color-hover:' . $options['icon-color-hover'].';' : '';
        if (! empty($css)) {
            $css = ':root{' . $css . '}';
        }

        // === CSS-HEAD
        $css .= $options['css-head'];
        $this->load_css_head($css);
        $id = '#' . $options['id'];

        // code en retour
        $out = <<<STR
        <div class="progress-wrap $id">
          <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
          <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
          </svg>
        </div>
        STR;

        return $out;
    }

    // run
}

// class
