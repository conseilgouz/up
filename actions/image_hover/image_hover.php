<?php

/**
 * Superpose un contenu HTML sur une image avec des effets lors du survol
 *
 * {up image-hover=images/image.jpg | effect=xx}
 * -- contenu HTML affiché au survol
 * {/up image-hover}
 *
 * Voir la démo pour les numéros des effets
 *
 * @author    Lomart
 * @version   UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="http://usingcss3.com/ultimate-image-hover-effects-using-css3/" target="_blank">Sanjay Jadon</a>
 * @tags image
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class image_hover extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'hover-effects.css');
        return true;
    }

    function run() {
        // cette action a obligatoirement du contenu
        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
          __class__ => '', // nom de l'image
          'effect' => '11', // nom de l'effet à trouver sur le site démo
          /* [st-bloc] Style CSS pour le bloc externe*/
          'id' => '', // identifiant
          'class' => '', // classe
          'style' => '', // style inline
          /* [st-img] Style CSS pour l'image */
          'img-class' => '', // classe pour image 
          'img-style' => '', // style inline pour image
          /* [st-txt] Style CSS pour le contenu HTML */
          'text-class' => '', // classe pour bloc texte lors survol
          'text-style' => '', // style inline pour bloc texte lors survol
          /* [st-divers] Divers */
          'css-head' => '', // code css pour le head. ATTENTION [] au lieu de {}
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // l'ajout de code CSS dans le head permet l'utilisation de pseudo-elements)
        UpHelper::load_css_head($this,$options['css-head']);

        // === le code HTML
        // structure attendue
        // <div class="effect-1">
        //  <div class="image-box">
        //     <img src="img.jpg" alt="">
        //  </div>
        //  <div class="text-desc">
        //     <h3>TITRE</h3>
        //     <p>Lorem ipsum dolor sit amet,...</p>
        //     <a href="#" class="btn">action</a>
        //  </div>
        // </div>
        // -- ajout options utilisateur dans la div principale

        $effect = ((int) $options['effect'] > 10) ? $options['effect'] : '11';
        $outer_div['class'] = 'port-' . $effect[0] . ' effect-' . $effect[1];
        UpHelper::get_attr_style($this,$outer_div, $options['class'], $options['style']);

        $img_div['class'] = 'image-box';
        UpHelper::get_attr_style($this,$img_div, $options['img-class'], $options['img-style']);

        $text_div['class'] = 'text-desc';
        UpHelper::get_attr_style($this,$text_div, $options['text-class'], $options['text-style']);

        $img_tag['src'] = $options[__class__];
        $img_tag['alt'] = UpHelper::link_humanize($this,$options[__class__]); // TODO sans ext, ni tiret
        // -- le code en retour
        $out = '';
        $out .= UpHelper::set_attr_tag($this,'div', $outer_div);
        $out .= UpHelper::set_attr_tag($this,'div', $img_div);
        $out .= UpHelper::set_attr_tag($this,'img', $img_tag);
        $out .= '</div>';
        $out .= UpHelper::set_attr_tag($this,'div', $text_div);
        $out .= $this->content;
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

// run
}

// class
