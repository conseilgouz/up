<?php

/**
 * affiche un trait horizontal avec une icône et/ou du texte
 *
 * syntaxe 1 : {up hr=nom_class_modele}
 * syntaxe 2 : {up hr=nom_prefset}
 * syntaxe 3 : {up hr=1px, solid, #F00, 50%}
 *
 * @author     Lomart
 * @version    UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
defined('_JEXEC') or die;

class hr extends upAction
{
    public function init()
    {
        $this->load_file('hr.css');
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // nom icône, code unicode, chemin image ou nom dans prefs.ini + color, size
            /* [st-lign] la ligne de séparation */
            'hr-border-top' => '3px double #666', // style du trait supérieur
            'hr-border-bottom' => '', // style du trait inférieur
            'hr-width' => '100%', // largeur du trait
            'hr-height' => '', // hauteur pour bg
            'hr-bg' => '', // argument pour background : couleur, dégradé, image
            'hr-align' => 'center', // position de la ligne : left, center, right
            'hr-style' => '', // style inline pour la ligne
            'hr-class' => '', // classe pour la ligne
            /* [st-icon] texte ou image sur la ligne */
            'icon' => '', // icon. admet raccourci icon, size, color
            'icon-text' => '', // texte en remplacement ou après l'icone
            'icon-size' => '24px', // taille icone en px, rem (px par defaut) - coté du carré dans lequel est inscrit l'image
            'icon-color' => '', // couleur pour icon et texte
            'icon-bg' => '#ffffff', // couleur de fond
            'icon-space' => '4px', // espace entre icon et trait
            'icon-h-offset' => '', // décalage horizontal en px ou rem négatif pour aller vers la gauche
            'icon-v-offset' => 0, // décalage vertical dans la même unité que icon-size. Par défaut moitié de icon-size
            'icon-style' => '', // style inline pour l'icône ou le texte
            // une classe pour icône ou le texte est IMPOSSIBLE
            /* [st-divers] Divers */
            'fontname' => 'Font Awesome 6 Free', // pour icomoon ou FontAwesome ou autres (a mettre dans pref.ini)
            'id' => '', // identifiant
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        if ($options['hr-width']) {
            $options['hr-width'] = $this->ctrl_unit($options['hr-width'], '%, px, rem');
        }
        if ($options['hr-height']) {
            $options['hr-height'] = $this->ctrl_unit($options['hr-height'], 'px, rem');
        }
        if ($options['icon-space']) {
            $options['icon-space'] = $this->ctrl_unit($options['icon-space'], 'px, rem');
        }

        // ==== DECLARATION / CONSOLIDATION VARIABLES POUR HR
        $id = $options['id'];
        $hr_class = ''; // classes pour tag HR
        $css = '';  // déclaration CSS pour icon (hr:after)
        $margin_size = 28; // valeur margin-top et bottom dans hr.css
        // Dimensions verticales : unités permises px & rem. Calcul en px
        $hr_height = (isset($options['hr-height'])) ? $options['hr-height'] : '4px';
        list($hr_height_val, $hr_height_unit) = $this->convert_size($hr_height);

        //
        // ==== GESTION HR
        //
        // ---- extraction de la saisie rapide. ex : hr=1px solid red 50%
        // Pas si user a défini HR
        $tmp = array(); // pour avoir sizeof($tmp)=0
        if ($options[__class__]) {
            $tmp = str_replace(',', ' ', $options[__class__]); // si erreur saisie
            $tmp = explode(' ', $tmp);
            switch (sizeof($tmp)) {
                case 0:
                    // rien, on utilise les autres options
                    break;
                case 1:
                case 2:
                    // un ou 2 noms de classes
                    $hr_class = implode(' ', $tmp);
                    // sauf saisie expresse, on annule tout
                    foreach ($options as $key => $val) {
                        if (empty($this->options_user[$key])) {
                            $options[$key] = '';
                        }
                    }
                    $options['id'] = $id;
                    break;
                case 3:
                    // les 3 arguments pour border
                    if (empty($this->options_user['hr-border-top'])) {
                        $options['hr-border-top'] = implode(' ', $tmp);
                    }
                    break;
                default:
                    // les 3 arguments pour border + la largeur. les autres sont oubliés !
                    if (empty($this->options_user['hr-border-top'])) {
                        $options['hr-border-top'] = implode(' ', array_slice($tmp, 0, 3));
                    }
                    if (empty($this->options_user['hr-width'])) {
                        $options['hr-width'] = $tmp[3];
                    }
                    break;
            }
        }
        //
        // --- Gestion background de HR.
        // si pas de hauteur définie, on la force à 4px
        //
        if ($options['hr-bg']) {
            $this->add_style($hr_attr['style'], 'background', $options['hr-bg']);
            $this->add_style($hr_attr['style'], 'height', $hr_height);
            if (empty($this->options_user[__class__]) && empty($this->options_user['hr-border-top'])) {
                $options['hr-border-top'] = '';
            }
        }

        //
        // --- les autres options pour HR
        //
        $hr_attr['id'] = $options['id'];
        $hr_attr['class'] = 'up ' . $hr_class;
        $this->get_attr_style($hr_attr, $options['hr-class'], $options['hr-style']);
        $this->add_style($hr_attr['style'], 'border-top', $options['hr-border-top']);
        $this->add_style($hr_attr['style'], 'border-bottom', $options['hr-border-bottom']);
        $this->add_style($hr_attr['style'], 'width', $options['hr-width']);
        switch (strtolower($options['hr-align'])) {
            case 'left':
                $this->add_str($hr_attr['style'], 'text-align:left;margin-left:0', ';');
                break;
            case 'right':
                $this->add_str($hr_attr['style'], 'text-align:right;margin-right:0', ';');
                break;
        }

        // =
        // ==== GESTION ICON
        // =
        //
        if ($options['icon'] || $options['icon-text']) {
            $css = ($options['icon-style']) ? $options['icon-style'] . ';' : ''; // init CSS
            //
            // --- extraction de la saisie rapide dans icon. ex: icon=icone,size,color,code_fonticon
            //
            $tmp = array_map('trim', explode(',', $options['icon']));
            $icon = $tmp[0];
            $iconCode = $tmp[0];
            for ($i = 1; $i < sizeof($tmp); $i++) {
                if ($tmp[$i][0] == '\\') {
                    // code d'une fonticon
                    $iconCode = $tmp[$i];
                } else {
                    if (intval($tmp[$i]) == 0) {
                        if (empty($this->options_user['icon-color'])) {
                            $options['icon-color'] = $tmp[$i];
                        }
                    } else {
                        if (empty($this->options_user['icon-size'])) {
                            $options['icon-size'] = $tmp[$i];
                        }
                    }
                }
            }
            // variables de travail
            $icon_color = $options['icon-color'];
            $icon_size = (isset($options['icon-size'])) ? $options['icon-size'] : '';
            list($icon_size_val, $icon_size_unit) = $this->convert_size($icon_size);
            //
            // --- Gestion position horizontale
            //
            if ($options['icon-h-offset']) {
                $css .= 'left:' . $options['icon-h-offset'] . ';';
            }
            // -
            // --- Gestion d'un texte
            // -
            $icon_text = ($options['icon-text']) ? ' "' . $options['icon-text'] . '"' : '';
            if (!$icon) {
                $css .= 'content:' . $icon_text . ";";
                $css .= ($icon_color) ? 'color:' . $icon_color . ' !important;' : '';
                $css .= ($icon_size) ? 'font-size:' . $icon_size . ";" : '';
                $css .= (empty($this->options_user['fontname'])) ? '' : 'font-family:' . $this->options_user['fontname'] . ';';
                //				$css .= 'line-height:1;';
            }
            // -
            // --- selon type d'icone : content, color,
            // -
            if ($icon) {
                if (strtolower(substr($icon, 0, 2)) == 'ux') {  // === UNICODE
                    $css .= 'content:"\\' . substr($icon, 2) . '"' . $icon_text . ' !important;';
                    if ($icon_size) {
                        $css .= 'font-size:' . $icon_size . ';';
                    }
                    //					$css .= 'line-height:1;';
                    $css .= ($icon_color) ? 'color:' . $icon_color . ' !important;' : '';
                } elseif ($this->preg_string('#.(png|jpg|gif)#i', $icon)) {  // === IMAGE
                    $css .= 'content:url(' . $this->get_url_relative($icon) . ') ' . $icon_text . ' !important;'; // v2.6
                    if (file_exists($icon)) {
                        list($w, $h) = getimagesize($icon);
                        if ($icon_size) {
                            if ($h < $w) {
                                $css .= 'width:' . $icon_size . ';';
                                $css .= 'height:' . ($h * $icon_size_val / $w) . $icon_size_unit . ';';
                            } else {
                                $css .= 'height:' . $icon_size . ';';
                                $css .= 'width:' . ($w * $icon_size_val / $h) . $icon_size_unit . ';';
                            }
                        }
                    }
                } else {  // POLICE ICONE
                    if ($iconCode[0] == '\\') {
                        $css .= 'content:"' . $iconCode . '"!important;'; // Pas de texte car police icon active
                    } else {
                        // classe dans hr
                        $hr_attr['class'] .= $iconCode;
                    }
                    if ($options['fontname']) {
                        $css .= 'font-family:"' . $options['fontname'] . '";';
                    }
                    if ($icon_size) {
                        $css .= 'font-size:' . $icon_size . ';';
                    }
                    $css .= ($icon_color) ? 'color:' . $icon_color . ' !important;' : '';
                    $css .= 'line-height:'.$icon_size.';';
                    $css .= 'font-weight:900;';
                }
            }
            // -
            // --- gestion de l'espacement vertical (défaut au centre estimé de l'icone)
            // -
            $top_offset = (floor(($hr_height_val - $icon_size_val) / 2) + (int) $options['icon-v-offset'] - 3);
            $css .= ($icon_size_val > 0) ? 'top:' . $top_offset . $icon_size_unit . ';' : '';
            // CSS pour HR
            // si $top_offset < 0 -> on ajoute à margin-top
            if ($top_offset < $margin_size * -1) {
                $this->add_style($hr_attr['style'], 'margin-top', abs($top_offset) . $icon_size_unit);
            }
            if (($top_offset + $icon_size_val) > max($hr_height_val, $margin_size)) {
                $mb = ($top_offset + $icon_size_val) - $hr_height_val;
                $this->add_style($hr_attr['style'], 'margin-bottom', $mb . $icon_size_unit);
            }
            // --- autres propriétés CSS
            $css .= ($options['icon-bg']) ? 'background:' . $options['icon-bg'] . ';' : '';
            $css .= ($options['icon-space']) ? 'padding:0 ' . $options['icon-space'] . ';' : '';
            $this->load_css_head('hr#id:before{' . $css . '}');
        } // if icon
        // =
        // === retour
        // =
        $html = $this->set_attr_tag('hr', $hr_attr);

        return $html;
    }

    // run
}

// class
