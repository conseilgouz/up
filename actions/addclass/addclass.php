<?php

/**
 * ajoute une classe à un sélecteur CSS (body par défaut.)
 *
 * Il est possible :
 * - de cibler le parent d'un sélecteur CSS ou être relatif à l'emplacement du shortcode
 * - de créer la règle CSS avec l'option 'css-head=.foo[color:red]'
 *
 * syntaxe {up addclass=nom_classe}
 *
 * Utilisation : changer l'image de fond à partir d'un article
 *
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Expert
 */
/*
 * v1.8 : ajout option filter
 * v1.9.5 : si parent & selector=body, on crée une référence à l'emplacement du shortcode
 * v3.1 : jquery -> js
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class addclass extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // aucune
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            $this->name => '', // nom de la classe ajoutée à la balise
            'selector' => 'body', // balise cible. Ne pas oublier le point pour une classe ou le # pour un ID
            'parent' => '', // 1 si on cible le parent de selector, 2 le grand-père, ...
            /* [st-annexe]options secondaires */
            'id' => '', // identifiant
            'css-head' => '', // code CSS pour head. Attention utiliser [] au lieu de {}
            'filter' => '' // conditions. Voir doc action filter (v1.8)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);
        $options[__class__] = trim($options[__class__], ' .'); // on enlève le point du nom de la classe (v5.2)

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // === Quel sélecteur (v1.9.5)
        $out['tag'] = ''; // HTML en retour
        if (intval($options['parent']) > 0 && $options['selector'] == 'body') {
            $options['selector'] = '#' . $options['id'];
            $out['tag'] = '<div id="' . $options['id'] . '"></div>';
        }

        // CSS dans le head
        if ($options['css-head'] != '') {
            UpHelper::load_css_head($this,$options['css-head']);
        }

        // === Ajout dans le head (3.1 jquery -> js)

        $parent = str_repeat('.parentElement', (int) $options['parent']);
        $code = 'document.querySelector("' . $options['selector'] . '")' . $parent . '.classList.add("' . $options[__class__] . '");';
        $out['after'] = UpHelper::load_js_code($this,$code, false); // en fin d'article

        // -- le code en retour
        return $out;
    }

    // run
}

// class
