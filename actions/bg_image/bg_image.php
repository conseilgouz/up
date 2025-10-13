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

class bg_image extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {

        // lien vers la page de demo
        $this->set_demopage();

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
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }
        // === css-head
        $this->load_css_head($options['css-head']);

        // === init variable
        $attr_main = array();
        $attr_content = array();

        // === si mobile physique et option définie
        $css_mobile = '';
        if ($options['mobile'] > '1') {
            $client = Factory::getApplication()->client;
            if ($client->mobile) {
                $css_mobile = $this->get_bg_mobile($options);
                $this->get_attr_style($attr_main, $css_mobile);
            }
        }

        // === OVERLAY SUR CONTENU
        if ($options['page-selector']) {
            $tmp = $options['page-selector'] . '{background:';
            $tmp .= $this->get_overlay($options['page-overlay']);
            $tmp .= '}';
            $this->load_css_head($tmp);
        }

        // === récupération du contenu
        if ($css_mobile == '') {
            $img = $options[__class__];
            if (substr(strtolower($img), 0, 10) == 'background') {
                // tous le code css est dans l'option principale
                $attr_main['style'] = $img;
            } elseif ($this->get_imgpath($img, $options['path'])) {
                // méthode simple :
                $bg['url'] = 'url(' . $this->get_url_relative($img) . ')';
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
                $bg = $this->get_array_property($options);
                foreach ($bg['url'] as $ind => $url) {
                    if (!$url)
                        continue;
                    $tmp = array(); // raz result
                    if ($this->get_imgpath($url, $options['path'])) {
                        $tmp['url'] = 'url(' . $this->get_url_relative($url) . ')';
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
                $this->add_style($attr_main['style'], 'background-color', $options['bg-color']);
            }
        }

        // ====== OVERLAY : optionnel, masque sur l'image(s)
        if ($options['bg-overlay'] != '') {
            $val = $this->get_overlay($options['bg-overlay']);
            // --- Ajout au style
            $attr_main['style'] = str_replace('background:', 'background:' . $val . ',', $attr_main['style']);
        }

        // --- classe pour centrage vertical
        if ($options['center']) {
            $this->add_class($attr_main['class'], 'up-center ');
            if ($options['center'] != 1) {
                $attr_content['class'] = $options['center'];
            }
        }

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['style']);

        // code en retour
        if ($this->content > '') {
            $html[] = $this->set_attr_tag('div', $attr_main);
            $html[] = $this->set_attr_tag('div', $attr_content, $this->content);
            $html[] = '</div>';
            return implode(PHP_EOL, $html);
        } else {
            $css = $options['bg-selector'] . '{';
            $css .= $attr_main['style'];
            $css .= '}';
            $this->load_css_head($css);
            return '';
        }
    }

// run

    /*
     * Retourne TRUE si le fichier existe ou FALSE sinon
     * Met à jour $file en ajoutant $path si nécessaire
     */
    function get_imgpath(&$file, $path) {
        $ok = is_file($file);
        if (!$ok && is_file($path . $file)) {
            $file = $path . $file;
            $ok = true;
        }
        return $ok;
    }

    /*
     * retourne un tableau consolidé pour les propriétés multi-images
     */

    function get_array_property($options) {
        $images = trim($options[__class__], ';\t\n\r\0');
        $bg['url'] = array_map('trim', explode(';', $images));
        $nb_images = count($bg['url']);
        $properties = array('repeat' => 'no-repeat', 'size' => 'cover', 'position' => 'center', 'attachment' => 'scroll');
        foreach ($properties AS $property => $default) {
            $bg[$property] = array_map('trim', explode(';', trim($options['bg-' . $property] . ';' . $default, " ;")));
            $bg[$property] = array_pad($bg[$property], $nb_images, end($bg[$property]));
        }

        return $bg;
    }

    /*
     * get_overlay : retourne la valeur pour la propriété background d'un overley
     * si $val se termine par .png : image répétée
     * si $val est un nombre (70, 70%) : masque blanc transparent
     * si $val commence par # (#FF9999 70%) : masque coloré transparent
     * sinon $val est une règle CSS (linear-gradient ou radial-gradient)
     */

    function get_overlay($val) {
        if (strtolower(substr($val, strrpos($val, '.'))) == '.png') {
            // si fichier PNG
            if (dirname($val) == '.') {
                $val = $this->upPath . 'assets/overlay/' . $val;
                $val = str_replace('\\', '/', $val);
            }
            $val = 'url(\'' . Uri::root(true) . '/' . $val . '\') repeat';
        } else if ($val[0] == '#') {
            $rgba = $this->hex2rgba($val);
            $val = 'linear-gradient(' . $rgba . ' 0%,' . $rgba . ' 100%)';
        } else if ((float) $val > 0) {
            // si 70 ou 70% -> rgba(256,256,256,.7)
            $val = (float) $val;
            $val = $val / 100;
            $val = 'linear-gradient(rgba(240,240,240,' . $val . ') 0%,rgba(240,240,240,' . $val . ') 100%)';
        }
        // sinon, c'était une règle CSS
        return $val;
    }

    /*
     * hex2rgba : retourne une couleur au format #RRGGBBAA ou #RGBA au format rgba(r,g,b,a)
     * opacité à 1 par défaut
     */

    function hex2rgba($hex) {
        // on retire le #
        $hex = str_replace('#', '', $hex);
        // si #RGBA ou #RGB : on double en forcant à FF si besoin
        if (strlen($hex) <= 4) {
            $hex .= $hex . 'FFFF';
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }
        // si >4 et <8, on force à FF
        $hex = substr($hex . 'FFFF', 0, 8);
        // conversion en décimal
        $rgba = array_map('hexdec', str_split($hex, 2));
        // canal alpha sous forme coeff
        $rgba[3] = round($rgba[3] / 255, 1);
        // retour
        return 'rgba(' . implode(',', $rgba) . ')';
    }

    /*
     * retourne le CSS pour le background sur mobile
     * $opt_mobile peut contenir :
     * - rien : on n'affiche pas la video, mais le fond prévu (poster bg-color)
     * - une image
     * - des propriétés css pour background : url(image.jpg) repeat-y
     * - du css : background:...;color:...
     */

    function get_bg_mobile($options) {
        $opt_mobile = $options['mobile'];
        if ($opt_mobile == '1') {
            $out = '';
        } elseif (is_file($opt_mobile)) {
            // image existante
            list($w, $h) = getimagesize($opt_mobile);
            if (($w + $h) < 200) {
                $out = 'background:url(\'' . $opt_mobile . '\') repeat ' . $options['bg-color'];
            } else {
                $out = 'background:url(\'' . $opt_mobile . '\') no-repeat ' . $options['bg-color'] . ' center/cover';
            }
        } else {
            $out = (substr($opt_mobile, 0, 11) == 'background:') ? '' : 'background:';
            $out .= $opt_mobile;
        }
        return $out;
    }

}

// class

