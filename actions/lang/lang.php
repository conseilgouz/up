<?php

/**
 * Choix du contenu selon la langue du visiteur
 *
 * syntaxe 1 : {up lang | fr=oui | gb=yes}
 * syntaxe 2 : {up lang | lang-order=en-fr} contenu anglais {====}contenu français {/up lang}
 * syntaxe 3 : {up lang} retourne le meilleur code langue selon lang-order 
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */

/*
 * v2.4 - réecriture du code
 * - ajout option info pour connaitre la langue du navigateur client
 * - seuls les 2 premiers caractères du tag langue sont pris en compte (en-US => en)
 * v3.1 - retourne le code langue si pas d'argument autre que lang-order
 * permet {html=img | src=images/foo_{up lang}.png} 
 * v6.0.30 :replace locale_accept_from_http
 */
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class lang extends Lomart\Plugin\Content\Up\Extension\Up
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
            __class__ => '', // langue pour vérifer le rendu (vide en production)
            'lang-order' => 'en,fr', // ordre de saisie des langues dans contenu
            /* [st-css] Style CSS*/
            'tag' => '', // balise entourant le contenu retourné
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            /* [st-divers] Divers */
            'info' => '0', // affiche la langue du navigateur et celle affichée
            'http' => '0' // utilise l'entête HTTP au lieu de Factory::getLanguage()
        );

        // === les traductions
        if ($this->content == '') {
            // les langues dans l'ordre de saisie dans le shortcode
            // On accepte toutes les options de 2 caractères. Il faut les ajouter avant contrôle
            $options_def_orig = $options_def; // on conserve une copie
            foreach (array_diff_key($this->options_user, $options_def) as $key => $val) {
                if (strlen($key) < 3) {
                    $trads[strtolower($key)] = $val;
                    unset($this->options_user[$key]);
                }
            }
            // fusion et controle des options
            $options = UpHelper::ctrl_options($this,$options_def);
        } else {
            // fusion et controle des options
            $options = UpHelper::ctrl_options($this,$options_def);
            // les traduction dans le contenu dans l'ordre de lang-order
            $lang_list = explode(',', strtolower($options['lang-order']));
            $content_parts = UpHelper::get_content_parts($this,$this->content);
            if (count($lang_list) != count($content_parts)) {
                $html = UpHelper::info_debug($this,UpHelper::trad_keyword($this,'LANG_NB_DIFF'));
                return $html;
            }
            $trads = array_combine($lang_list, $content_parts);
        }

        // === Par défaut, la langue est le code de la langue - v3.1
        if (empty($trads)) {
            $langs = explode(',', strtolower($options['lang-order']));
            foreach($langs AS $lang)
                $trads[$lang] = $lang;
        }
        
        // === la langue demandée
        if ($options[__class__] == '') {
            //if ($options['http']) {
            //    $userlang = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            //} else {
                $userlang = Factory::getApplication()->getLanguage()->getTag();
            //}
        } else {
            // la langue à utiliser pour tester le rendu
            $userlang = $options[__class__];
        }
        // langue du navigateur client (la 1ere partie fr pour fr-FR, en pour en-GB v2.4)
        $userlang = strtolower(substr($userlang, 0, 2));
        
        // === la meilleure langue
        $bestlang = (empty($trads[$userlang])) ? array_keys($trads)[0] : $userlang;
        $content = $trads[$bestlang];

        // === info language (v2.4)
        if ($options['info']) {
            $msgTitle = 'Lang info ' . $options['id'];
            $msg = UpHelper::trad_keyword($this,'LANG_INFO', $userlang, $bestlang);
//             $msg = UpHelper::trad_keyword($this,'User language : ' . $userlang . ' --- Displayed : ' . $bestlang);
            UpHelper::msg_info($this,$msg, $msgTitle);
        }

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];

        // code en retour
        if (($options['tag'] == '') && ($options['class'] || $options['style'])) {
            $tag = 'span';
        }
        if ($options['tag'] == '') {
            $html = $content;
        } else {
            $html = UpHelper::set_attr_tag($this,$options['tag'], $attr_main, $content);
        }
        return $html;
    }

    // run
}

// class

