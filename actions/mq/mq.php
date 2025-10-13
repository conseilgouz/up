<?php

/**
 * Construit une chaîne mediaqueries
 *
 * {up mq=#id | default=color:red | mobile=color:green | tablet=color:blue | 960-1200-screen=color:orange | 1200-0=color:black}
 * retourne la chaîne
 * #id[color:red;@media (max-width:480px)[color:green]@media (min-width:481px) and (max-width:760px)[color:blue]
 * @media screen and (min-width:960px) and (max-width:1200px)[color:orange]@media (min-width:1200px)[color:black]]
 * si selector est indiqué, il est ajouté à la chaîne
 *
 * LE PRINCIPE :
 * on indique les plages d'action du style sous la forme 480-960
 * une plage inférieure (max-width) doit avoir la forme 0-480
 * une plage supérieure (min-width) doit avoir la forme 1200-0 ou 1200 (une seule valeur)
 *
 * autres termes pris en compte :
 * H, horizontal, landscape, V, vertical, portrait, screen, print
 *
 * @version  UP-5.2
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    LOMART
 * @tags    Expert
 *
 */
defined('_JEXEC') or die();

class mq extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // vide ou sélecteur CSS
            'default' => '', // propriété CSS par défaut hors mediaquerie
            'mobile' => '', // propriété CSS pour les mobiles
            'tablet' => '', // propriété CSS pour les tablettes
            'desktop' => '',  // propriété CSS pour les ordinateurs
            'large' => '', // propriété CSS pour les grands écrans
            'xxx' => 'yyy', // breakpoint => propriété. ex: 480-960-screen=color:red
            'use-with-up' => 1, // crochets neutres au lieu d'accolades pour argument action UP

            /* [st-divers] Paramètres */
            'bp-mobile' => '0-480', // valeur min-max pour mobile
            'bp-tablet' => '481-760', // valeur min-max pour tablette
            'bp-desktop' => '761-1200', // valeur min-max pour ordinateur
            'bp-large' => '1201-0', // valeur min-max pour grands écrans
            'unit' => 'px', // unité pour les largeurs d'écrans
            /* [st-interne] */
            'id' => '', //
        );

        // tableau des mediaquerie : key, min, max, media, orientation
        $mq = array();

        // ===========================
        // === analyse des options xxx
        // ===========================
        // On accepte toutes les options. Il faut les ajouter avant contrôle
        $xxx = array_diff_key($this->options_user, $options_def);
        foreach ($xxx as $key => $val) {
            unset($this->options_user[$key]);
            settype($key, 'string');
            $foo = $key[0];
            if ($key[0] < '0' || $key[0] > '9') {
                $this->msg_error($key . ' : is not a valid option');
            } else {
                $key = 'bp-' . $key;
                $this->options_user[$key] = $val;
                $options_def[$key] = '';
                $mq[] = [
                    'key' => $key,
                    'val' => $val
                ];
            }
        }
        // fusion et controle des options
        unset($options_def['xxx']);
        $options = $this->ctrl_options($options_def);

        // ===========================
        // === analyse de breakpoints
        // ===========================
        // les breakpoints standards
        foreach (array(
            'mobile',
            'tablet',
            'desktop',
            'large'
        ) as $k) {
            if (! empty($options[$k])) {
                $mq[] = [
                    'key' => 'bp-' . $options['bp-' . $k],
                    'val' => $options[$k]
                ];
            }
        }
        // --- préparation des valeurs
        for ($i = 0; $i < count($mq); $i++) {
            $k = strtolower($mq[$i]['key']);
            // recherche des medias et orientation
            // H, horizontal, landscape, V, vertical, portrait, screen, print
            foreach (array(
                'screen',
                'print'
            ) as $v) {
                if (strpos($k, $v)) {
                    $mq[$i]['media'] = $v;
                    $mq[$i]['key'] = str_replace($v, '', $k);
                }
            }
            foreach (array(
                'horizontal',
                'landscape',
                'h'
            ) as $v) {
                if (strpos($k, $v)) {
                    $mq[$i]['orientation'] = 'landscape';
                    $mq[$i]['key'] = str_replace($v, '', $k);
                }
            }
            foreach (array(
                'vertical',
                'portrait',
                'v'
            ) as $v) {
                if (strpos($k, $v)) {
                    $mq[$i]['orientation'] = 'portrait';
                    $mq[$i]['key'] = str_replace($v, '', $k);
                }
            }

            // ventilation des valeurs min-max
            // si une seule valeur, c'est la min (mobile first)
            $tmp = explode('-', $mq[$i]['key'] . '-x');
            if ($tmp[2] == 'x') {
                $mq[$i]['min'] = $tmp[1];
                $mq[$i]['max'] = 0;
            } else {
                $mq[$i]['min'] = $tmp[1];
                $mq[$i]['max'] = $tmp[2];
            }
        }
        // -- tri sur la largeur mini
        array_multisort(array_column($mq, 'min'), $mq);

        // ==== construction de la chaîne mediaquerie
        $openBracket = (empty($options['use-with-up'])) ? '{' : '[';
        $closeBracket = (empty($options['use-with-up'])) ? '}' : ']';
        $unit = $options['unit'];
        $out = (empty($options[__class__])) ? '' : $options[__class__] . $openBracket;
        $out .= (! empty($options['default'])) ? rtrim($options['default'], ';') . ';' : ''; // style par defaut
        for ($i = 0; $i < count($mq); $i++) {
            $arg = array();
            if (! empty($mq[$i]['media'])) {
                $arg[] = $mq[$i]['media'];
            }
            if (! empty($mq[$i]['min'])) {
                $arg[] = '(min-width:' . $mq[$i]['min'] . $unit . ')';
            }
            if (! empty($mq[$i]['max'])) {
                $arg[] = '(max-width:' . $mq[$i]['max'] . $unit . ')';
            }
            if (! empty($mq[$i]['orientation'])) {
                $arg[] = '(orientation:' . $mq[$i]['orientation'] . ')';
            }
            // la chaîne retour
            $str = implode(' and ', $arg);
            if (! empty($str) && ! empty($mq[$i]['val'])) {
                $str = '@media ' . $str;
                $str .= $openBracket . $mq[$i]['val'] . $closeBracket;
            }
            $out .= $str;
        }

        $out .= (empty($options[__class__])) ? '' : $closeBracket;

        return $out;
    }
}

// class
