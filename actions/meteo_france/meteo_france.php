<?php

/**
 * affiche le widget de Météo France
 *
 * Syntaxe :  {up meteo-france=ville | orientation=sens}
 *
 * le code commune de la ville à récupérer sur <a href="http://www.meteofrance.com/meteo-widget" target="_blank">http://www.meteofrance.com/meteo-widget</a>
 *
 * @author      LOMART
 * @version     UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Widget
 */
/*
 * https://public.opendatasoft.com/explore/dataset/correspondance-code-insee-code-postal/api/?flg=fr&q=75001
 * https://api.gouv.fr/api/api-geo.html#!/Communes/get_communes
 *
 */
/*
 * v1.8 - possibilité d'indiquer une ville non française
 */
/** TODO NOUVELLE VERSION
 *  Voir https://meteofrance.com/widgets
 *  {up html=iframe  | id=widget_autocomplete_preview |  width=450|  height=150 | frameborder=0 | src=https://meteofrance.com/widget/prevision/751150##009688BF}
 */
defined('_JEXEC') or die;

class meteo_france extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // sortie si pas de code commune
        if ($this->options_user[__class__] === true) {
            $txt = '<a href="https://www.meteofrance.com/meteo-widget" target="_blank">';
            $txt .= 'METEO: Récupérer le code de la commune ici';
            $txt .= '</a>';
            $this->msg_info($txt);
            return $txt;
        }

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
          __class__ => '', // le code commune de la ville à récupérer sur <a href="http://www.meteofrance.com/meteo-widget" target="_blank">http://www.meteofrance.com/meteo-widget</a>
          'orientation' => 'v', // bloc horizontal (H) ou vertical (V)
          'block' => 'p', // balise HTML autour du module météo
          /* [st-css] Style CSS*/
          'id' => '', // Identifiant
          'class' => '', // classe(s) pour bloc parent
          'style' => '' // style inline pour bloc parent
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // le bloc conteneur
        $main_attr['class'] = $options['class'];
        $main_attr['style'] = $options['style'];

        // récupération script meteo
        $ville = $options[__class__];
        $monde = (strlen($ville) < 6);
        $sens = strtolower($options['orientation'][0]);
        $sens = ($sens == 'v') ? 'PORTRAIT_VIGNETTE' : 'PAYSAGE_VIGNETTE';

        $url = 'https://www.meteofrance.com/mf3-rpc-portlet/rest/vignettepartenaire/';
        $url .= $ville;
        $url .= ($monde) ? '/type/VILLE_MONDE/size/' : '/type/VILLE_FRANCE/size/';
        $url .= $sens;
        $url2 = str_replace('https://', 'http://', $url);

        $meteo = $this->get_html_contents($url, 30, $url2);
        // ajout détection erreur de Pascal Leconte
        if (preg_match("/Erreur :/", $meteo)) { // erreur dans l'appel meteo France
            return $this->info_debug('M&eacute;t&eacute;o France: ' . $meteo);
        }
        $meteo = str_replace('<head>', '', $meteo);
        $meteo = str_replace('</head>', '', $meteo);
        $meteo = str_replace('http://logc279', 'https://logs', $meteo);
        $meteo = str_replace('http://', '//', $meteo);
        $meteo = str_replace('target="_blank"', 'target="_blank" rel="noopener noreferrer" ', $meteo);

        // code retour
        $out = $this->set_attr_tag($options['block'], $main_attr);
        $out .= '<script charset="UTF-8" type="text/javascript">';
        $out .= $meteo;
        $out .= '</script>';
        $out .= '</' . $options['block'] . '>';

        $out = '<iframe id="widget_autocomplete_preview"  width="150" height="300" frameborder="0" src="http://meteofrance.com/widget/prevision/770140##2C9E30CC"> </iframe>';
        return $out;
    }

// run
}

// class
