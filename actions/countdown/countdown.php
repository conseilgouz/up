<?php

/**
 * affiche un compte à rebours ou une horloge digitale
 *
 * syntaxe:
 *   {up countdown=201801010000}  // délai jusqu'à une date
 *   {up countdown=120}           // compte à rebours en secondes
 *   {up countdown}               // affiche une horloge
 *
 * @author    Lomart
 * @version   UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/Lexxus/jq-timeTo" target="_blank">Lexxus - jq-timeTo</a>
 * @tags  Widget
 */

/*
 * v2.6 - format des dates identique à countdown-simple
 * - ajout option filtre
 * v2.9 - suppression options JS inutiles
 */
defined('_JEXEC') or die();

class countdown extends upAction
{
    public function init()
    {
        // ===== Ajout dans le head (une seule fois)
        $this->load_file('timeTo.css');
        $this->load_file('jquery.time-to.min.js');
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut (hors JS)
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // date, nombre de secondes ou vide pour horloge
            'align' => '', // left, center ou right
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe
            'style' => '', // style inline
            'filter' => '' // condition d'éxécution
        );

        // ===== paramétres spécifique pour JS
        // traite a part pour avoir uniquement ceux indique
        $js_options_def = array(
            /* [st-JS] paramétres Javascript pour configuration du compteur */
            'callback' => '', // Fonction appelée à la fin du compte à rebours
            'captionSize' => 0, // fontsize legendes
            'countdownAlertLimit' => 10, // alerte en seconde avant fin countdown
            'displayCaptions' => 0, // 1 = légendes affichées
            'displayDays' => 0, // nb chiffres affichés pour jours
            'fontFamily' => 'Verdana, sans-serif', // Police pour chiffres
            'fontSize' => 28, // Taille police en pixels pour chiffres
            'lang' => 'en', // Défini automatiquement par UP
            'seconds' => 0, // Temps initial en secondes pour le compte à rebours
            'start' => 1, // démarrer automatiquement la minuterie
            'theme' => 'white' // style : white, black ou blue
        );

        $tz = $this->get_action_pref('timezone', 'Europe/Paris');
        date_default_timezone_set($tz);

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // === Date cible ou delai ?
        $targetDate = trim($options[__class__]);
        if ($targetDate != '') {
            if (strlen($targetDate) < 4) { // nombre de secondes sans le plus (compatibilité ascendante)
                $targetDate = '+' . $targetDate . 'seconds';
            }
            if ($targetDate[0] == '+') {
                $term_fr = array(
                    'année',
                    'an',
                    'mois',
                    'jour',
                    'semaine',
                    'heure',
                    'seconde'
                );
                $term_en = array(
                    'year',
                    'year',
                    'month',
                    'day',
                    'week',
                    'hour',
                    'second'
                );
                $targetDate = str_ireplace($term_fr, $term_en, $targetDate);
            }
            $targetDate = date('Y/m/d H:i:s', strtotime($targetDate));
        }

        // ---- conversion params JS en chaine JSON
        $js_params = $this->only_using_options($js_options_def);
        $js_params = $this->json_arrtostr($js_params);

        // -- initialisation
        // ==== le code JS
        $js_code = '$("#' . $options['id'] . '").timeTo(';
        if ($targetDate > '') {
            $js_code .= 'new Date("' . $targetDate . '"),';
        }
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);

        // ==== Attribut STYLE pour le div principal
        $attr_out['id'] = $options['id'];
        $attr_out['class'] = $options['class'];
        $this->add_class($attr_out['class'], 'clear');
        $attr_out['style'] = $options['style'];
        $this->add_style($attr_out['style'], 'text-align', $options['align']);
        // correction bug : forcer hauteur si fontsize plus grand
        if (isset($options['fontSize'])) {
            $coef = 1.10;
            if (isset($options['displayCaptions']) && $options['displayCaptions']) {
                $coef = 1.80;
            }
            $this->add_style($attr_out['style'], 'height', ($options['fontSize'] * $coef) . 'px');
        }

        // ==== le HTML
        $out = $this->set_attr_tag('div', $attr_out) . '</div>';

        return $out;
    }

    // run
}

// class
