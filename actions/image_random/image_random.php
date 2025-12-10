<?php

/**
 * Affiche aléatoirement une des images d'un dossier
 *
 * Le dossier peut être un pattern valide pour la fonction PHP GLOB
 *
 * syntaxe 1 : {up image-random=folder}
 * syntaxe 2 : {up image-random}image 1{===}image 2 avec lien{/up image-random}
 * syntaxe 3 : {up image-random}content 1{===}content 2{/up image-random}
 *
 * @author   LOMART
 * @version  UP-1.8.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags image
 */
/*
 * v2.1 : ajout path-only
 * v3.0 : accepte webp
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class image_random extends Lomart\Plugin\Content\Up\Extension\Up
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
            __class__ => '', // dossier des images
            'path-only' => '0', // retourne uniquement le chemin de l'image pour utilisation par une autre action.
            /* [st-css] Style CSS */
            'id' => '', // Identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $this->options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$this->options['css-head']);

        // === on récupère
        $attr_main['id'] = $this->options['id'];
        UpHelper::get_attr_style($this,$attr_main, $this->options['class'], $this->options['style']);

        if ($this->content) {
            if (UpHelper::ctrl_content_parts($this,$this->content)) {
                $imgList = UpHelper::get_content_parts($this,$this->content);
                $num = rand(0, count($imgList) - 1);
                return UpHelper::set_attr_tag($this,'div', $attr_main, $imgList[$num]); // ajout classes et style dans a ?????
            } else {
                // ==========> les images (avec/sans liens) indiquées entre les shortcodes
                $regex = '#(?:<a .*>)?<img.*>(?:</a>)?#i';
                if (preg_match_all($regex, $this->content, $imglist)) {
                    foreach ($imglist[0] as $img) {
                        preg_match('#(<a.*>)?(<img .*>)(</a>)?#iU', $img, $matches);
                        $tmp = UpHelper::get_attr_tag($this,$matches[2], 'alt');
                        if ($tmp['alt'] == '') {
                            $imgname = $tmp['src'];
                            if (isset($this->options['zoom-suffix']))
                                $imgname = str_ireplace($this->options['zoom-suffix'] . '.', '.', $imgname);
                            $tmp['alt'] = UpHelper::link_humanize($this,$imgname);
                        }
                        $matches[3] = ($matches[1]) ? '</a>' : ''; // lien ouvrant et fermant
                        $imgList[] = $matches[1] . UpHelper::set_attr_tag($this,'img', $tmp) . $matches[3];
                    }
                }
                $num = rand(0, count($imgList) - 1);
                if ($this->options['path-only'])
                    return $imgList[$num];
                return UpHelper::set_attr_tag($this,'div', $attr_main, $imgList[$num]); // ajout classes et style dans a ?????
            }
        } else {
            // === Récupération images d'un dossier
            if (strpos($this->options[__class__], '.') === false) {
                $pattern = $this->options[__class__] . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}';
            } else {
                $pattern = $this->options[__class__];
            }
            $imgList = glob($pattern, GLOB_BRACE | GLOB_NOSORT);
            if (empty($imgList)) {
                return UpHelper::info_debug($this,UpHelper::trad_keyword($this,'NOT_FOUND'));
            }
            // --- selection de l'image
            $num = rand(0, count($imgList) - 1);
            $imgpath = dirname($imgList[$num]);
            $imgname = basename($imgList[$num]);

            if ($this->options['path-only'])
                return $imgList[$num];

            // --- attributs du bloc image
            $attr_img['id'] = $this->options['id'];
            $attr_img['src'] = $imgList[$num];
            $attr_img['alt'] = UpHelper::link_humanize($this,$imgList[$num]);
            UpHelper::get_attr_style($this,$attr_img, $this->options['class'], $this->options['style']);
            // bloc image
            $out = UpHelper::set_attr_tag($this,'img', $attr_img);

            // existe-t-il un fichier link
            if (file_exists($imgpath . '/link.ini')) {
                $links = UpHelper::load_inifile($this,$imgpath . '/link.ini');
                if (isset($links[$imgname])) {
                    $attr_link['href'] = $links[$imgname];
                    $out = UpHelper::set_attr_tag($this,'a', $attr_link, $out);
                }
            }
        }

        // === retour
        return $out;
    }

    // run
}

// class


