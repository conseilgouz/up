<?php

/**
 * Affiche un compte à rebours simple et facilement adaptable par CSS
 *
 * syntaxe {up countdown-simple=AAAAMMJJHHMM}
 *
 * @author   LOMART
 * @version  UP-2.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/time-clock/countdown-date-loop-counter.html" target="_blank">Countdown From A Specific Date</a> de anik4e
 * @tags Widget
 */

/*
* v2.6 - format des dates identique à countdown
*  	- prise en charge des dates par iOS
*/

defined('_JEXEC') or die;

class countdown_simple extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('countdown_simple.css');
        $this->load_file('upcountdown.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // date cible ou délai si débute par +
            'model' => '', // CSS de base pour la présentation (digital, rainbow)
            'digit-view' => 'YMDHMS', // afficheurs. Mettre un X pour ne pas l'afficher. Pour les premiers non affichés, la valeur sera convertie et ajoutée au premier affiché
            /* [st-txt] Textes ajoutés au compteur */
            'intro-text' => '', // texte avant les afficheurs
            'close-text' => '', // texte après les afficheurs
            'prefix' => '', // texte avant valeur
            'suffix' => 'lang[en=years,months,days,hours,minutes,seconds;fr=années,mois,jours,heures,minutes,secondes]', // texte après valeur
            'elapsed-text' => 'lang[en=Too late;fr=Trop tard]', // Texte ou bbcode affiché si délai écoulé
            /* [st-digit] style des chiffres */
            'digit-class' => '', // classe(s) pour les chiffres
            'zero' => '0', // ajoute un zéro devant les heures, minutes et secondes
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            'filter' => '' // condition d'éxécution
        );


        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        $tz = $this->get_action_pref('timezone', 'Europe/Paris');
        date_default_timezone_set($tz);

        // === Date cible ou delai ?
        $targetDate = trim($options[__class__]);
        if ($targetDate[0] == '+') {
            $term_fr = array('année', 'an', 'mois', 'jour', 'semaine', 'heure', 'seconde');
            $term_en = array('year', 'year', 'month', 'day', 'week', 'hour', 'second');
            $targetDate = str_ireplace($term_fr, $term_en, $targetDate);
        }
        $targetDate = date('Y/m/d H:i:s', strtotime($targetDate));


        // === css-head
        $this->load_css_head($options['css-head']);

        // les libellés prefix et suffix
        $prefix = array_pad(explode(',', $options['prefix']), 6, '');
        $suffix = array_pad(explode(',', $options['suffix']), 6, '');
        for ($i = 0; $i < 6; $i++) {
            $prefix[$i] = ($prefix[$i]) ? ' ' . $prefix[$i] . '&nbsp;' : '';
            $suffix[$i] = ($suffix[$i]) ? '&nbsp;' . $suffix[$i] . ' ' : '';
        }


        // le code JS
        $selector = '#' . $options['id'] . '.countdown-simple';
        $js = '$(document).ready(function () { ';
        $js .= 'upcountdown(\'' . $selector . '\');';
        $js .= ' });';

        // attributs du bloc principal interne
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['data-date'] = date_format(date_create($targetDate), 'Y/m/d H:i:s');
        $attr_main['data-zero'] = $options['zero'];
        $attr_main['class'] = 'countdown-simple ' . $options['model'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // Texte si délai écoulé
        $elapsed_content = ($this->content > '') ? $this->content : $options['elapsed-text'];
        $elapsed_content = $this->get_bbcode($elapsed_content);
        $attr_elapsed['class'] = 'elapsed';

        // nb jours pour determiner les afficheurs nécessaires
        $nbminutes = floor((strtotime($targetDate) - time()) / 60);
        $digit_period = array_fill(0, 6, 0);
        if ($nbminutes >= 0) {
            $nbjours = intval($nbminutes / 1440);
            $digit_view = strtoupper(substr('xxxxxx' . $options['digit-view'], -6));
            // --- analyse besoin et choix user
            $digit_period[0] = ($nbjours > 365 && $digit_view[0] <> 'X') ? 'years' : '';
            $digit_period[1] = ($nbjours > 30 && $digit_view[1] <> 'X') ? 'months' : '';
            $digit_period[2] = ($nbjours > 0 && $digit_view[2] <> 'X') ? 'days' : '';
            $digit_period[3] = ($nbminutes >= 60 && $digit_view[3] <> 'X') ? 'hours' : '';
            $digit_period[4] = ($nbminutes > 0 && $digit_view[4] <> 'X') ? 'minutes' : '';
            $digit_period[5] = ($digit_view[5] <> 'X') ? 'seconds' : '';
            // Contenu
            //$digit_class = ($options['digit-class']) ? ' ' . $options['digit-class'] : '';
            $attr_elapsed['style'] = 'display:none';
        }
        // style des chiffres
        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main);
        $html[] = '<div class="digits">';
        $html[] = $this->get_bbcode($options['intro-text']);
        for ($i = 0; $i < 6; $i++) {
            if ($digit_period[$i]) {
                $str = '<div class="block-' . $digit_period[$i] . '">' . $prefix[$i];
                $digit_class = array();
                $this->get_attr_style($digit_class, $digit_period[$i], $options['digit-class']);
                $str .= $this->set_attr_tag('span', $digit_class, true);
                //                $str .= '<span class = "' . $digit_period[$i] . $digit_class . '"></span>';
                $str .= $suffix[$i] . '</div>';
                $html[] = $str;
            }
        }
        $html[] = $this->get_bbcode($options['close-text']);
        $html[] = '</div>'; // digits
        $html[] = $this->set_attr_tag('div', $attr_elapsed, $elapsed_content);
        $html[] = '</div>'; // main

        $html[] = $this->load_jquery_code($js, false);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
