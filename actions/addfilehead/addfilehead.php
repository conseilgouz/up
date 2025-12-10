<?php

/**
 * Ajoute des fichiers JS ou CSS dans le head
 *
 * syntaxe {up addfilehead=file1.js, file2.css, //site.fr/file.js}
 *
 * @version  UP-3.0
 * @author   Lomart
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Expert
 *
 */
defined('_JEXEC') or die();
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class addfilehead extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // liste des fichiers ou URL. séparateur virgule
            'filter' => '' // conditions. Voir doc action filter (v1.8)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        $files = array_map('trim', explode(',', $options[__class__]));
        foreach ($files as $file) {
            UpHelper::load_file($this,$file);
        }

        return '';
    }

    // run
}

// class
