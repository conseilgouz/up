<?php

/**
 * Active les rapports d'erreurs PHP
 *
 * syntaxe {up php_error} // dev (defaut) ou 0, min, max
 *
 * A METTRE A LA FIN DE L'ARTICLE (UP commence par la fin!)
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Expert
 */
defined('_JEXEC') or die;

class php_error extends upAction {

    function init() {
        // none
    }

    function run() {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
          __class__ => 'dev', // mode rapport d'erreurs : none, 0, min, max, dev
          'id' => '' // identifiant
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        switch (strtolower($options[__class__])) {
            case 'none':
            case '0':
                error_reporting(0);
                break;
            case 'min':
            case 'simple':
                error_reporting(E_ERROR | E_WARNING | E_PARSE);
                break;
            case 'max':
            case 'maximum':
                error_reporting(E_ALL);
                break;
            case 'dev':
            case 'development':
                error_reporting(-1);
                break;
            default:
                error_reporting(0);
        }

        return '';
    }

// run
}

// class
