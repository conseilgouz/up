<?php

/**
 * Affiche la position dans la page dans un cercle autour du curseur
 *
 * syntaxe {up scroll-indicator}
 *
 * @version  UP-2.9
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/other/custom-cursor-scroll-indicator.html" target"_blank">script de ig_design</a>
 * @tags   Editor
 *
 */
defined('_JEXEC') or die();

class scroll_indicator extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('scroll_indicator.css');
        $this->load_file('scroll_indicator.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // aucun argument
            'bg-color' => '', // couleur de base du cercle (rgba() ou #RRGGBBAA )
            'color' => '',  // couleur du segment indicateur (rgba() ou #RRGGBBAA )
            'size' => '', // largeur du segment indicateur (1 à 100)
            /* [st-css] Style CSS*/
            'id' => '',  // Identifiant
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        if ($options['bg-color']) {
            $options['css-head'] .= '.progress-wrap{box-shadow:inset  0 0 0 2px ' . $options['bg-color'] . ';}';
        }
        if ($options['color'] || $options['size']) {
            $options['css-head'] .= '.progress-wrap svg.progress-circle path{';
            if ($options['color']) {
                $options['css-head'] .= 'stroke:' . $options['color'].';';
            }
            if ($options['size']) {
                $options['css-head'] .= ' stroke-width:' . (int) $options['size'].';';
            }
            $options['css-head'] .= '}';
        }
        $this->load_css_head($options['css-head']);

        // code en retour
        $html = <<<'HTML'
        	<div class='cursor' id="cursor"></div>
        	<div class='cursor2' id="cursor2">					
        	<div class="progress-wrap">
        		<svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
        			<path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        		</svg>
        	</div>
            </div>
        	<div class='cursor3' id="cursor3"></div>
        HTML;

        return $html;
    }

    // run
}

// class
