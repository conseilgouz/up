<?php

/**
 * affiche une carte OpenStreetMap
 *
 * syntaxe 1 : {up osmap=latitude, longitude}
 * La latitude/longitude peut être connue sur le site : <a href="https://www.coordonnees-gps.fr" target="_blank">https://www.coordonnees-gps.fr</a>
 *
 * Les tuiles disponibles sont ici : https://wiki.openstreetmap.org/wiki/Tile_servers
 * .
 * syntaxe 2 : multimakers
 * {up osmap=latitude, longitude}
 * 	{marker=latitude, longitude | popup-text | popup-clic=0 | marker-image=img | marker-options=...}
 * {/up osmap}
 *
 * @author   LOMART
 * @version  UP-1.3
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://leafletjs.com/" target"_blank">script de Leaflet</a>
 * @tags     widget
 */

/*
 * v2.2 - fix pour insertion dans onglet
 * - update vers leaflet 1.6.0 - utilisation CDN
 * v2.3 - fix si marqueur se termine par -icon qui implique un -shadow
 * v2.9 : update version leaflet de 1.6 à 1.8
 * v5.0.1 : - update version leaflet de 1.6 à 1.9.4
 * - correction URL des tiles
 */
defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Image\Image; 	
use Joomla\CMS\Language\Text; 	

class osmap extends upAction
{

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     *
     * @return true
     */
    function init()
    {
        // $this->load_file('leaflet/leaflet.css');
        // $this->load_file('leaflet/leaflet.js');
        $opt = array();
        $attr['crossorigin'] = '';
        $attr['referrerpolicy'] = 'no-referrer';
		
        $attr['integrity'] = 'sha512-Zcn6bjR/8RZbLEpLIeOwNtzREBAJnUKESxces60Mpoj+2okopSAcSUIUOseddDm0cxnGQzxIR7vJgsLZbdLE3w==';
        $this->load_file('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css', $opt, $attr);

        $attr['integrity'] = 'sha512-BwHfrr4c9kmRkLw6iXFdzcdWV/PGkVgiIyIWLLlTSXzWQzxuSg4DiQUCpauz/EWjgk5TYQqX/kvn9pG1NpYfqg==';
        $this->load_file('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js', $opt, $attr);

        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // latitude, longitude du centre de la carte (a récupérer sur http://getlatlon.yohman.com)
            'zoom' => 13, // niveau de zoom de la carte
            'height' => 250, // hauteur du bloc carte. La largeur est 100% du parent
            'scale' => 1, // affiche l'échelle. 0:sans, 1:métrique, 2:impérial, 3:métrique&impérial
            'map-options' => '', // liste des options pour la carte. ex: zoomControl:1, keyboard:0
            'tile' => '', // nom de la tuile
            'tile-options' => '', // niveau de zoom maximum, ...
            'tile-url' => '', // url de la tuile
            'marker' => 1, // affiche un marker au centre de la carte
            'marker-image' => '', // 0: aucun ou chemin image pin
            'marker-options' => '', // chaine des options du marqueur. voir https://leafletjs.com/reference-1.3.0.html#icon
            'popup-clic' => 1, // 0: permanent ou 1: sur clic (si texte)
            'popup-text' => '', // texte du popup en bbcode. Si vide, pas de popup
            'gmap' => '', // texte du lien affiché au-dessous de la carte pour afficher la carte sous GMap
            'gmap-url' => '', // optionnel.Permet d'afficher un marqueur
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '' // style inline ajouté au bloc principal
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === Variables de travail
        // pour utiliser id UP comme variable JS
        $id = str_replace('-', '_', $options['id']); // up_10_2
        $mapid = 'map' . $id; // mapup_10_2
                              // === ajout css dans head
        $css_code = '#' . $mapid . '{';
        $css_code .= '  height:' . $options['height'] . 'px;';
        $css_code .= '}';
        $this->load_css_head($css_code);

        // === Préparation pour code JS
        $js = '';
        // --- Coordonnées et options carte
        $options[__class__] = str_replace(' ', '', $options[__class__]);
        list ($lat, $lon) = explode(',', $options[__class__] . ',0,0');
        $zoom = $options['zoom'];
        $js = 'var ' . $mapid . ' = L.map("' . $mapid . '", {';
        $js .= 'center:[' . $lat . ',' . $lon . '],';
        $js .= 'zoom:' . $zoom . ',';
        $js .= $options['map-options'];
        $js .= '});';
        $js .= 'setInterval(function () {' . $mapid . '.invalidateSize();}, 500);'; // v2.1.2
        // --- Tuiles
        if ($options['tile-url']) {
            // si tile-url indiqué, on l'utilise
            $tile_url = str_replace(array(
                '[',
                ']'
            ), array(
                '{',
                '}'
            ), $options['tile-url']);
            $tile_options = $this->strtoarray($options['tile-options']);
        } else {
            // sinon on utilise la définition json indiquée ou celle par défaut
            if ($options['tile'] == '')
                $options['tile'] = 'osm-mapnik';
            $tile = $this->get_jsontoarray('tiles/' . $options['tile'] . '.json');
            if (is_array($tile)) {
                $tile_url = $tile['url'];
                $tile_options = $tile['settings'];
            }
        }

        $tile_options = $this->json_arrtostr($tile_options);
        // recherche clé
        $regex = '/\#(.*-key)\#/U';
        if (preg_match($regex, $tile_options, $pref)) {
            $val = $this->get_action_pref($pref[1]);
            $tile_options = str_replace($pref[0], $val, $tile_options);
        }
        $js .= 'L.tileLayer("' . $tile_url . '",';
        $js .= $tile_options;
        $js .= ').addTo(' . $mapid . ');';

        // --- Markers & popups
        if ($this->content) {
            // si shortcode interne pour marker, on les utilise
            $markers = $this->get_content_shortcode($this->content, 'marker');
        } else {
            // sinon met un marker au centre de la carte sauf marker=0
            $markers = [];
            if ($options['marker'] == 1) {
                $markers[0]['marker'] = $options[__class__];
                $markers[0]['marker-image'] = $options['marker-image'];
                $markers[0]['marker-options'] = $options['marker-options'];
                $markers[0]['popup-clic'] = $options['popup-clic'];
                $markers[0]['popup-text'] = $options['popup-text'];
            }
        }
        // si marker-image dans shorcode principal
        // on crée un objet pour l'utiliser par défaut
        $icon = '';
        if ($options['marker-image']) {
            $js .= 'var icon' . $id . ' = L.icon({';
            $js .= $this->get_icon_options($options['marker-image'], $options['marker-options']);
            $js .= '});';
            $icon = ',{icon:icon' . $id . '}';
        }
        // Mise en place du ou des markers
        $i = 0;
        foreach ($markers as $marker) {
            $i ++;
            $markerid = 'marker' . $id . '_' . $i;
            // position marker
            list ($lat, $lon) = explode(',', $marker['marker'] . ',0,0');
            $js .= 'var ' . $markerid . ' = L.marker(';
            $js .= '[' . $lat . ',' . $lon . ']';
            // image marker
            if (isset($marker['marker-image']) && $marker['marker-image'] > '') {
                $js .= ',{icon: L.icon({';
                $opt = (isset($marker['marker-options'])) ? $marker['marker-options'] : $options['marker-options'];
                $js .= $this->get_icon_options($marker['marker-image'], $opt);
                $js .= '})}';
            } else {
                $js .= $icon;
            }
            $js .= ').addTo(' . $mapid . ');';
            // si popup
            if ($marker['popup-text']) {
                $txt = $this->conversion_bbcode_html($marker['popup-text']);
                $js .= $markerid . '.bindPopup("' . $txt . '")';
                if (isset($marker['popup-clic']) && $marker['popup-clic'] != 1) {
                    $js .= '.openPopup()';
                }
                $js .= ';';
            }
        }
        // ajout echelle
        if ($options['scale']) {
            switch ($options['scale']) {
                case 1:
                    $opt = 'imperial:0';
                    break;
                case 2:
                    $opt = 'metric:0';
                    break;
                default:
                    $opt = '';
            }
            $js .= 'L.control.scale({' . $opt . '}).addTo(' . $mapid . ');';
        }
        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $outer_div['id'] = $mapid;
        $outer_div['class'] = $options['class'];
        $outer_div['style'] = $options['style'];
        // -- lien gmap
        $gmap = '';
        if ($options['gmap']) {
            $gmap .= '<small><a href="';
            if ($options['gmap-url']) {
                $gmap .= $options['gmap-url'];
            } else {
                $gmap .= 'https://www.google.fr/maps/@' . $lat . ',' . $lon . ',' . $zoom . 'z';
            }
            $gmap .= '" target="_blank">';
            $gmap .= $options['gmap'];
            $gmap .= '</a></small>';
        }

        // -- le code en retour
        $out = $this->set_attr_tag('div', $outer_div, true);
        $out .= $gmap;
        $out .= '<script>' . $js . '</script>';

//        $this->msg_info('code JS: '.trim($js));

        return $out;
    }

    // run

    /*
     * retourne les options par défaut pour un marqueur
     */
    function get_icon_options($img, $options = '')
    {
        $out = ''; // 2.8.2
        $img2 = JPATH_ROOT . DIRECTORY_SEPARATOR . $img;
        if (file_exists($img2)) {
            $out = 'iconUrl:"' . Uri::ROOT() . $img . '",';
            if (strpos($img, '-icon.') !== false) {
                $shadowfile = str_replace('-icon.', '-shadow.', $img);
                if (file_exists(JPATH_ROOT . DIRECTORY_SEPARATOR . $shadowfile)) {
                    $out .= 'shadowUrl:"' . Uri::ROOT() . $shadowfile . '",';
                } else {
                    $shadowfile = '';
                }
            }
            if ($options == '') {
                $info = Image::getImageFileProperties($img2);
                $h = $info->height;
                $w = $info->width;
                $out .= 'iconSize:[' . $w . ',' . $h . '],';
                $out .= 'iconAnchor:[' . intval($w / 2) . ',' . $h . '],';
                $out .= 'popupAnchor:[' . (0) . ',' . (- $h) . '],';
                // si ombre existe, on l'utilise
                if (! empty($shadowfile)) {
                    $info = Image::getImageFileProperties(JPATH_ROOT . DIRECTORY_SEPARATOR . $shadowfile);
                    $h = $info->height;
                    $w = $info->width;
                    $out .= 'shadowSize:[' . $w . ',' . $h . '],';
                    $out .= 'shadowAnchor:[' . ($w / 2) . ',' . ($h) . '],';
                }
            } else {
                $out .= $options;
            }
        } else {
            $this->msg_error(Text::sprintf('UP_FILE_NOT_FOUND', Uri::ROOT() . $img));
        }
		
        return $out;
    }

    /*
     * convertit un simili-bbcode en HTML
     */
    function conversion_bbcode_html($bbcode)
    {
        $txt = str_replace(array(
            '[',
            ']',
            '"'
        ), array(
            '<',
            '>',
            '\''
        ), $bbcode);
        // remplacer chemin relatif image
        $regex = '#src=\'(.*)\'#U';
        if (preg_match_all($regex, $txt, $matches)) {
            foreach ($matches[1] as $match) {
                if (strpos($match, '://') === false) {
                    $txt = str_replace($match, Uri::ROOT() . $match, $txt);
                }
            }
        }
        return $txt;
    }
}

// class
