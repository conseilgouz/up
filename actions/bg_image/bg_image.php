<?php

/**
 * Affiche une image en fond d'un bloc
 *
 * syntaxe :
 * fond du site : {up bg-image=chemin_image}
 * fond du bloc : {up bg-image=chemin_image} contenu {/up bg-image}
 * fond d'un autre bloc : {up bg-image=chemin_image | bg-selector=#foo}
 *
 *
 * doc : https://developer.mozilla.org/fr/docs/Web/CSS/background
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Body
 */

/*
 * v5.1 - ajout id dans bloc principal
 */

 /* PSEUDO-CODE
  1/ Overlay sur contenu -> css dans head
  2/ CSS si mobile
  3/ Récupération du contenu de bg-image
  - si débute par background -> attr_main['style']
  - si 1 image -> attr_main['style']
  - si +sieurs images -> attr_main['style']
  Overlay sur image -> comme image en début de attr_main['style']
  4/ Ajout options class & style dans attr_main
  5/ Si center -> up-center dans attr_main & options[center] dans attr_content
  6/ Code HTML retour
  - si contenu -> div.attr_main > div.attr_content
  - si bloc ou body -> bg-selector{$attr_main['style']} dans head
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class bg_image extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            /*images*/
            __class__ => '', // images séparées par des points-virgules
            'mobile' => '', // image ou règle(s) css (si smartphone)
            'path' => '', // chemin de base ajouté devant le nom des fichiers
            /*[st-bloc]emplacement du fond image*/
            'bg-selector' => 'body', // sélecteur du bloc pour image(s) de fond
            /*[st-bg]paramètres de l'image*/
            'bg-color' => '', // couleur sous image
            'bg-repeat' => 'no-repeat', // répétition de l'image : no-repeat, repeat-x, repeat-y, repeat, space
            'bg-position' => 'center', // position de l'image : left|center|right top|center|bottom
            'bg-size' => 'cover', // remplissage : cover, contain, 100%, 100px
            'bg-attachment' => 'scroll', // défilement de l'image : scroll, fixed, local
            'bg-overlay' => '', // image ajoutée en overlay
            /*[st-page]transparence du contenu de la page*/
            'page-selector' => '', // si un bloc est défini, sélecteur du bloc sur lequel appliquer la transparence
            'page-overlay' => '70', // transparence sous la forme 70, #RGBA, image overlay ou règle CSS
            /*[st-annexe]options secondaires*/
            'filter' => '', // conditions. Voir doc action filter  (v1.8)
            'center' => '', // centrage vertical du contenu entre shortcodes
            'id' => '',
            'style' => '', // style inline pour bloc
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }
        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // === init variable
        $attr_main = array();
        $attr_content = array();

        // === si mobile physique et option définie
        $css_mobile = '';
        if ($options['mobile'] > '1') {
            $client = Factory::getApplication()->client;
            if ($client->mobile) {
                $css_mobile = UpHelper::get_bg_mobile($this,$options);
                UpHelper::get_attr_style($this,$attr_main, $css_mobile);
            }
        }

        // === OVERLAY SUR CONTENU
        if ($options['page-selector']) {
            $tmp = $options['page-selector'] . '{background:';
            $tmp .= UpHelper::get_overlay($this,$options['page-overlay']);
            $tmp .= '}';
            UpHelper::load_css_head($this,$tmp);
        }

        // === récupération du contenu
        if ($css_mobile == '') {
            $img = $options[__class__];
            if (substr(strtolower($img), 0, 10) == 'background') {
                // tous le code css est dans l'option principale
                $attr_main['style'] = $img;
            } elseif (UpHelper::get_imgpath($this,$img, $options['path'])) {
                // méthode simple :
                $bg['url'] = 'url(' . UpHelper::get_url_relative($this,$img) . ')';
                $bg['color'] = $options['bg-color'];
                $bg['attachment'] = $options['bg-attachment'];
                $bg['repeat'] = $options['bg-repeat'];
                if (isset($this->options_user['bg-repeat']) == false) {
                    list($w, $h) = getimagesize($img);
                    $bg['repeat'] = ( ($w + $h) < 200) ? 'repeat' : 'no-repeat';
                }
                if ($bg['repeat'] != 'no-repeat') {
                    $bg['size'] = $options['bg-position'];
                } else {
                    $bg['size'] = $options['bg-position'] . '/' . $options['bg-size'];
                }
                $attr_main['style'] = 'background:' . implode(' ', $bg);
            } else {
                // multi-images
                $bg = UpHelper::get_array_property($this,$options);
                foreach ($bg['url'] as $ind => $url) {
                    if (!$url)
                        continue;
                    $tmp = array(); // raz result
                    if (UpHelper::get_imgpath($this,$url, $options['path'])) {
                        $tmp['url'] = 'url(' . UpHelper::get_url_relative($this,$url) . ')';
                        $tmp['repeat'] = $bg['repeat'][$ind];
                        $tmp['attachment'] = $bg['attachment'][$ind];
                        if ($tmp['repeat'] != 'no-repeat') {
                            $tmp['size'] = $bg['position'][$ind];
                        } else {
                            $tmp['size'] = $bg['position'][$ind] . '/' . $bg['size'][$ind];
                        }
                    } else {
                        // valeur CSS pour background
                        $tmp['css'] = $url;
                    }
                    $css[] = implode(' ', $tmp);
                }

                $attr_main['style'] = 'background:' . implode(', ', $css);
                UpHelper::add_style($this,$attr_main['style'], 'background-color', $options['bg-color']);
            }
        }

        // ====== OVERLAY : optionnel, masque sur l'image(s)
        if ($options['bg-overlay'] != '') {
            $val = UpHelper::get_overlay($this,$options['bg-overlay']);
            // --- Ajout au style
            $attr_main['style'] = str_replace('background:', 'background:' . $val . ',', $attr_main['style']);
        }

        // --- classe pour centrage vertical
        if ($options['center']) {
            UpHelper::add_class($this,$attr_main['class'], 'up-center ');
            if ($options['center'] != 1) {
                $attr_content['class'] = $options['center'];
            }
        }

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['style']);

        // code en retour
        if ($this->content > '') {
            $html[] = UpHelper::set_attr_tag($this,'div', $attr_main);
            $html[] = UpHelper::set_attr_tag($this,'div', $attr_content, $this->content);
            $html[] = '</div>';
            return implode(PHP_EOL, $html);
        } else {
            $css = $options['bg-selector'] . '{';
            $css .= $attr_main['style'];
            $css .= '}';
            UpHelper::load_css_head($this,$css);
            return '';
        }
    }

// run


}

// class

