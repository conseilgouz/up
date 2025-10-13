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

class chart extends upAction
{
    public function init()
    {
        $this->load_file('https://www.gstatic.com/charts/loader.js');
        // Load Charts and the corechart package.
        $this->load_js_code('google.charts.load(\'current\', {\'packages\':[\'corechart\']});');
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo
        $this->set_demopage();

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
        $options = $this->ctrl_options($options_def);

        // controle existence et case
        $typeChart = $this->ctrl_argument($options[__class__], 'Area,Bar,Bubble,Column,Combo,Line,Pie,Scatter,SteppedArea');

        // ==
        // ==== MISE EN FORME DONNEES
        // ==
        // === Recuperation du contenu CSV
        $content = $this->get_content_csv($this->content);

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
                        $msgError .= $this->trad_keyword('ERR_NB_COL', ($key + 1), count($tmp), $nbCol);
                    }
                }
                $rows[] = $tmp;
            }
        }
        if ($msgError) {
            return $this->info_debug($msgError);
        }
        $isNum = [];
        // === Analyse entete données pour structure données
        foreach ($rows as $key => $val) {
            unset($tmp);
            foreach ($val as $k1 => $v1) {
                $v1 = $this->supertrim($v1);
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
        $js .= $this->set_options('title', $options['title']);
        $js .= $this->set_options('titlePosition', $options['title-position']);
        $js .= $this->set_options('titleTextStyle', $options['title-style']);
        // --- Ctrl legend
        $legend['position'] = $this->ctrl_argument($options['legend-position'], ',bottom,left,top,right,in,none', false);
        $legend['textStyle'] = $options['legend-style'];
        $js .= $this->set_options('legend', $legend);

        // $js .= 'chartArea:{left:20,top:0,width:\'70%\',height:\'85%\'},';
        if ($options['area']) {
            $tmp = explode(',', $options['area']) + array(
                '20',
                '20',
                '80',
                '80'
            );
            $js .= 'chartArea:{';
            $js .= 'left:\'' . $this->supertrim($tmp[0], "%'") . '%\',';
            $js .= 'top:\'' . $this->supertrim($tmp[1], "%'") . '%\',';
            $js .= 'width:\'' . $this->supertrim($tmp[2], "%'") . '%\',';
            $js .= 'height:\'' . $this->supertrim($tmp[3], "%'") . '%\',';
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
        $this->load_js_code($js);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $id;
        if ($options['height']) {
            $attr_main['style'] = 'min-height:' . $this->ctrl_unit($options['height']);
        }
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main, '');
        return implode(PHP_EOL, $html);
    }

    // run
    public function set_options($option, $arg)
    {
        $out = '';
        if (is_array($arg)) {
            foreach ($arg as $key => $val) {
                if ($val) {
                    if (str_word_count($val, 0, '0123456789-_') == 1) {
                        $val = '"' . $val . '"';
                    } else {
                        $val = '{' . $val . '}';
                    }
                    $out .= ($out) ? ',' : '';
                    $out .= $key . ':' . $val;
                }
            }
            if ($out) {
                $out = $option . ':{' . $out . '},';
            }
        } else {
            if (trim($arg)) {
                if (strpos($arg, ':') === false) {
                    $arg = '"' . $arg . '"';
                } else {
                    $arg = '{' . $arg . '}';
                }
                $out = $option . ':' . $arg . ',';
            }
        }
        return $out;
    }
}

// class
