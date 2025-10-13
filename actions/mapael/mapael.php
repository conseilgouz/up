<?php

/**
 * Affiche une carte vectorielle interactive
 *
 * syntaxe
 * {up mapael=nom_carte}
 *    {default-area | ...}
 *    {default-plot | ...}
 *    {default-link | ...}
 *    {area=ID | value=X | ...}
 *    {plot=ID | coord=lat,lon | value=[X1,X2] | text=... | ...}
 *    {link=ID | between=lat1,lon1,lat2,lon2 | factor=0.5 | ...}
 *    {link=ID | between=plot-ID-1, plot-ID-2 | factor=0.5 | ...}
 *    {legend-area=TITRE | ...}
 *    {legend-plot=TITRE | ...}
 *    {legend-slice=LABEL | value=X | ...}
 *    {legend-slice=LABEL | min=X | max=Y | ...}
 * {/up mapael}
 *
 * # Options shortcode principal :
 *   zoom=min,max | zoom-init=niv,lat,lon | csv-xxx | ...
 * # Options communes à tous les shortcodes secondaires :
 *   bd-color | bd-color-hover | bd-dash | bd-width | bd-width-hover | bg-color | bg-color-hover
 *   class | eventHandlers | href | target | transform-hover
 *   text | text-attrs | text-attrs-hover | text-margin | text-position
 *   tooltip | tooltip-class | tooltip-offset-left | tooltip-offset-top | tooltip-overflow-right | tooltip-overflow-bottom
 *   options
 * # Options spécifiques à defaultPlot et plot
 *   type | size | height | width | url | path
 *   saisie rapide : circle=W,color | square=W,color | image=WxH,url | svg=WxH,path
 * # Options pour area
 *   value
 * # Options pour plot
 *   value | coord | plotsOn
 * # Options pour link
 *   between | factor
 * # Options pour legend-area et legend-plot
 *   mode | exclusive | display | legend-class
 *   ml | mb | ml-label | ml-title |mb-title | color-title | color-label | color-label-hover
 *   hide-enabled | hide-opacity | hide-animation
 * # Options pour legend-slice
 *   value | min | max
 *   legend-slice/label | clicked | display
 *   legend-font-size | legend-bd-color | legend-bd-width
 *
 * @author   LOMART
 * @version  UP-2.3
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.vincentbroute.fr/mapael/" target"_blank">script mapael de neveldo</a>
 * @credit    <a href="https://dmitrybaranovskiy.github.io/raphael/" target"_blank">script raphael</a>
 * @tags    Widget
 *
 * */
defined('_JEXEC') or die;

class mapael extends upAction
{
    /**
     * charger les ressources communes a toutes les instances de l'action
     * cette fonction n'est lancee qu'une fois pour la premiere instance
     * @return true
     */
    public function init()
    {
        $this->load_file('mapael.css');
        $this->load_file('//cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js');
        $this->load_file('//cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.min.js');
        $this->load_file('//cdnjs.cloudflare.com/ajax/libs/jquery-mapael/2.2.0/js/jquery.mapael.min.js');
        $this->load_file('//cdn.jsdelivr.net/npm/jquery-mapael@2.2.0/js/jquery.mapael.min.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur parametres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // nom de la carte (fichier .js)
            'zoom' => '', // valeur mini,maxi du zoom
            'zoom-init' => '', // niveau initial et coordonnées du centre
            'options' => '', // liste des options au format mapael. ex: default-area: {attrs:{fill:"#dda0dd"},attrsHover:{fill:"#FF00FF"}}
            /*[st-csv] Gestion des fichiers CSV */
            'csv-areas' => '', // fichier CSV pour définir les areas. Les sous-shortcodes 'area' sont ignores
            'csv-areas-model' => '', // modele pour construire la définition d'une zone
            'csv-plots' => '', // fichier CSV pour définir les plots. Les sous-shortcodes 'plot' sont ignores
            'csv-plots-model' => '', // modele pour construire la définition d'un point
            'csv-links' => '', // fichier CSV pour définir les links. Les sous-shortcodes 'link' sont ignores
            'csv-links-model' => '', // modèle pour construire la définition d'un lien
            /*[st-bulle] Style des bulles d'aide */
            'tooltip-class' => '', // Nom de classe CSS des infobulles
            'tooltip-style' => '', // Proprietes CSS pour les infobulles
            /* [st-divers] Divers */
            'before-init' => '', // voir demo
            'after-init' => '', // voir demo
            /*[st-css] Style CSS */
            'make-html' => '1', // par défaut, les blocs pour la ou les légendes sont crees au-dessous de celui pour la carte.
            'map-class' => 'map', // Nom de classe CSS du conteneur de la carte
            'id' => '', // remplace l'id generee automatiquement par UP
            'class' => '', // classe(s) ajoutees au bloc principal
            'style' => '', // style inline ajoute au bloc principal
            'css-head' => '' // style ajoute dans le HEAD de la page
        );

        // Les tableaux associatifs ci-dessous facilitent le controle et l'evolution
        // des parametres transmis au script MAPAEL.
        // la cle est le nom attendu par le script avec les eventuels guillemets doubles (pour Raphael)
        // la valeur est le nom de l'option du shortcode UP (en minuscules)
        // PARAMETRES COMMUNS (mapael & Raphael)
        // -------------------------------------
        $params_common = array(
            'href' => 'href',
            'target' => 'target',
            'cssClass' => 'class',
            'eventHandler' => 'eventhandler',
            'attrs' => array(
                'opacity' => 'opacity',
                'fill' => 'bg-color',
                'stroke' => 'bd-color',
                '"stroke-width"' => 'bd-width',
                '"stroke-dasharray"' => 'bd-dash',
            ),
            'attrsHover' => array(
                'opacity' => 'opacity-hover',
                'fill' => 'bg-color-hover',
                'stroke' => 'bd-color-hover',
                '"stroke-width"' => 'bd-width-hover',
                'transform' => 'transform-hover',
            ),
            'tooltip' => array(
                'content' => 'tooltip',
                'cssClass' => 'tooltip-class',
                'overflow' => array(
                    'right' => 'tooltip-overflow-right',
                    'bottom' => 'tooltip-overflow-bottom'
                ),
                'offset' => array(
                    'left' => 'tooltip-offset-left',
                    'top' => 'tooltip-offset-top'
                )
            ),
            'text' => array(
                'content' => 'text',
                'position' => 'text-position',
                'margin' => 'text-margin', // {x:"x",y:"y"}
                'attrs' => 'text-attrs',
                'attrsHover' => 'text-attrs-hover',
            )
        );

        // PARAMETRES SHORTCODE PRINCIPAL
        // ------------------------------
        // zoom & zoom.init sont traites par get_coord
        $params_main = array(
            'afterInit' => 'after-init',
            'beforeinit' => 'before-init',
            'tooltip' => array(
                'cssClass' => 'tooltip-class',
                'css' => 'tooltip-style',
            ),
        );

        // PARAMETRES POUR ELEMENTS
        // ------------------------

        $this->params_defaultPlot = array(
            'type' => 'type',
            'size' => 'size',
            'width' => 'width',
            'height' => 'height',
            'url' => 'url',
            'path' => 'path',
            'circle' => 'circle',
            'square' => 'square',
            'svg' => 'svg',
            'image' => 'image',
        );

        $params_plot = array(
            'value' => 'value',
            'coord' => 'coord',
            'plotsOn' => 'plotson'
        );

        $params_area = array(
            'value' => 'value',
        );

        $params_link = array(
            'between' => 'between',
            'factor' => 'factor',
        );

        // PARAMETRES POUR LEGENDES
        // ------------------------

        $this->params_legend = array(
            'mode' => 'mode', // horizontal / vertical (défaut)
            'exclusive' => 'exclusive', // true
            'display' => 'display',
            'cssClass' => 'legend-class',
            'marginLeft' => 'ml',
            'marginLeftLabel' => 'ml-label',
            'marginLeftTitle' => 'ml-title',
            'marginBottom' => 'mb',
            'marginBottomTitle' => 'title-mb',
            'titleAttrs' => array(
                'fill' => 'color-title'
            ),
            'labelAttrs' => array(
                'fill' => 'color-label'
            ),
            'labelAttrsHover' => array(
                'fill' => 'color-label-hover'
            ),
            'hideElemsOnClick' => array(
                'enabled' => 'hide-enabled',
                'opacity' => 'hide-opacity',
                'animDuration' => 'hide-animation',
            )
                // + $params_common
        );

        $this->params_slice = array(
            'label' => 'label',
            'min' => 'min',
            'max' => 'max',
            'sliceValue' => 'value',
            'clicked' => 'clicked', // true
            'display' => 'display', // true
            'legendSpecificAttrs' => array(// onlybe applied to legend elements
                'size' => 'legend-font-size',
                'stroke' => 'legend-bd-color',
                '"stroke-width"' => 'legend-bd-width',
            )
                // + $params_common
                // + $params_defaultPlot si plot
        );

        // les regles pour les parametres
        // -----------------------------------
        // la fonction ou controle pour les noms UP de $params_common
        $this->ctrl_params = array(
            'after-init' => 'fct|get_upcode',
            'bd-color' => 'fct|get_string',
            'bd-color-hover' => 'fct|get_string',
            'bd-dash' => 'list|-,.,-.,-..,. ,- ,--,- .,--.,--..',
            'bd-width' => 'fct|get_int',
            'bd-width-hover' => 'fct|get_int',
            'before-init' => 'fct|get_upcode',
            'between' => 'fct|get_between',
            'bg-color' => 'get_string',
            'bg-color-hover' => 'get_string',
            'circle' => 'fct|get_plot',
            'clicked' => 'fct|get_boolean',
            'color-label' => 'fct|get_string',
            'color-label-hover' => 'fct|get_string',
            'color-title' => 'fct|get_string',
            'coord' => 'fct|get_coord',
            'display' => 'fct|get_boolean',
            'eventHandlers' => 'fct|get_upcode',
            'exclusive' => 'fct|get_boolean',
            'factor' => 'fct|get_float',
            'fill' => 'fct|get_string',
            'height' => 'fct|get_int',
            'hide-animation' => 'fct|get_int',
            'hide-enabled' => 'fct|get_boolean',
            'hide-opacity' => 'fct|get_float',
            'image' => 'fct|get_plot',
            'legend-bd-color' => 'fct|get_string',
            'legend-bd-width' => 'fct|get_int',
            'legend-font-size' => 'fct|get_int',
            'max' => 'fct|get_float',
            'mb' => 'fct|get_int',
            'mbl-title' => 'fct|get_int',
            'min' => 'fct|get_float',
            'ml' => 'fct|get_int',
            'ml-label' => 'fct|get_int',
            'ml-title' => 'fct|get_int',
            'mode' => 'list|horizontal,vertical',
            'options' => 'fct|get_upcode',
            'position' => 'list|inner,right,left,top,bottom',
            'size' => 'fct|get_int',
            'square' => 'fct|get_plot',
            'svg' => 'fct|get_plot',
            'target' => 'list|_blank,_self',
            'text-attrs' => 'fct|get_upcode',
            'text-attrs-hover' => 'fct|get_upcode',
            'text-margin' => 'fct|get_int',
            'tooltip-offset-left' => 'fct|get_int',
            'tooltip-offset-top' => 'fct|get_int',
            'tooltip-overflow-right' => 'fct|get_boolean',
            'tooltip-overflow-bottom' => 'fct|get_boolean',
            'tooltip-style' => 'fct|get_upcode',
            'type' => 'list|circle,square,image,svg',
            'url' => 'fct|get_image_path',
            'value' => 'fct|get_value',
            'width' => 'fct|get_int',
        );

        // variable de travail. Liste des classes pour les blocs de legendes
        $this->legendClass = array();

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // ctrl shortcodes secondaires
        $SCOK = array('default-area', 'default-plot', 'default-link', 'area', 'plot', 'link', 'legend-area', 'legend-plot', 'legend-slice',);
        $SC_all = $this->get_content_shortcode($this->content);
        foreach ($SC_all as $SC) {
            $key = array_key_first($SC);
            if (!in_array($key, $SCOK)) {
                $this->msg_error($this->trad_keyword('ERR_SUBKEY', $key));
            }
        }

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // --- charger la carte
        $map = $this->options[__class__];
        if ($map[0] !== '/') {
            $map = '/' . $this->actionPath . 'maps/' . $map;
        }
        $map = (strrchr($map, '.') == '.js') ? $map : $map . '.js';
        $this->load_file($map);
        // le nom seul pour le code js (sans .min eventuel)
        $map = preg_replace('#(.min.js|.js)#', '', basename($map));

        // =========== le code JS
        // -- ouverture
        $js_code = '$(function () {';
        $js_code .= '$("#' . $this->options['id'] . '").mapael({';
        // ----------- Options carte
        $js_code .= 'map: {';
        $js_code .= 'name: "' . $map . '"';
        $js_code .= $this->make_params_main($params_main);
        // options en dernier pour ecraser option identique
        //        if ($this->options['options'])
        //            $js_code .= ',' . $this->get_code($this->options['options']);
        // ----------- DefaultArea
        $js_code .= $this->make_params('defaultArea', 'default-area', $params_common);
        $js_code .= $this->make_params('defaultPlot', 'default-plot', $params_common, $this->params_defaultPlot);
        $js_code .= $this->make_params('defaultLink', 'default-link', $params_common);
        $js_code .= '}';  // fin map
        // ----------- legend
        // Test pour ajout de la DIV legend
        $js_code .= $this->make_params_legend($params_common);
        // ----------- Areas / Plots / link
        $js_code .= $this->make_params('areas', 'area', $params_area, $params_common);
        $js_code .= $this->make_params('plots', 'plot', $params_plot, $params_common, $this->params_defaultPlot);
        $js_code .= $this->make_params('links', 'link', $params_link, $params_common);
        // ----------- cloture
        $js_code .= PHP_EOL . '});'; // mapael
        $js_code .= '});'; // $(function
        // pas joli, mais efficace !!
        $js_code = str_replace(
            array(',,,', ',,', ',' . PHP_EOL . ',', '{,', '{' . PHP_EOL . ',', ',}'),
            array(',', ',', ',', '{', '{', '}'),
            $js_code
        );
        $this->load_jquery_code($js_code);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $attr_main['id'] = $this->options['id'];
        $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);

        // attribut HTML
        //        $attr_main = $this->get_attr_style($attr_array, 'mapcontainer', $this->options['class'], $this->options['style']);
        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main);
        if ($this->options['make-html']) {
            $html[] = '<div class="' . $this->options['map-class'] . '">';
            $html[] = '<span>Alternative content for the map</span>';
            $html[] = '</div>';
            foreach ($this->legendClass as $class) {
                $html[] = '<div class="' . $class . '">';
                $html[] = '<span>Alternative content for the legend</span>';
                $html[] = '</div>';
            }
        }
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    /*
     * make_params_main
     * parametres pour le shortcode principal
     */

    public function make_params_main($defParam)
    {
        $ret_code = '';
        // on indique le nom de la classe du bloc carte, si specifie par l'utilisateur
        if (!empty($this->options_user['map-class'])) {
            $ret_code = ',cssClass:"' . $this->options_user['map-class'] . '"';
        }
        //
        $ret_code .= $this->get_zoom($this->options);
        //
        $ret_code .= $this->get_params_recurse('', $this->options_user, $defParam);
        // options en dernier pour ecraser option identique
        if ($this->options['options']) {
            $ret_code .= ',' . $this->get_code($this->options['options']);
        }
        return $ret_code;
    }

    /*
     * make_params_default
     * construit la chaine json pour les options par défaut
     * $section = default-area, default-plot ou default-link
     */

    public function make_params($key, $upParams, ...$defParams)
    {
        //--- si un fichier CSV existe pour $key, on l'utilise
        if (!empty($this->options['csv-' . $key])) {
            $ret_code = PHP_EOL . ',' . $key . ':{';
            $ret_code .= $this->get_csv_file($key);
            return $ret_code . '}';
        }
        //--- si $upParams est une chaine, c'est le nom du shortcode secondaire
        if (is_array($upParams) === false) {
            $upParams = $this->get_content_shortcode($this->content, $upParams);
        }

        //--- si vide, on retourne rien
        if (empty($upParams)) {
            return '';
        }

        //--- on y va
        $ret_code = PHP_EOL . ',' . $key . ':{';
        $comma = '';
        foreach ($upParams as $upParam) {
            $subkey = ($upParam[array_key_first($upParam)]);
            $ret_code .= ($subkey === true) ? ',' : PHP_EOL . $comma . '"' . $subkey . '":{';
            foreach ($defParams as $defParam) {
                $ret_code .= ltrim($this->get_params_recurse('', $upParam, $defParam), ',');
            }
            // appel code manuel en fin
            if (!empty($upParam['options'])) {
                $ret_code .= ',' . $this->get_code($upParam['options']);
            }
            $ret_code .= ($subkey === true) ? '' : '}';
            $comma = ',';
        }
        return $ret_code . '}';
    }

    /*
     * make_params_legend
     * ------------------
     * construit la chaine des parametres pour les legendes
     */

    public function make_params_legend($params_common)
    {
        $ret_code = '';
        $upOptions = $this->get_content_shortcode($this->content, 'legend.*');
        if (empty($upOptions)) {
            return;
        }

        // on y va !
        $nb_legend['plot'] = count($this->get_content_shortcode($this->content, 'legend-plot'));
        $nb_legend['area'] = count($this->get_content_shortcode($this->content, 'legend-area'));

        $ret_code .= ',legend:{';
        $cpt_legend['plot'] = 0;
        $cpt_legend['area'] = 0;
        $legendTypeCurrent = '';
        $cptSlice = 0;

        foreach ($upOptions as $upOption) {
            $ret_code .= PHP_EOL;
            $key = rtrim(array_key_first($upOption), ' s');
            switch ($key) {
                case 'legend-plot':
                case 'legend-area':
                    $legendType = str_replace('legend-', '', $key);
                    // --- fermeture de la legende precedente
                    if ($legendTypeCurrent != '') { // pas si premiere
                        $ret_code .= ($cptSlice > 0) ? ']}' : '';
                        if ($cpt_legend[$legendTypeCurrent] > 1) {
                            $ret_code .= ']';
                        }
                    }
                    $cpt_legend[$legendType]++;

                    // -- on met un nom unique pour la classe
                    if (empty($upOption['legend-class'])) {
                        $upOption['legend-class'] = $key . $cpt_legend[$legendType];
                    }
                    $this->legendClass[] = $upOption['legend-class'];

                    // on ouvre la nouvelle legende
                    if ($cpt_legend[$legendType] == 1) { // 1ere du type
                        $ret_code .= ($legendTypeCurrent != '') ? ',' : ''; // le 2e type
                        $ret_code .= $legendType . ':';
                        $ret_code .= ($nb_legend[$legendType] > 1) ? '[' : ''; // si +sieur meme type
                    }
                    $ret_code .= ($cpt_legend[$legendType] == 1) ? '{' : ',{';

                    // les parametres generaux de la legende
                    $ret_code .= $this->get_val('title', $upOption[$key]);
                    $ret_code .= $this->get_params_recurse('', $upOption, $this->params_legend);
                    $ret_code .= $this->get_params_recurse('', $upOption, $params_common);
                    if (!empty($upOption['options'])) {
                        $ret_code .= $this->get_code($upOption['options']);
                    }

                    $legendTypeCurrent = $legendType;
                    $cptSlice = 0;
                    break;
                default:
                    $ret_code .= ($cptSlice == 0) ? ',slices:[{' : ',{';
                    // l'arg principal est le label. Ecrase par option label si elle existe
                    $ret_code .= 'label:"' . $upOption[$key] . '"';
                    //$ret_code .= $this->get_val('sliceValue', $upOption['value']);
                    //$ret_code .= $this->get_common_plot($upOption);
                    $ret_code .= $this->get_params_recurse('', $upOption, $params_common);
                    $ret_code .= $this->get_params_recurse('', $upOption, $this->params_defaultPlot);
                    $ret_code .= $this->get_params_recurse('', $upOption, $this->params_slice);
                    if (isset($upOption['options'])) {
                        $ret_code .= $this->get_code($upOption['options']);
                    }
                    $cptSlice++;
                    break;
            }
            if ($cptSlice > 0) {
                $ret_code .= '}';
            }
        }
        $ret_code .= ($cptSlice > 0) ? ']}' : '';

        // fermeture de la legende precedente
        $ret_code .= ($nb_legend[$legendTypeCurrent] > 1) ? ']}' : '}';

        return $ret_code;
    }

    /*
     * get_common_plot
     * retourne les parametres specifiques aux Plots
     */

    public function get_common_plot($upParams)
    {
        $ret_code = '';
        $type = (isset($upParams['type'])) ? strtolower($upParams['type']) : 'circle';
        switch ($type) {
            case 'circle':
            case 'square' :
                $ret_code .= ',type:"' . $type . '"';
                if (!empty($upParams['size'])) {
                    $ret_code .= ',size:' . $upParams['size'];
                }
                break;
            case 'image':
                $ret_code .= ',type:"' . $type . '"';
                if ($upParams['size']) {
                    list($w, $h) = explode(',', $upParams['size'] . ',');
                    $ret_code .= ',width:' . $w;
                    $ret_code .= ($h) ? ',height:' . $h : '';
                }
                if (!empty($upParams['src'])) {
                    $ret_code .= ',url:"' . $this->get_url_relative($upParams['src']) . '"';
                }
                break;
            case 'svg':
                $ret_code .= ',type:"' . $type . '"';
                if ($upParams['size']) {
                    list($w, $h) = explode(',', $upParams['size'] . ',');
                    $ret_code .= ',width:' . $w;
                    $ret_code .= ($h) ? ',height:' . $h : '';
                }
                if (!empty($upParams['src'])) {
                    $ret_code .= ',path:"' . $upParams['src'] . '"';
                    break;
                }
                // TODO : charger fichier SVG ou code
                // no break
            default:
                if ($type != '') {
                    $this->msg_error($type . $this->trad_keyword('NOT_OPTION'));
                }
        }
        if (isset($upParams['coord'])) {
            $ret_code .= $this->get_coord('', $upParams['coord']);
        }
        if (isset($upParams['ploton'])) {
            $ret_code .= ',plotOn:' . $upParams['ploton'];
        }

        return $ret_code;
    }

    /*
     * get_params_recurse
     * fonction recursive utilisee uniquement par get_common
     */

    public function get_params_recurse($key, $upParams, $jsParams)
    {
        $ret = '';
        foreach ($jsParams as $jsKey => $upKey) {
            if (is_array($upKey)) {
                $ret .= $this->get_params_recurse($jsKey, $upParams, $upKey);
            } elseif (isset($upParams[$upKey])) {
                $ret .= $this->ctrl_param($jsKey, $upKey, $upParams[$upKey]) . ',';
            }
        }
        if ($ret == '') {
            $out = '';
        } elseif ($key != '') {
            $out = $key . ':{' . rtrim($ret, ',') . '},';
        } else {
            $out = ',' . ltrim($ret, ',');
        }
        return $out;
    }

    /*
     * quote
     * ajoute des guillemets si $arg est une chaine
     */

    public function quote($arg)
    {
        if (!(is_numeric($arg) && $arg[0] != '{')) {
            return '"' . $this->get_bbcode($arg) . '"';
        } else {
            return $arg;
        }
    }

    /*
     * get_boolean
     * si $val=1, retourne ,$key:true
     */

    public function get_boolean($key, $val)
    {
        return $key . ':' . ((empty($val)) ? 'false' : 'true');
    }

    /*
     * get_val
     * si $val, retourne ,$key:"$val"
     */

    public function get_val($key, $val)
    {
        return (empty($val)) ? '' : ',' . $key . ':' . $this->quote($val);
    }

    /*
     * get_value
     * ajout de guillemets a $val qui peut etre :
     * - un nombre ou une chaine
     * - un tableau avec des nombres et des chaines
     */

    public function get_value($key, $val)
    {
        $out = '';
        if ($val[0] == '[') {
            $val = trim($val, '[] ');
            $tmp = explode(',', trim($val, '[] '));
            foreach ($tmp as $e) {
                $out .= ',' . $this->quote($e);
            }
            $out = ',' . $key . ':[' . trim($out, ',') . ']';
        } else {
            $out = ',' . $key . ':' . $this->quote($val);
        }
        return $out;
    }

    /*
     * get_string
     * force retour comme chaine
     * si $val, retourne ,$key:"$val"
     */

    public function get_string($key, $val)
    {
        return $key . ':"' . $val . '"';
    }

    /*
     * get_float
     * force retour comme entier
     * si $val, retourne ,$key:(int)$val
     */

    public function get_float($key, $val)
    {
        return $key . ':' . (float) $val;
    }

    /*
     * get_float
     * force retour comme reel
     * si $val, retourne ,$key:(int)$val
     */

    public function get_int($key, $val)
    {
        return $key . ':' . (int) $val;
    }

    /*
     * get_zoom
     * retourne la chaine parametre pour les options zoom
     */

    public function get_zoom($upOptions)
    {
        if ($upOptions['zoom'] || $upOptions['zoom-init']) {
            $out = ',zoom:{enabled:true';
            // zoom
            $tmp = explode(',', $upOptions['zoom']);
            if (count($tmp) > 1) {
                $out .= ',minLevel:' . $tmp[0];
            }
            if (count($tmp) > 0) {
                $out .= ',maxLevel:' . end($tmp);
            }
            // zoom-init : level, x/long, y/lat
            if ($upOptions['zoom-init']) {
                $tmp = explode(',', $upOptions['zoom-init'] . ',0,0,0');
                $out .= ',init:{level:' . $tmp[0];
                $out .= $this->get_coord('', $tmp[1] . ',' . $tmp[2]);
                $out .= '}';
            }
            return $out . '}';
        }
        return '';
    }

    /*
     * get_coord
     * retourne une coordonnee sous forme :
     * y/x si nombre entier
     * latitude/longitude si float
     * l'ordre est celui utilise dans les URL de Google Maps
     * $key inutilise existe pour uniformiser les appels de ctrl_param
     */

    public function get_coord($key, $val)
    {
        $out = '';
        if ($val) {
            $tmp = explode(',', $val . ',0,0,0');
            $out .= (floatval($tmp[0]) != intval($tmp[0])) ? ',latitude:' : 'y:';
            $out .= $tmp[0];
            $out .= (floatval($tmp[1]) != intval($tmp[1])) ? ',longitude:' : ',x:';
            $out .= $tmp[1];
        }
        return $out;
    }

    /*
     * get_plot : saisie rapide d'un plot
     * circle=taille,color
     * square=taille,color
     * image=taille,imagePath
     * svg=taille,path-svg
     */

    public function get_plot($key, $val)
    {
        $ret_code = ',type:"' . $key . '"';
        // si taille indiquee, on l'utilise sinon elle doit etre definie par default-plot
        if ((int) $val[0] != 0) {
            $size = strstr($val, ',', true);
            list($w, $h) = array_map('intval', explode('x', $size . 'x'));
            $h = (empty($h)) ? $w : $h;
            $val = substr($val, strpos($val, ',') + 1);
            if ($key == 'image' || $key == 'svg') {
                $ret_code .= ',width:' . $w . ',height:' . $h;
            } else {
                $ret_code .= ',size:' . $w;
            }
        }
        switch ($key) {
            case 'circle':
            case 'square':
                if ($val != '') {
                    $ret_code .= ',attrs:{fill:"' . $val . '"}';
                }
                break;
            case 'image':
                $ret_code .= ',url:"' . $this->get_url_relative($val) . '"';
                break;
            case 'svg':
                $ret_code .= ',path:"' . $val . '"';
                break;
        }
        return $ret_code;
    }

    /*
     * get_between
     * x1,y1,x2,y2  : longitude/latitude  ou x/y
     * id1,id2      : ID
     */

    public function get_between($key, $val)
    {
        $out = '';
        $tmp = explode(',', $val);
        switch (count($tmp)) {
            case 2: //
                $out = '"' . $tmp[0] . '","' . $tmp[1] . '"';
                break;
            case 4:
                $out = '{' . $this->get_coord('', $tmp[0] . ',' . $tmp[1]) . '}';
                $out .= ',{' . $this->get_coord('', $tmp[2] . ',' . $tmp[3]) . '}';
                break;
            default:
                $this->msg_error($this->trad_keyword('BETWEEN'));
        }
        return ',between:[' . $out . ']';
    }

    public function get_image_path($key, $val)
    {
        return ',' . $key . ':"' . $this->get_url_relative($val) . '"';
    }

    /*
     * get_upcode
     * convertit du code JS ou css saisi dans un shortcode
     */

    public function get_upcode($key, $val)
    {
        if (empty($val)) {
            return '';
        }

        $out = (empty($key)) ? '' : ',' . $key . ':';
        return ',' . $out . $this->get_code($val);
    }

    public function ctrl_param($jsKey, $upKey, $val)
    {
        if (isset($this->ctrl_params[$upKey])) {
            if (strpos($this->ctrl_params[$upKey], '|') === false) {
                $this->ctrl_params[$upKey] = 'fct|' . $this->ctrl_params[$upKey];
            }
            list($fct, $arg) = explode('|', $this->ctrl_params[$upKey]);
            switch ($fct) {
                case 'list' :
                    $val = strtolower($val);
                    $tmp = explode(',', $arg);
                    if (in_array($val, $tmp)) {
                        $out = ',' . $jsKey . ':"' . $val . '"';
                    } else {
                        $this->msg_error($val . $this->trad_keyword('FORBIDDEN_VALUE') . $jsKey . ' - correct: ' . $arg);
                    }
                    break;
                case 'fct' :
                    $out = $this->{$arg}($jsKey, $val);
                    break;
                default:
                    $out = ',' . $jsKey . ':' . $this->quote($val);
                    break;
            }
            return $out;
        }
        return ',' . $jsKey . ':' . $this->quote($val);
    }

    /*
     * get_csv_file
     * ---------------
     * appelle par make_params pour ajouter aux sections plots et areas
     * des donnees issues d'un fichier CSV
     *
     */

    public function get_csv_file($key)
    {
        $filename = $this->options['csv-' . $key];
        if ($filename[0] !== '/') {
            $filename = $this->actionPath . '/map/' . $filename;
        }
        $filename = ltrim($filename, '/ ');
        $csv = $this->get_html_contents($filename);
        $csv = $this->get_content_csv($csv, false);
        if (empty($csv)) {
            $this->msg_error('File not found :' . $filename);
            return '';
        }
        // recherche du modele
        // 1 - option dans shortcode
        $model = $this->options['csv-' . $key . '-model'];
        if ($model != '') {
            // decrytage du model
            $model = $this->get_code($model);
            $model = $this->get_bbcode($model);
        } else {
            // 2 - dans un fichier meme nom avec extension : .model
            $model = substr($filename, 0, strrpos($filename, '.')) . '.model';
            $model = file_get_contents($model);
        }
        if (empty($model)) {
            $this->msg_error($this->trad_keyword('MODEL_NOT_FOUND') . $filename);
            return '';
        }
        // la 1ere ligne doit etre le nom des colonnes
        $colname = array_map('trim', str_getcsv($csv[0], ';', '"', '\\'));
        array_shift($csv);
        // le contenu du CSV
        foreach ($csv as $lign) {
            $col = array_map('trim', str_getcsv($lign, ';', '"', '\\'));
            $lign = $model;
            foreach ($colname as $k => $name) {
                $lign = str_replace('%' . $name . '%', $col[$k] ?? '', $lign);
            }
            $out[] = $lign . ',';
        }
        return implode(PHP_EOL, $out);
    }

    /*
     * get_map_path
     */

    //    function get_map_path($url) {
    //        $url = trim($url);
    //        if ($url[0] !== '/') {
    //            $url = $this->actionPath . 'maps/' . $url;
    //        } else {
    //            $url = ltrim($url, '/');
    //        }
    //        return $url;
    //    }
}

// class
