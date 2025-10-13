<?php

/**
 * uniformise l'appel des icônes. Evite de passer en mode code pour la saisie
 *
 * syntaxe 1 : {up icon=nom_icone}
 * syntaxe 2 : {up icon=Ux1F7A7}
 * syntaxe 3 : {up icon=images/icone.png}
 * syntaxe 4 : {up icon=Ux1F7A7,#F00,2rem}
 *
 * Important : indiquer dans prefs.ini le préfixe pour la police d'icones installée sur le site
 *
 * @author     Lomart
 * @version    UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags      Editor
 */

/*
 * v1.4 - ajout prefix pour prise en charge plusieurs polices d'icônes
 * v1.7 - ajout unicode et image.
 * - raccourci saisie et collection dans custom/prefs.ini
 * - création règle css
 * v1.72 - info=2 renvoit les icons de prefs.ini à la place du shortcode, 1 dans debug
 * - fix prise en charge prefset
 * v2.81 - ajout option title (pascal)
 * v3.0 - size : possibilité d'indiquer la taille en fonction de la largeur d'écran
 * v5.2 - une fonticon peut-être saisie :
 *  - fa fa-address-book : icon fontAwesome
 *  - icon-plus : icon icomoon
 *  - plus : ajout prefix ou icon- si prefix vide
 */
defined('_JEXEC') or die();

class icon extends upAction
{
    public function init()
    {
        // aucune
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            $this->name => '', // jeu d'options ou src,color,size
            'src' => '', // nom icône, code unicode, chemin image (indispensable si prefset)
            'size' => '', // taille icône ou coté du carré dans lequel est inscrit l'image. px, rem, em, % (px par defaut). Responsive= basesize, breakpoint1:size1, breakpointN:sizeN
            'color' => '', // couleur
            'color-hover' => '', // couleur lors survol icône (sauf image et unicode coloré)
            /* [st-ext] Pour ajouter l'icône à un bloc sur la page */
            'selector' => '', // selecteur CSS pour identifier le bloc ciblé
            /* [st-divers] Divers */
            'info' => '0', // 1 affiche la liste des icônes définies dans prefs.ini comme un message debug, 2 la retourne pour affichage à la place du shortcode
            'title' => '', // texte affiché au survol de l'icone
            'prefix' => 'icon-', // pour icomoon ou 'fa fa-' pour font-awesome (a mettre dans pref.ini)
            'fontname' => 'icomoon', // pour icomoon ou FontAwesome ou autres (a mettre dans pref.ini)
            /* [st-css] Style CSS */
            'style' => '', // style inline
            'class' => '', // classe
            'id' => '' // identificateur
        );
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ==== on charge prefs.ini
        $pref_user_file = $this->actionPath . 'custom/prefs.ini';
        if (file_exists($pref_user_file)) {
            $pref_user = $this->load_inifile($this->actionPath . 'custom/prefs.ini', true);
            if ($pref_user !== false) {
                if (array_key_exists(strtolower($options[__class__]), $pref_user['icons'])) {
                    $options[__class__] = $pref_user['icons'][strtolower($options[__class__])];
                }
            }
        }

        // ==== Demande de documentation
        if ($options['info'] && ! empty($pref_user['icons'])) { // v2.8
            $out = '<div class="fg-row fg-auto-12 fg-auto-m6 fg-auto-s3 tc fg-vbottom">';
            foreach ($pref_user['icons'] as $key => $val) {
                $attr = array();
                // definition
                $tmp = explode(',', $val);
                $icon = trim($tmp[0]);
                for ($i = 1; $i < sizeof($tmp); $i++) {
                    if (intval($tmp[$i]) == 0) {
                        $this->add_style($attr['style'], 'color', $tmp[$i]);
                    } else {
                        $size = $tmp[$i];
                    }
                }
                // == out
                $out2 = $this->get_html_icon($icon, $size, $options['prefix'], $attr, $options['id']);
                $out .= '<div>' . $out2 . '<br>' . $key . '</div>';
            }
            $out .= '</div>';
            if ($options['info'] === '2') {
                // emplacement shortcode
                return $out;
            } else {
                // comme message d'info
                $this->msg_info($out, $this->trad_keyword('ICONLIST_TITLE'));
            }
            // raz
            $attr = array();
            $out = '';
        }

        // ==== extration de la saisie rapide. ex : plus=plus,red,2rem,\code_iconfont
        if ($options['src']) {
            $icon = $options['src'];
            $iconCode = $options['src'];
        } else {
            $tmp = explode(',', $options[__class__]);
            $icon = trim($tmp[0]);
            $iconCode = $options[__class__];
            for ($i = 1; $i < sizeof($tmp); $i++) {
                if ($tmp[$i][0] == '\\') {
                    $iconCode = $tmp[$i];
                } else {
                    if (intval($tmp[$i]) == 0) {
                        if ($options['color'] == '') {
                            $options['color'] = $tmp[$i];
                        }
                    } else {
                        $options['size'] = $this->str_append(trim($tmp[$i]), $options['size'], ',');
                    }
                }
            }
        }

        if ($options['title']) {
            $attr['title'] = $options['title'];
        }
        // ==== controle options
        // $size = $this->ctrl_unit($options['size'], 'px,em,rem');
        $size = $this->icon_size($options['size']);
        if (is_array($size)) {
            $attr['id'] = $options['id'];
        }
        $color = $options['color'];

        // ==== Les styles
        if ($options['selector'] == '') {
            $attr['style'] = '';
            $this->get_attr_style($attr, $options['style'], $options['class']);
            $this->add_style($attr['style'], 'color', $options['color']);

            // color-hover est traité en javascript
            if ($options['color-hover']) {
                $attr['onMouseOver'] = "this.style.color='" . $options['color-hover'] . "'";
                $attr['onMouseOut'] = "this.style.color='" . $options['color'] . "'";
                // note: si vide, equivaut à inherit
            }

            // ==== code selon type d'icone
            $out = $this->get_html_icon($icon, $size, $options['prefix'], $attr, $options['id']);
        } else {
            $out = $options['selector'] . '[';
            $out .= $this->get_css_icon($icon, $size, $color, $iconCode, $options['fontname']);
            $out .= ';' . $options['style'];
            $out .= ']';
        }
        // ------ code en retour
        return $out;
    }

    // run
    /*
     * retourne le code HTML de base en fonction du type d'icone
     */
    public function get_html_icon($icon, $size, $prefix, &$attr, $id)
    {
        if (str_starts_with($icon, '&')) {
            $icon = html_entity_decode($icon); // v5.2 saisie entité HtML
            if (is_array($size)) {
                $this->make_css_fontsize($size, $id);
                $size = '';
            } elseif ($size > 0) {
                $this->add_style($attr['style'], 'font-size', $size);
            }
            $out = $this->set_attr_tag('span', $attr, $icon);
        } elseif (strtolower(substr($icon, 0, 2)) == 'ux') {
            // $type = 'unicode';
            if (is_array($size)) {
                $this->make_css_fontsize($size, $id);
                $size = '';
            } elseif ($size > 0) {
                $this->add_style($attr['style'], 'font-size', $size);
            }
            $out = $this->set_attr_tag('span', $attr, '&#x' . substr($icon, 2) . ';');
        } elseif ($this->preg_string('#.(png|jpg|gif)#i', $icon)) {
            // $type = 'image';
            $attr['src'] = $icon;
            list($w, $h) = getimagesize($icon);
            if (is_array($size)) {
                $this->make_css_imgsize($size, $w, $h, $id);
                $size = '';
            } else {
                $this->add_style($attr['style'], 'height', ($h >= $w) ? $size : 'auto');
                $this->add_style($attr['style'], 'width', ($h < $w) ? $size : 'auto');
            }
            $attr['alt'] = pathinfo($icon, PATHINFO_FILENAME);
            $out = $this->set_attr_tag('img', $attr);
        } else {
            // $type = 'fonticon';
            if (is_array($size)) {
                $this->make_css_fontsize($size, $id);
                $size = '';
            } elseif ($size > 0) {
                $this->add_style($attr['style'], 'font-size', $size);
            }
            if (str_starts_with($icon, 'icon-') || str_starts_with($icon, 'fa ')  || str_starts_with($icon, 'fab ')) { // v5.2
                $this->add_class($attr['class'], $icon);
            } elseif (empty($prefix)) {
                $this->add_class($attr['class'], 'icon-' . $icon);
            } else {
                $this->add_class($attr['class'], $prefix . $icon);
            }
            $attr['aria-label'] = pathinfo($icon, PATHINFO_FILENAME);
            $out = $this->set_attr_tag('i', $attr, true);
        }

        return $out;
    }

    /*
     * retourne les propriétés pour une règle CSS
     */
    public function get_css_icon($icon, $size, $color, $iconCode, $fontname)
    {
        if (strtolower(substr($icon, 0, 2)) == 'ux') {
            // $type = 'unicode';
            $out = 'content:"\\' . substr($icon, 2) . '" !important';
            if ($size) {
                $out .= ';font-size:' . $size;
            }
        } elseif ($this->preg_string('#.(png|jpg|gif)#i', $icon)) {
            // $type = 'image';
            $out = 'content:url("' . $icon . '") !important';
            if ($size) {
                list($w, $h) = getimagesize($icon);
                $unit = substr($size, strlen(strval(intval($size))));
                if ($h < $w) {
                    $out .= ';width:' . $size;
                    $out .= ';height:' . ($h * $size / $w) . $unit;
                } else {
                    $out .= ';height:' . $size;
                    $out .= ';width:' . ($w * $size / $h) . $unit;
                }
            }
        } else {
            // $type = 'fonticon';
            $out = 'content:"' . $iconCode . '" !important';
            if ($fontname) {
                $out .= ';font-family:' . $fontname;
            }
            if ($size) {
                $out .= ';font-size:' . $size;
            }
        }
        $out .= ';display:inline-flex';
        if ($color) {
            $out .= ';color:' . $color . ' !important';
        }

        return $out;
    }

    /*
     * icon_size($arg)
     * -------------------
     * $arg: 48px, 960:32px, 1200: 24px, ...
     * retourne la taille ou un tableau associatif si mediaquerie
     */
    public function icon_size($arg)
    {
        // supprime tous les espaces inutiles
        $arg = str_replace(explode(',', " ,\t,\n,\r,\0,\x0B,\xA0,\xC2"), '', $arg);
        $sizes = explode(',', $arg);
        foreach ($sizes as $size) {
            if (strpos($size, ':') === false) {
                $bp = '0';
                $this->ctrl_unit($size, 'px,em,rem,%');
            } else {
                list($bp, $size) = explode(':', $size);
                $bp = (int) $bp;
                $this->ctrl_unit($size, 'px,em,rem,%');
            }
            $rules[$bp] = $size;
        }
        ksort($rules);
        if (count($rules) == 1) {
            return $rules[0];
        } else {
            return $rules;
        }
    }

    // get_icon_size

    /*
     * make_css_fontsize($size, $id)
     * -----------------------------
     * ajoute les regles CSS mediaquerie pour la taille caractères
     */
    public function make_css_fontsize($sizes, $id)
    {
        $basesize = 0;
        $out = '';
        foreach ($sizes as $mq => $size) {
            if (substr($size, - 1) == '%' && $basesize) {
                $size = 'calc(' . $basesize . '*' . ((int) $size / 100) . ')';
            }
            $css = '#' . $id . '{font-size:' . $size . '}';
            if ($mq) {
                $out .= '@media(min-width:' . $mq . 'px){' . $css . '}';
            } else {
                $out .= $css;
                $basesize = $size;
            }
        }
        $this->load_css_head($out);
    }

    /*
     * make_css_imgsize($size, $w, $h, $id)
     * -----------------------------
     * ajoute les regles CSS mediaquerie pour la taille de l'image
     */
    public function make_css_imgsize($sizes, $w, $h, $id)
    {
        $basesize = 0;
        $out = '';
        foreach ($sizes as $mq => $size) {
            if (substr($size, - 1) == '%' && $basesize) {
                $size = 'calc(' . $basesize . '*' . ((int) $size / 100) . ')';
            }
            $css = '#' . $id . '{' . (($w > $h) ? 'width' : 'height') . ':' . $size;
            if ($mq) {
                $out .= '@media(min-width:' . $mq . 'px){' . $css . '}}';
            } else {
                $out .= $css;
                $out .= ';' . (($w <= $h) ? 'width' : 'height') . ':auto}';
                $basesize = $size;
            }
        }
        $this->load_css_head($out);
    }
}

// class
