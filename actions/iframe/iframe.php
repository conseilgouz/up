<?php

/**
 * introduire un iFrame responsive dans un article
 *
 * syntaxe {up iframe=URL}
 *
 * @author   LOMART
 * @version  UP-1.9.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit https://css-tricks.com/snippets/sass/maintain-aspect-ratio-mixin/
 * @tags    HTML
 */
defined('_JEXEC') or die;

class iframe extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('up-iframe.css');
        return true;
    }

    function run() {


        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // URL site distant
            'preview' => '', // image affichée avant chargement de l'iframe
            /* [st-iframe] paramétre de l'iframe*/
            'allowfullscreen' => 'allowfullscreen', // autorise le plein ecran
            'scrolling' => 'no', // affiche les ascenseurs si nécessaire
            'ratio' => '16:9', // sous forme largeur:hauteur ou ratio 0.5625 ou pourcentage 56.25%
            'height' => '', // hauteur (utile pour mobile)
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc externe
            'style' => '', // style inline pour bloc externe
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-interne] Interne, conservé pour compatibilité */
            'iframe-class' => '', // classe(s) pour bloc
            'iframe-style' => '', // style inline pour bloc
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $options['scrolling'] = ($options['scrolling'] == 'no') ? 'no' : 'yes';
        // === CSS-HEAD
        $css = $options['css-head'];


        // ===  CSS BLOC CONTAINER
        // RATIO
        if (isset($this->options_user['ratio'])) {
            $ratio = $options['ratio'];
            if ((int) $ratio == 0) {
                $ratio *= 100;
            } elseif (strpos($ratio, ':') !== false) {
                list($w, $h) = explode(':', $ratio);
                $ratio = $h / $w * 100;
            } else {
                $ratio = floatval($ratio);
            }
            $css .= '#id.up-iframe-container:before{padding-top:' . strval($ratio) . '%}';
        }

        // HEIGHT : permet de conserver une hauteur sur mobile pour les iframes l'acceptant
        if ($options['height']) {
            $height = $this->ctrl_unit($options['height'], 'px,vh,rem,em');
            $css .= '#id.up-iframe-container{min-height:' . $height . '}';
        }

        // PREVIEW
        if ($options['preview']) {
            $css .= '#id.up-iframe-container{background:url("' . $options['preview'] . '") no-repeat;background-size:cover}';
        }
        // === LOAD CSS HEAD
        $this->load_css_head($css);


        // === attributs container
        $attr_container['id'] = $options['id'];
        $attr_container['class'] = 'up-iframe-container';
        $this->get_attr_style($attr_container, $options['class'], $options['style']);

        // === attributs iframe
        $attr_iframe['id'] = $options['id'];
        $attr_iframe['src'] = $options[__class__];
        $attr_iframe['class'] = 'up-iframe';
        $this->get_attr_style($attr_iframe, $options['iframe-class'], $options['iframe-style']);
        $attr_iframe['allowfullscreen'] = $options['allowfullscreen'];
        $attr_iframe['scrolling'] = $options['scrolling'];

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_container);
        $html[] = $this->set_attr_tag('iframe', $attr_iframe, 'iframe not supported');
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

// run
}

// class

