<?php

/**
 * Ajuste un texte à son conteneur
 *
 * syntaxe {up up text-fit=option_principale}texte{/up text-fit}
 *
 * @version  UP-2.2 
 * @author  LOMART
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.cssscript.com/scale-text-textblock/" target"_blank">script Textblock de glyphic-co</a>
 * @tags    HTML
 *
 */
/*
 * v3.1 fix quote sur $arg['target'] (lign 89)
 */
defined('_JEXEC') or die();

class text_fit extends upAction
{

    function init()
    {
        // charger les ressources communes ÃƒÂ  toutes les instances de l'action
        $this->load_file('textblock.min.js');
        return true;
    }

    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // sélecteur du bloc. ex: h1, h1.foo, #id, h2#id, ...
            /* [st-def] Définition des valeurs minimales/maximales */
            'blocsize' => '320-960', // largeur mini-maxi en px du bloc conteneur
            'fontsize' => '1-1.8', // taille mini-maxi en em de la police. Autre unité : em,rem,px,ex,%,vh,vw. ex : 16-32px
            'lineheight' => '1.33-1.25', // hauteur de ligne mini-maxi. facteur multiplicateur de la taille de la police
            'fontweight' => '', // graisse mini-maxi si police variable. ex: 400-900
            /* [st-divers] Divers */
            'fontfile' => '', // chemin vers le fichier d'une police de caractère
            'fontclass' => '', // nom de la classe attribué à la police
            'container' => 'parent', // bloc utilisé pour calcul blocsize. parent ou self.
            /* [st-css] Style CSS */
            'tag' => 'div', // balise par defaut
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ====
        $main_selector = '';
        if ($options[__class__] == '') {
            // sans argument principal, on cible uniquement cette instance
            $main_tag = $options['tag'];
            $main_class = $options['id'];
            $main_selector = $main_tag . '.' . $options['id'];
        } else {
            // si #id dans le selecteur.
            $options[__class__] = str_ireplace('#id', '#' . $options['id'], $options[__class__]);
            // si {up text-fit=h2#foo | id=foo}
            $options[__class__] = str_replace('#', '.', $options[__class__]);

            list ($main_tag, $main_class) = explode('.', $options[__class__] . '.');
            $main_tag = ($main_tag == '') ? $options['tag'] : $main_tag;

           if (! empty($this->options_user['blocsize']) || ! empty($this->options_user['fontsize']) || ! empty($this->options_user['fontweight']) || ! empty($this->options_user['lineheight'])) {
                $main_selector = $main_tag;
                $main_selector .= ($main_class != '') ? '.' . $main_class : '';
           }
        }
        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === Analyse options
        if ($main_selector) {
            $arg['target'] = '"'.$main_selector.'"';

            list ($arg['minWidth'], $arg['maxWidth']) = $this->minmax($options['blocsize']);
            list ($arg['minWidth_FontSize'], $arg['maxWidth_FontSize']) = $this->minmax($options['fontsize']);
            list ($arg['minWidth_LineHeight'], $arg['maxWidth_LineHeight']) = $this->minmax($options['lineheight']);
            list ($arg['minWidthVariableGrade'], $arg['maxWidthVariableGrade']) = $this->minmax($options['fontweight']);
            if (preg_match('#(?:px|rem|em|ex|%|vh|vw)+#i', $options['fontsize'], $unit)) {
                $arg['units'] = $unit[0];
            }
            if (strtolower($options['container']) != 'parent')
                $arg['container'] = 'self';
            // -- preparation script
            $code[] = '<script>';
            $code[] = 'Textblock([{';
            foreach ($arg as $k => $v) {
                if ($v != null) {
                    $quote = ((intval($v) * 0) == $v) ? '"' : '';
                    $code[] = $k . ':' . $quote . $v . $quote . ',';
                }
            }
            $code[] = '}]);';
            $code[] = '</script>';
            $this->load_custom_code_head(implode(PHP_EOL, $code));
        }
        // === Demande chargement police
        if ($options['fontfile'] != '') {
            $options['fontclass'] = ($options['fontclass'] != '') ? $options['fontclass'] : $options['id'];
            $css = '@font-face{font-family:"' . $options['fontclass']. '";';
            $css .= 'src:url("' . $this->get_url_absolute($options['fontfile']) . '")}';
            $css .= '.' . $options['fontclass'] . '{font-family:"' . $options['fontclass'] . '"}';
            $this->load_css_head($css);
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['fontclass'], $main_class);
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html = $this->set_attr_tag($main_tag, $attr_main, $this->content);

        return $html;
    }

    // run
    function minmax($param)
    {
        $param = preg_replace("#[^0-9\-\.]#", "", $param);
        if (substr_count($param, '-') == 1) {
            return array_map('trim', explode('-', $param));
        } else {
            return '';
        }
    }
}

// class
