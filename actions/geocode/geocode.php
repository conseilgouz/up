<?php

/**
 * retourne les coordonnées GPS d'une adresse postale
 *
 * syntaxe {up geocode=adresse postale}
 * mots-clé pour template : ##latitude##, ##longitude##, ##adresse##
 *
 * @version  UP-502
 * @author	 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Groupe pour bouton editeur
 *
 */
/*
 v5.3.3 : php 8.5 compatibility
 */
 
defined('_JEXEC') or die;

class geocode extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {


        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // adresse postale
            'template' => "##latitude##,##longitude##", // mise en forme pour retour
            /*[st-user-agent] mentions obligatoire pour utiliser l'API */
            'site' => '', // url du site. Si vide, on utilise celle du serveur
            'email' => '', // adresse email du site. Si vide, on utilise 'webmaster@tld_site''
            'ssl' => '' // état SSL. permet de forcer l'état. Vide, on récupère la config serveur
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $address = $options[__class__];
        $address = $this->spaceNormalize($address);
        $address = preg_replace('/\s+/', ' ', $address); // on supprime les espaces en trop
        $address = urlencode($address);
        $file = 'tmp/up-geocode/'. str_replace('%2C', '_', $address) . '.json';

        if (file_exists($file)) {
            // Si le fichier existe, on récupère les données
            $response = file_get_contents($file);
        } else {
            $ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            $site = ($ssl) ? 'https://' : 'http://';
            $site .= $_SERVER['HTTP_HOST'];
            $email = ($options['email']) ? $options['email'] : 'webmaster@' . $_SERVER['HTTP_HOST'];

            // URL de l'API Nominatim
            $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . $address;

            // Initialiser et coonfigurer une session cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "$site ( $email )");

            // Désactiver la vérification SSL (à ne pas utiliser en production)
            if ($ssl === false || $options['ssl'] == '0') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }

            // Exécuter la requête et obtenir la réponse
            $response = curl_exec($ch);

            // on sauve la réponse dans un fichier cache
            if (!empty($response)) {
                if (!file_exists('tmp/up-geocode')) {
                    mkdir('tmp/up-geocode', 0777, true);
                }
                file_put_contents($file, $response);
            }
        }

        // Décoder la réponse JSON
        $responseData = json_decode($response, true);

        // Vérifier si la requête a réussi et qu'il y a des résultats
        if (!empty($responseData)) {
            $out = $options['template'];
            $out = str_replace('##latitude##', $responseData[0]['lat'], $out);
            $out = str_replace('##longitude##', $responseData[0]['lon'], $out);
            $out = str_replace('##adresse##', $responseData[0]['display_name'], $out);
        } else {
            $out = $this->msg_inline('error, adresse introuvable : '. $options[__class__]);
        }

        // code en retour
        return $out;
    }

    // run
}

// class
