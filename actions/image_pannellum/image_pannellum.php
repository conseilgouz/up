<?php

/**
 * Affiche un panorama 3D à partir d'une image equirectangular
 *
 * syntaxe {up image_pannellum=chemin_image_equirectangular}
 *
 *
 *
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://pannellum.org" target"_blank">script pannellum de Matthew Petroff.</a>
 * @tags image
 */

defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class image_pannellum extends Lomart\Plugin\Content\Up\Extension\Up {

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     * @return true
     */
    function init() {
        UpHelper::load_file($this,'pannellum.css');
        UpHelper::load_file($this,'pannellum.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // chemin de l'image
            'height' => '400px', // hauteur en px ou vh
            'width' => '100%', // largeur en px ou %
            'fullscreen' => '0', // 1 pour autoriser la vue plein écran
            'language' => '', //  liste motclé (bylineLabel,loadButtonLabel,loadingLabel) + traduction. Exemple:bylineLabel:lang[en=by %s;fr:par %s], loadingLabel:Loading...
            'options' => '', // liste des options supplémentaires . ex: showZoomCtrl:true,compass:true -  Attention au min/maj. voir <a href="https://pannellum.org/documentation/reference" target="_blank">cette page</a>
            /* [st-css] Style CSS */
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour configuration */
            'panorama' => '', // chemin image (il est rempli automatiquement avec l'argument principal du shortcode)
            'preview' => '', // chemin image preview
            'type' => 'equirectangular', // type de l'image
            'title' => '', // titre. pseudo BBCode et traduction acceptes
            'author' => '', // auteur. pseudo BBCode et traduction acceptes
            'authorURL' => '', // lien vers site auteur
            'autoLoad' => '0', // chargement auto de l'image
            'autoRotate' => '0', // rotation nombre de degrés par seconde
            'showControls' => '1', // 0 pour masquer tous les boutons
            'showZoomCtrl' => '1', // 0 pour masquer les boutons +/- du zoom
            'showFullscreenCtrl' => '1', // 0 pour masquer le bouton plein écran
            'hotSpotDebug' => '0', // 1 pour afficher les coordonnées hotspot dans la console
        );

        // affecter l'option principale à une option JS
        $this->options_user['panorama'] = $this->options_user[__class__];

        // traiter language qui peut contenir des traductions avant nettoyage par ctrl_options
        if (isset($this->options_user['language'])) {
            $tmp = UpHelper::params_decode($this,$this->options_user['language']);
            //permettre la saisie de balise HTML inline
            $language = str_replace(array('[', ']'), array('<', '>'), $tmp);
            $this->options_user['language'] = '';
        }

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);
        // --- conversion pseudo-BBcode
        $options['title'] = str_replace(array('[', ']'), array('<', '>'), $options['title']);
        $options['author'] = str_replace(array('[', ']'), array('<', '>'), $options['author']);

        // ======= Prise en charge des hotspots
        $spot_def = array(
            'pitch' => 0.0,
            'yaw' => 0.0,
            'type' => 'xxx',
            'text' => '', // . pseudo BBCode accepte
            'URL' => '',
            'cssClass' => '',
        );
        if ($this->content) {
            // si hotSpots interne, on les utilise
            $hotSpots = UpHelper::get_content_shortcode($this,$this->content, 'hotspot');
            foreach ($hotSpots AS $hotSpot) {
                $hotSpot['text'] = str_replace(array('[', ']'), array('<', '>'), $hotSpot['hotspot']);
                $js_hotspot[] = UpHelper::only_using_options($this,$spot_def, $hotSpot);
            }
        }

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);

        // --- conversion pseudo-BBcode
        if (isset($js_options['title']))
            $js_options['title'] = str_replace(array('[', ']'), array('<', '>'), $js_options['title']);
        if (isset($js_options['author']))
            $js_options['author'] = str_replace(array('[', ']'), array('<', '>'), $js_options['author']);

        // ajout des hotspots
        if (isset($js_hotspot)) {
            $js_options['hotSpots'] = $js_hotspot;
        }
        // ajout des options
        if ($options['options']) {
            $js_options = array_merge($js_options, UpHelper::params_decode($this,$options['options']));
        }
        // ajout des traductions
        if (isset($language)) {
            $js_options['strings'] = $language;
        }

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = UpHelper::json_arrtostr($this,$js_options);


        // -- initialisation
        $js_code = 'pannellum.viewer(\'' . $options['id'] . '\', ';
        $js_code .= $js_params;
        $js_code .= ');';

        // === les personnalisations CSS dans le head
        // #ID est remplace par l'id de l'instance
        UpHelper::load_css_head($this,$options['css-head']);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
//		$attr['id'] = 'panorama'; // $options['id'];
        $attr['id'] = $options['id'];
        $attr['class'] = $options['class'];
        $attr['style'] = 'width:' . UpHelper::ctrl_unit($this,$options['width'], 'px,%');
        $attr['style'] .= ';height:' . UpHelper::ctrl_unit($this,$options['height'], 'px,vh');
        UpHelper::add_str($this,$attr['style'], $options['style'], ';');
        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $attr, true);
        $html[] = UpHelper::load_js_code($this,$js_code, false);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
