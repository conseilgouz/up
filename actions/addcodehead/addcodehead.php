<?php

/**
 * ajoute du code libre dans le head.
 *
 * possibilité d'ajouter du code libre dans le head sans risque de nettoyage par un éditeur WYSIWYG
 *
 * syntaxe 1 {up addCodeHead=<meta property="og:title" content="Page title" />}
 * syntaxe 2 {up addCodeHead=meta | property=og:title | content=Page title}
 *
 * <b>Attention</b> : l'option XXX doit être remplacée directement dans le shortcode par un nom d'attribut et sa valeur
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Expert
 *
 */

/*
 * v1.7  nouveau mode saisie par attribut=valeur
 * v2.6  fix substitution entite HTML
 */

defined('_JEXEC') or die();

class addcodehead extends upAction
{

    function init()
    {
        // aucune
    }

    function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // code à ajouter dans le head ou balise
            'xxx' => 'yyy', // couple attribut-valeur. ex: title=le titre, href=//google.fr
            /*[st-annexe]options secondaires*/
            'filter' => '', // conditions. Voir doc action filter (v1.8)
            'id' => '' // identifiant
        );

        // On accepte toutes les options. Il faut les ajouter avant contrôle
        foreach (array_diff_key($this->options_user, $options_def) as $key => $val) {
            $options_def[$key] = '';
        }
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // Si des attributs en plus et argument principal mot unique : balise
        // Sinon, cest le code complet (méthode post 1.7)
        if (count($options) > 5 && strpos(trim($options[__class__]), ' ') === false) {
            // -- toutes les options sont des attributs sauf la principale et attr (un exemple)
            $attr = $options;
            unset($attr[__class__]);
            unset($attr['xxx']);
            // -- code pour head
            $tag_not_close = array_map('trim', explode(',', 'area, br, hr, img, input, link, meta, param'));
            $close = (array_search($options[__class__], $tag_not_close) === false);
            $code = $this->set_attr_tag($options[__class__], $attr, $close);
        } else {
            $code = $this->get_code(trim($options[__class__]));
        }
        // -- aucun code HTML en retour
        // il suffit de charger le code dans le head
        if (!empty($code))
            $this->load_custom_code_head($code);
        return '';
    }

    // run
}

// class addcsshead
