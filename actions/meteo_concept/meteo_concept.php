<?php

/**
 * Retourne les prévisions météo pour à date donnée pout une ville FRANCAISE donnée
 * 
 * Basée sur l'API de Météo Concept permettant aux développeurs de site internet ou d'applications d'intégrer des données météorologiques de prévisions ou d'observations pour un lieu ou une station météo souhaitée.
 * Pour commencer à l'utiliser l'API, il est nécessaire de s'inscrire sur le site internet de Météo Concept. La formule gratuite permet de faire jusqu'à 500 requêtes par jour.
 * Cette inscription fourni une authentification et une autorisation grâce à une clé d'API, appelé communément « token ».
 * 
 * syntaxe 1 
 * {up meteo=Date ou dateheure de la prévision |insee=code |token=Token fourni par Météo Concept } 
 * syntaxe 2 
 * {up meteo=Date ou dateheure de la prévision |insee=code |token=Token fourni par Météo Concept } 
 *   modèle de texte avec mots-clés
 * {/up meteo}
 * trouver un code insee : https://www.insee.fr/fr/recherche/recherche-geographique
 * 
 * ---- MOTS CLES 
 * ##insee##            Code Insee de la commune
 * ##cp##               Code postal de la commune
 * ##name##             Nom de la commune
 * ##latitude##         Latitude décimale de la commune
 * ##longitude##        Longitude décimale de la commune
 * ##altitude##         Altitude de la commune en mètres
 * ##dirwind10m## ou ##winddirs##   Direction du vent 
 * ##gust10m##          Rafales de vent à 10 mètres en km/h
 * ##gustx##            Rafale de vent potentielle sous orage ou grain en km/h
 * ##probafog##         Probabilité de brouillard entre 0 et 100%
 * ##probafrost##       Probabilité de gel entre 0 et 100%
 * ##rainprob## ou ##probarain##    Probabilité de pluie entre 0 et 100%
 * ##probawind100##     Probabilité de vent >70 km/h entre 0 et 100%
 * ##probawind70##      Probabilité de vent >100 km/h entre 0 et 100%
 * ##rainmax## ou ##rr1##   Cumul de pluie maximal sur la journée en mm
 * ##rain## ou ##rr10## Cumul de pluie sur la journée en mm
 * ##weather-text##     Resenti météo sous forme de texte 
 * ##weather##          Resenti météo index (nombre de 0 à 235)
 * ##windspeed## ou ##wind10m##   Vent moyen à 10 mètres en km/h
 * ---- QUART DE JOURNEE UNIQUEMENT
 * ##temp## ou ##temp2m##      Température à 2 mètres en °C
 * ---- JOURNEE ENTIERE UNIQUEMENT
 * ##temp-max## ou ##tmax##    Température maximale à 2 mètres en °C
 * ##temp-min## ou ##tmin##    Température minimale à 2 mètres en °C
 * ##sun_hours##   Ensoleillement en heures
 * ##etp##              Cumul d'évapotranspiration en mm
 *
 * @author  Denis & lomart
 * @version  UP-2.9 
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Widget
 *
 */
 /*
 v31 - fix message si hors période
 v5.3.3 : php 8.4/8.5 compatibility
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class meteo_concept extends Lomart\Plugin\Content\Up\Extension\Up
{
    public $synonym;
    public $wcity,$wdata ;
    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo
        UpHelper::set_demopage($this);
        $options_def = array(

            __class__ => '', // Date de la prévision sous la forme AAAAMMJJ (journée) ou AAAAMMJJHHMM (quart de journée début à 1,7,13 ou 19h)
            'insee' => '', // Localisation par code insee de la commune (mode prioritaire)
            /* [st-out] Si la date n'est pas dans les 14 jours à venir */
            'msg' => 0, // 1 pour afficher un message si hors période de 14 jours
            'msg-before' => 'L\'événement est terminé depuis le %s', // message si date antérieure
            'msg-after' => 'Prévisions disponibles à partir du %s', // message si date postérieur
            /* [st-css] CSS pour bloc principal */
            'tag' => 'div', // balise utilisée pour le bloc principal si un style est indiqué.
            'style' => '', // classe et style pour le bloc principal.
            /* [st-expert] Paramètres webmaster */
            'date-format' => '%A %e %B %Y', // format de la date
            'cache-delay' => 60, // durée du cache en minutes. 0 pas de cache
            'token' => '' // Token fourni par Météo Concept
        );
        // === fusion et controle des options

        $options = UpHelper::ctrl_options($this,$options_def);
        // === les styles
        $attr_main = array();
        UpHelper::get_attr_style($this,$attr_main, $options['style']);

        // === le code INSEE est obligatoire pour éviter d'avoir Rennes par défaut
        if (strlen($options['insee']) < 4)
            return UpHelper::info_debug($this,UpHelper::trad_keyword($this,'error_insee'));

        // === Analyse et chargement des paramètres
        $lib_daypart = UpHelper::params_decode($this,UpHelper::trad_keyword($this,'DAY-PART'));
        $lib_weather = UpHelper::params_decode($this,UpHelper::trad_keyword($this,'WEATHER'));
        $lib_winddirs = UpHelper::params_decode($this,UpHelper::trad_keyword($this,'WINDDIRS'));
        $this->synonym = UpHelper::params_decode($this,UpHelper::trad_keyword($this,'SYNONYM'));

        // === Traitement de la date de prévisions souhaitée (argument meteo-concept)
        // vide : date du jour si out-of-period=2
        // AAAAMMJJ ou AAAA-MM-JJ : jour all (8 ou 10)
        // AAAAMMJJHHMM ou AAAA-MM-JJ HH:MM : jour partiel (12 ou 16)
        $target = str_replace(array(
            ' ',
            '-',
            '/',
            '\\',
            ':',
            '*',
            '|',
            '>',
            '<',
            'h'
        ), '', $options[__class__]);
        // today pour la date du jour
        if ($target == 'today')
            $target = date('Ymd'); // aujourd'hui

        // date non saisie
        if ((int) $target == 0)
            return UpHelper::info_debug($this,UpHelper::trad_keyword($this,'error_empty_date'));

        // traiter la date
        if (strlen($target) >= 12) {
            $hour = date('H', strtotime($target));
            $daypart = intval($hour / 6);
        } elseif (strlen($target) >= 8) {
            $daypart = 'all';
        }
        // calcul de la différence entre aujourd'hui et la date des prévisions (v3.1)
        $date = date_create($target);
        $now = date_create(date("Y-m-d"));
        $interval = date_diff($now, $date);
        $date_offset = $interval->format('%r%a');
        // On ne va s'occuper que de la différence en jours
        if ($date_offset < 0 || $date_offset > 13) {
            if ($options['msg'] == 1) {
                $msg = ($date_offset < 0) ? $options['msg-before'] : $options['msg-after'];
                if ($date_offset > 13)
                    $target = date("Y-m-d",strtotime('-13 day',strtotime($target)));
                $msg = sprintf($msg, UpHelper::up_date_format($this,$target, $options['date-format']));
                return UpHelper::set_attr_tag($this,'_' . $options['tag'], $attr_main, $msg);
            } else {
                return '';
            }
        }
        
        // === construction de l'url d'appel de l'API de Météo Concept
        $url = 'https://api.meteo-concept.com/api/forecast/daily';

        if ($daypart === 'all') {
            $url = $url . '?token=' . $options["token"] . '&insee=' . $options["insee"];
        } else {
            $url = $url . '/' . $date_offset . '/periods?token=' . $options["token"] . '&insee=' . $options["insee"];
        }

        // --- Le modèle de mise en page est le contenu entre shortcode
        // ou à défaut le texte
        if ($this->content) {
            $template = $this->content;
        } else {
            $template = ($daypart == 'all') ? UpHelper::trad_keyword($this,'MSG-ALL') : UpHelper::trad_keyword($this,'MSG-PART');
        }
        $this->out = UpHelper::get_bbcode($this,$template);

        // Récupération des données
        $filetemp = 'tmp/meteo-concept-' . $options['insee'] . '-' . $target . '.json';
        // $filedate = date('Y-m-d H:i', filemtime($filetemp));
        // $now = date('Y-m-d H:i', strtotime('-' . $options['cache-delay'] . 'minutes'));
        if (file_exists($filetemp) && (filemtime($filetemp) < strtotime('-' . $options['cache-delay'] . 'minutes')) === false) {
            $curl = false;
            $data = file_get_contents($url); // Méthode en n'utilisant pas la methode curl
        } else {
            $curl = true;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pour permettre l'utilisation du https
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            if ($data == false)
                return UpHelper::info_debug($this,curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
            file_put_contents($filetemp, $data);
        }

        // === traitement des données
        $decoded = json_decode($data);
        // print_r($decoded);
        if (isset($decoded->message)) {
            $this->out = UpHelper::info_debug($this,$decoded->message);
        } else {
            $this->wcity = $decoded->city;
            if ($daypart == 'all') {
                $this->wdata = $decoded->forecast[$date_offset];
            } else {
                $this->wdata = $decoded->forecast[$daypart];
            }

            // --- données de base ou avec mise en forme
            $this->kwmeteo_replace('DAY-PART', $lib_daypart[$daypart]);
            $this->kwmeteo_replace('DATE', UpHelper::up_date_format($this,$this->wdata->datetime, $options['date-format']));
            $this->kwmeteo_replace('UPDATE', UpHelper::up_date_format($this,$decoded->update, $options['date-format']));
            $this->kwmeteo_replace('WINDDIRS', $lib_winddirs[(int) floor(($this->wdata->dirwind10m + 11.25) / 22.5)]);
            $this->kwmeteo_replace('WEATHER-TEXT', $lib_weather[$this->wdata->weather]);

            // --- autres données
            $keywords = array();
            if (preg_match_all('#\#\#(.*)\#\##U', $this->out, $keywords)) {
                foreach ($keywords[1] as $kw) {
                    $this->kwmeteo_replace($kw);
                }
            }
        }

        // -- c'est fini
        return UpHelper::set_attr_tag($this,'_' . $options['tag'], $attr_main, $this->out);
        
    }

    // run

    /*
     * Remplace le mot-clé $kw par sa valeur ($value) dans le modele ($this->out)
     */
    function kwmeteo_replace($kw, $value = NULL)
    {
        $kw_value = strtolower($kw);
        if (isset($this->synonym[$kw_value]))
            $kw_value = $this->synonym[$kw_value];
        if (is_null($value)) {
            if (isset($this->wdata->{$kw_value})) {
                $value = $this->wdata->{$kw_value}; // données meteo
            } elseif (isset($this->wcity->{$kw_value})) {
                $value = $this->wcity->{$kw_value}; // données de city
            } else {
                $value = '<span style="color:red">##' . $kw . '##</span>';
            }
        }
        $this->out = str_ireplace('##' . $kw . '##', $value, $this->out);
    }
} // class


