<?php

/**
 * Graphiques statistiques avec GoogleChart
 *
 * syntaxe {up chart=type_chart}... data ...{/up chart}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  https://developers.google.com/chart/interactive/docs
 * @tags    Widget
 */
/*
 * v3.1 : fix resize sur toutes les instances
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class chart extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        UpHelper::load_file($this,'https://www.gstatic.com/charts/loader.js');
        // Load Charts and the corechart package.
        UpHelper::load_js_code($this,'google.charts.load(\'current\', {\'packages\':[\'corechart\']});');
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! UpHelper::ctrl_content_exists($this)) {
            return false;
        }
        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // type de chart : area, bar,bubble,column,combo,line,pie,scatter,SteppedArea
            'separator' => ',', // séparateur des valeurs dans la liste
            /* [st-format] mise en forme du graph */
            'area' => '', // valeur en % dans l'ordre : left, top, width, height. EX: 10,25,90,75
            'maximized' => 0, // affichage remplit le bloc
            'height' => '', // min-height bloc parent
            'colors' => '', // liste des couleurs
            /* [st-title] titre du graph */
            'title' => '', // titre du graphique
            'title-position' => '', // in, out, none (defaut)
            'title-style' => '', // color: 'blue', fontsize: '14px', bold:true (attention à la syntaxe)
            /* [st-legend] légende du graph */
            'legend-position' => '', // in, none (defaut), top, bottom
            'legend-style' => '', // ex: color:'blue',fontSize:14,bold:true
            /* [st-specific] Paramètres spécifiques selon le type de graph */
            'vertical' => 0, // horizontal par défaut ou vertical. Tous sauf bar et bubble
            'bar-width' => '', // largeur des barres en %. Area, bubble, pie, scatter & stepped
            '3D' => 0, // camembert en relief. Tous sauf pie
            'donut' => '0', // part du trou central. ex: 0.5 pour la moitié. uniquement pour pie
            'isstacked' => '', // 0, true (absolute) ou relative. Tous sauf bubble, line, pie & scatter
            'options' => '', // les autres options proposées par google.chart (remplacer {} par [] dans la chaine JSON )
            /* [st-annexe]style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // controle existence et case
        $typeChart = UpHelper::ctrl_argument($this,$options[__class__], 'Area,Bar,Bubble,Column,Combo,Line,Pie,Scatter,SteppedArea');

        // ==
        // ==== MISE EN FORME DONNEES
        // ==
        // === Recuperation du contenu CSV
        $content = UpHelper::get_content_csv($this,$this->content);

        // === analyse et nombre de colonnes du tableau
        $nbCol = 0;
        $msgError = '';
        foreach ($content as $key => $val) {
            if (strpos($val, $options['separator']) !== false) {
                $tmp = str_getcsv($val, $options['separator'], '"', '\\');
                // vérification nombre colones identiques
                if ($nbCol == 0) {
                    $nbCol = count($tmp);
                } else {
                    if (count($tmp) != $nbCol) {
                        $msgError .= UpHelper::trad_keyword($this,'ERR_NB_COL', ($key + 1), count($tmp), $nbCol);
                    }
                }
                $rows[] = $tmp;
            }
        }
        if ($msgError) {
            return UpHelper::info_debug($this,$msgError);
        }
        $isNum = [];
        // === Analyse entete données pour structure données
        foreach ($rows as $key => $val) {
            unset($tmp);
            foreach ($val as $k1 => $v1) {
                $v1 = UpHelper::supertrim($this,$v1);
                if ($key == 0) {
                    // entete
                    if ($k1 == 0) {
                        $isNum[0] = false;
                        $tmp[] = '\'' . $v1 . '\'';
                    } elseif (strtolower($v1) == '##color##') {
                        $tmp[] = '{ role: \'style\' }';
                        $isNum[$k1] = false;
                    } elseif (strtolower($v1) == '##label##') {
                        $tmp[] = '{ role: \'annotation\' }';
                        $isNum[$k1] = false;
                    } else {
                        $isNum[$k1] = true;
                        $tmp[] = '\'' . $v1 . '\'';
                    }
                } else {
                    // les données
                    $tmp[] = ($isNum[$k1] || $typeChart == 'Scatter') ? $v1 : '\'' . $v1 . '\'';
                }
            }
            $out[] = '[' . implode(',', $tmp) . ']';
        }

        $data = implode(',', $out);

        // === JS CHART
        $id = str_replace('-', '_', $options['id']);
        $js = 'google.charts.setOnLoadCallback(' . $id . ');';
        $js .= 'function ' . $id . '() {';
        $js .= 'var data = new google.visualization.arrayToDataTable([';
        $js .= $data;
        $js .= ']);';
        $js .= 'var options = {';
        // --- Ctrl titre
        $js .= UpHelper::set_options($this,'title', $options['title']);
        $js .= UpHelper::set_options($this,'titlePosition', $options['title-position']);
        $js .= UpHelper::set_options($this,'titleTextStyle', $options['title-style']);
        // --- Ctrl legend
        $legend['position'] = UpHelper::ctrl_argument($this,$options['legend-position'], ',bottom,left,top,right,in,none', false);
        $legend['textStyle'] = $options['legend-style'];
        $js .= UpHelper::set_options($this,'legend', $legend);

        // $js .= 'chartArea:{left:20,top:0,width:\'70%\',height:\'85%\'},';
        if ($options['area']) {
            $tmp = explode(',', $options['area']) + array(
                '20',
                '20',
                '80',
                '80'
            );
            $js .= 'chartArea:{';
            $js .= 'left:\'' . UpHelper::supertrim($this,$tmp[0], "%'") . '%\',';
            $js .= 'top:\'' . UpHelper::supertrim($this,$tmp[1], "%'") . '%\',';
            $js .= 'width:\'' . UpHelper::supertrim($this,$tmp[2], "%'") . '%\',';
            $js .= 'height:\'' . UpHelper::supertrim($this,$tmp[3], "%'") . '%\',';
            $js .= '},';
        }
        if ($options['colors']) {
            $tmp = array_map('trim', explode(',', $options['colors']));
            $tmp = '\'' . implode('\',\'', $tmp) . '\'';
            $js .= 'colors:[' . $tmp . '],';
        }
        if ($options['maximized']) {
            $js .= 'theme:\'maximized\',';
        }
        if ($options['vertical']) {
            $js .= 'orientation:\'vertical\',';
        }
        if ($options['bar-width']) {
            $tmp = rtrim($options['bar-width'], ' %') . '%';
            $js .= 'bar:{groupWidth:\'' . $tmp . '\'},';
        }
        if ($options['3D']) {
            $js .= 'is3D:true,';
        }
        if ($options['donut']) {
            while ($options['donut'] > 1) {
                $options['donut'] = $options['donut'] / 10;
            }
            $js .= 'pieHole:' . $options['donut'] . ',';
        }
        if ($options['isstacked']) {
            $js .= ($options['isstacked'] == 1) ? 'isStacked:true,' : 'isStacked:\'relative\',';
        }
        if ($options['options']) {
            $tmp = str_replace(array(
                '[',
                ']'
            ), array(
                '{',
                '}'
            ), $options['options']);
            $tmp2 = str_replace(array(
                '\{',
                '\}'
            ), array(
                '[',
                ']'
            ), $tmp);
            $js .= rtrim($tmp2 . ',', ',');
        }
        $js .= '};';
        // Instantiate and draw the chart
        $js .= 'var chart = new google.visualization.' . $typeChart . 'Chart(document.getElementById(\'' . $id . '\'));';
        $js .= 'window.addEventListener("resize", drawChart);';
        $js .= 'drawChart();';

        $js .= 'function drawChart() {';
        $js .= '    chart.clearChart();';
        $js .= '    chart.draw(data, options);';
        $js .= '}';
        $js .= '}';
        UpHelper::load_js_code($this,$js);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $id;
        if ($options['height']) {
            $attr_main['style'] = 'min-height:' . UpHelper::ctrl_unit($this,$options['height']);
        }
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $attr_main, '');
        return implode(PHP_EOL, $html);
    }

    // run
}

// class
