<?php

/**
 * affiche une carte google pour une adresse
 *
 * syntaxe : {up gmap=1 rue de la paix, Paris}
 * IMPORTANT: il faut saisir son APIKey dans les paramétres du plugin sous la forme: gmap-key=apikey
 *
 * @author     lomart
 * @version    UP-0.9
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Widget
 */

/*
 * v2.9  - ajout option rgpd pour exclure le shortcode de la gestion par tarteaucitron
 */

defined('_JEXEC') or die;

class gmap extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ==== PARAMETRES
        $options_def = array(
            $this->name => '', // adresse postale
            'width' => '100%', // largeur de la carte
            'height' => '300px', // hauteur de la carte
            /*[st-rgpd] Gestion RGPD (tarteaucitron) */
            'rgpd' => '1', // 0 pour ne pas appliquer la règle pour le RGPD
            /*[st-css] Styles CSS */
            'class' => '', // classe
            'style' => '',   // style-inline
            'id' => '', // identifiant
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['width'] = $this->ctrl_unit($options['width'], '%,px,rem,vw');
        $options['height'] = $this->ctrl_unit($options['height'], 'px,rem,vh');
        // recup APIKEY dans les params de UP
        $options['apikey'] = $this->get_action_pref('gmap-key');

        $main_attr['style'] = $options['style'];
        $main_attr['class'] = $options['class'];
        // add_style($main_attr['style'], 'width', $options['width']);
        // ==== EXECUTION
        if ($options['apikey'] !== false) {
            $address = str_replace(' ', '+', $options[$this->name]);
            if ($this->tarteaucitron && $options['rgpd']) {
                $out = $this->set_attr_tag('div', $main_attr);
                $out .= '<div';
                $out .= ' class="googlemapssearch"';
                $out .= ' data-search="' . $address . '"';
                $out .= ' width="' . $options['width'] . '"';
                $out .= ' height="' . $options['height'] . '"';
                $out .= ' data-api-key=' . $options['apikey'];
                $out .= '"></div>';
                $out .= '</div>';
            } else {
                $out = $this->set_attr_tag('div', $main_attr);
                $out .= '<iframe';
                $out .= ' class="googlemaps-canvas"';
                $out .= ' width="' . $options['width'] . '"';
                $out .= ' height="' . $options['height'] . '"';
                $out .= ' frameborder="0" style="border:0"';
                $out .= ' src="https://www.google.com/maps/embed/v1/place';
                $out .= '?key=' . $options['apikey'];
                $out .= '&q=' . $address;
                $out .= '"></iframe>';
                $out .= '</div>';
            }
        } else {
            $out = 'APIKEY not found. Please indicate it in up plugin parameters<br>form: gmap-key=<i>apikey</i>';
        }

        return $out;
    }

    // run
}

// class

// <div class="googlemapssearch" data-search="SEARCHWORDS"
// data-api-key="YOUR_GOOGLE_MAP_API_KEY" width="WIDTH" height="HEIGHT" ></div>
