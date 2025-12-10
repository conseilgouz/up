<?php

/**
 * Insère du code HTML à une position de l'article
 *
 * syntaxe 1  {up addhtml=code HTML | selector=.foo | parent=2}
 * syntaxe 2  {up addhtml | selector=.foo | parent=2}code HTML{/up addhtml}
 *
 * Exemple : {up addhtml={up icon=star} | selector=.header-page > h1 | parent=2}
 *
 * @version  UP-5.2
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags   Expert
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class addhtml extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        // if (! UpHelper::ctrl_content_exists($this)) {
        // return false;
        // }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - 0 pour cacher le lien vers demo car inexistante
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // Contenu HTML à ajouter. BBCode admis
            'selector' => '', // sélecteur CSS référence pour l'insertion
            'parent' => 0, // niveau parent par rapport à selector ou si vide à la position du shortcode
            'position' => 'beforebegin', // position par rapport au sélecteur : beforebegin, afterbegin, beforeend ou afterend
            'filter' => '', // conditions. Voir doc action filter
            'id' => '', // identifiant
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // CSS dans le head
        if ($options['css-head'] != '') {
            UpHelper::load_css_head($this,$options['css-head']);
        }

        // === LE CONTENU
        $position = $options['position'];
        $content = $options[__class__];
        if (empty($content)) {
            $content = $this->content;
        }
        if (empty($content)) {
            UpHelper::msg_error($this,'Pas de contenu HTML à inserer');
            return;
        }
        $content = UpHelper::get_bbcode($this,$content);
        $content = str_replace('"', '\'', $content);

        // === LE JAVASCRIPT POUR LE PARENT
        $out['tag'] = ''; // HTML en retour
        $code = '';
        $parent = (int) $options['parent'];
        $parentSelector = '';
        if ($parent > 0) {
            $parentSelector = $options['id'] .'-parent';
            // marquer la position du shortcode dans la page
            $out['tag'] = '<div id="' . $options['id'] . '"></div>';
            $code .= 'document.getElementById("' . $options['id'] . '")';
            $code .= str_repeat('.parentElement', $parent);
            $code .= '.classList.add("'.$parentSelector.'");';
            $parentSelector = '.'.$parentSelector;
        }

        // === LE JAVASCRIPT POUR INSERER LE CONTENU
        $selector = trim($parentSelector.' '.UpHelper::get_code($this,$options['selector']));


        $code .= 'document.querySelector("' . $selector . '")';
        $code .= '.insertAdjacentHTML("' . $position . '","' . $content . '");';

        $out['after'] = UpHelper::load_js_code($this,$code, false); // en fin d'article

        // -- le code en retour
        return $out;
    }

    // run
}

// class
