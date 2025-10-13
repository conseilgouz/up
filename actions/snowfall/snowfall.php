<?php

/**
 * Simule des chutes de neige ou autres ...
 *
 * syntaxe
 * site : {up snowfall=image}
 * bloc : {up snowfall=image | selector=bloc}
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit Script <a href="https://github.com/loktar00/JQuery-Snowfall" target="_blank">JQuery-Snowfall de loktar00</a>
 * @tags    Body
 */
/*
 * v1.63 - ajout option filter
 */
defined('_JEXEC') or die;

class snowfall extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('snowfall-up.jquery.min.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => 'snow-01.png', // image ou dossier
            'selector' => 'body', // cible pour snowfall
            /* [st-param] Taille, vitesse et nombre des images */
            'nb' => 20, // nombre d'images affichées en même temps
            'size' => '20/40', // taille mini/maxi des images en px
            'speed' => '1/5', // vitesse mini/maxi des images
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'center' => '', // style et classe pour centrage vertical du contenu entre shortcodes
            'z-index' => 99, // z-index des images
            /* [st-divers] Divers */
            'filter' => '', // conditions. Voir doc action filter
        );

        // contôle des options
        $options = $this->ctrl_options($options_def);

        // === selecteur cible
        $selector = ($this->content) ? '#' . $options['id'] : $options['selector'];

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // === options JS
        $js_options['flakeCount'] = $options['nb'];
        $js_options['z-index'] = $options['z-index'];
        list($js_options['minSize'], $js_options['maxSize']) = array_map('intval', explode('/', $options['size'] . '/30/30'));
        list($js_options['minSpeed'], $js_options['maxSpeed']) = array_map('intval', explode('/', $options['speed'] . '/3/5'));

        // -- image(s) passée(s) dans option principale
        $images = array_map('trim', explode(';', $options[__class__]));
        foreach ($images as $image) {
            // c'est une image png
            if (strtolower(pathinfo($image, PATHINFO_EXTENSION)) == 'png') {
                if (dirname($image) == '.') {
                    $flakes[] = $this->actionPath . 'img/' . $image;
                }
                $flakes[] = (dirname($image) == '.') ? $this->actionPath . 'img/' . $image : $image;
            } elseif (is_dir($image)) {
                // chemin complet vers un dossier
                $imgs = glob($image . '/*.png');
                foreach ($imgs as $img) {
                    $flakes[] = $img;
                }
            }
        }

        // --- init JS
        foreach ($flakes as $flake) {
            $js_options['image'] = $this->get_url_relative($flake);
            // -- conversion en chaine Json
            $js_params = $this->json_arrtostr($js_options);
            // -- code JS
            $js_code = '$("' . $selector . '").snowfall(';
            $js_code .= $js_params;
            $js_code .= ');';
            $this->load_jquery_code($js_code);
        }

        // ==== code HTML en retour
        $out = '';
        $attr_content = array();
        $attr_main = array();
        if ($this->content) {
            // --- classe pour centrage vertical
            if ($options['center']) {
                $this->add_class($attr_main['class'], 'up-center-outer');
                $this->get_attr_style($attr_content, 'up-center-inner', $options['center']);
            }

            // attribut
            $this->get_attr_style($attr_main, $options['class'], 'position:relative;' . $options['style']);
            $attr_inner['id'] = $options['id'];
            $attr_inner['style'] = 'position:absolute; top:0; left:0; right:0; bottom:0';
            // code
            $out = $this->set_attr_tag('div', $attr_main);
            $out .= $this->set_attr_tag('div', $attr_inner, true);
            $out .= $this->set_attr_tag('div', $attr_content, $this->content);
            $out .= '</div>';
        }

        return $out;
    }

    // run
}

// class
