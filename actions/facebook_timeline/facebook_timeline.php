<?php

/**
 * affiche la timeline Facebook
 *
 * {up facebook=facebook id}

 * voir https://developers.facebook.com/docs/plugins/page-plugin
 * @author    PMLECONTE
 * @version   UP-1.3
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Widget
 */

/*
 * v5.3 : facebook.js : update sdk de la version 13.0 vers 23.0. 
 *        bug sur data-tabs : https://developers.facebook.com/support/bugs/584988619248795/
 * v2.8 : facebook.js : update sdk de la version 2.5 vers 13.0. Ajout options defer et paramètre crossorigin
 */
defined('_JEXEC') or die();

class facebook_timeline extends upAction
{
    public function init()
    {
        $this->load_file('facebook.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // ID facebook. Voir https://findmyfbid.com
            'tabs' => 'timeline', // Onglets à afficher, par exemple : timeline, events, messages
            'width' => '500', // La largeur du plugin en pixels. Valeur mini = 180, maxi = 500
            'height' => '500', // La hauteur du plugin en pixels. valeur mini = 70.
            'adaptwidth' => 1, // Essayer d’adapter la largeur au conteneur.
            'facepile' => 1, // Affiche les photos de profils quand des amis aiment le contenu.
            'hidecover' => 0, // Masque la photo de couverture dans l’en-tête
            'smallheader' => 0, // Utiliser un en-tête réduit
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe bloc parent
            'style' => '' // style inline bloc parent
        );
        /*
         * note (interne) sur paramètres :
         * on peut saisir 1 ou true / 0 ou false
         * width=500 (valeur maxi) permet avec adaptwidth=1 de toujours remplir la largeur d'une colonne
         */

        // $this->set_option_user_if_true(__class__, $this->actionUserName);
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // attributs pour div externe
        $attr_outer['id'] = $options['id'];
        $attr_outer['class'] = $options['class'];
        $attr_outer['style'] = $options['style'];

        // attributs pour div interne
        $attr_in['class'] = 'fb-page';
        $attr_in['data-href'] = 'https//www.facebook.com/' . $options[__class__];
        $attr_in['data-small-header'] = $options['smallheader'];
        // ne fonctionne pas : bug facebook https://developers.facebook.com/support/bugs/584988619248795/
        if ($options['tabs'] == 'timeline') {
            $attr_in['data-show-posts'] = 'true';
        } else { // on laisse quand même au cas où...
            $attr_in['data-tabs'] = $options['tabs'];
        }
        $attr_in['data-adapt-container-width'] = $options['adaptwidth'];
        $attr_in['data-hide-cover'] = $options['hidecover'];
        $attr_in['data-show-facepile'] = $options['facepile'];
        $attr_in['data-width'] = $options['width'];
        $attr_in['data-height'] = $options['height'];

        $out = $this->set_attr_tag('div', $attr_outer);
        $out .= '<div id="fb-root"></div>';
        $out .= $this->set_attr_tag('div', $attr_in);
        $out .= '<div class="fb-xfbml-parse-ignore"> </div>';
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
