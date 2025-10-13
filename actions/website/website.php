<?php

/**
 * Affiche un screenshot d'un site avec un lien.
 *
 * syntaxe {up website=URL}
 * par defaut, le texte affiche sous la vignette est l'URL sans http://
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Widget
 */
/*
 * v1.95 - bug sur lang
 * v2.1 - prise en compte v5 de l'api google
 * v2.2 - récupération par la méthode get_html_contents pour gestion timeout
 * - ajout options timeout=10 et renew=30 (0 pour jamais)
 * v2.5 - fix. suppr \ en fin nom, prise en charge query dans ur
 * v2.6 - ajout gwebsite-key, ignore erreur google si l'image a été créée
 * clé api à créer sur https://developers.google.com/speed/docs/insights/v5/get-started
 */
defined('_JEXEC') or die();

class website extends upAction
{
    public function init()
    {
        // charger les ressources communes a  toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // URL du site
            /* [st-view] Affichage du site */
            'link' => 1, // affiche le lien au-dessous du screenshot
            'link-text' => '', // texte affiche pour le lien et alt
            'target' => '_blank', // ou _self pour ouvrir le site dans le même onglet
            /* [st-new] Fréquence actualisation */
            'renew' => '30', // nombre de jours pour actualiser les vignettes. 0 = jamais (v2.2)
            'timeout' => '15', // delai pour recupérer les infos du serveur Google (v2.2)
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'style' => '', // classes et style inline pour bloc
            'class' => '', // classe(s) pour bloc (obsolete)
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head) v1.8
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $renew = intval($options['renew']);

        // === css-head
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        $options['apikey'] = $this->get_action_pref('gwebsite-key');

        // -- traitement
        $tmp = parse_url($options[__class__]);
        $siteURL = (empty($tmp['scheme'])) ? '//' : $tmp['scheme'] . '://';
        $siteURL .= $tmp['host'];
        $siteURL .= (empty($tmp['path'])) ? '' : $tmp['path'];
        $siteURL .= (empty($tmp['query'])) ? '' : '?' . $tmp['query'];
        $msgerr = '';
        if (! filter_var($siteURL, FILTER_VALIDATE_URL)) {
            $msgerr = $this->lang('en=not valid URL;fr=URL non valide');
        } else {
            // -- si besoin creation dossier cache
            $filePath = 'media/plg_content_up/website/';
            if (! is_dir(JPATH_ROOT . '/' . $filePath)) {
                mkdir(JPATH_ROOT . '/' . $filePath, 0755, true);
            }
            // -- nom fichier image (sans http)
            $siteName = $this->preg_string('#.*\:\/\/(.*)#', $siteURL);
            $siteFileName = str_replace('/', '_', $siteName) . '.png';
            $siteFileName = str_replace('?', '_', $siteFileName);
            $siteFileName = str_replace('&', '_', $siteFileName);
            $siteFilePath = $filePath . $siteFileName;
            // -- si screenshot obsolete, on le supprime
            if (file_exists($siteFilePath) && $renew != 0) {
                $age = (time() - filemtime($siteFilePath)) / 86400; // en jours
                if ($age > $renew) {
                    // une demande par heure uniquement
                    if (time() - $this->recentFileTime($filePath . '*.png') > 900) {
                        unlink($siteFilePath);
                    }
                }
            }
            // -- si pas de screenshot en cache
            if (! file_exists($siteFilePath)) {
                // call Google PageSpeed Insights API
                // modif 14-11-20 : appel méthode au lieu de file_get_contents
                $url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . $siteURL . '&screenshot=true';
                if ($options['apikey'] !== false) {
                    $url .= "&key=".$options['apikey'];
                }
                $resp = $this->get_html_contents($url, $options['timeout']);
                // dump($resp, $url);
                if ($resp != '') {
                    $resp = json_decode($resp, true);
                    $data = str_replace('_', '/', $resp['lighthouseResult']['audits']['final-screenshot']['details']['data']);
                    $data = str_replace('-', '+', $data);
                    $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
                    $imgname = JPATH_ROOT . '/' . $siteFilePath;
                    file_put_contents($imgname, $image);
                    //                     if (! empty($image)) // v31
                    //                         imagedestroy($image);
                }
            }
        }

        // -- mise en forme pour retour
        if ($msgerr == '') {
            // -- nom visible du site
            $siteName = ($options['link-text']) ? $options['link-text'] : $siteName;
            // -- code retour
            $out = $this->set_attr_tag('div', $attr_main);
            $out .= '<a href="' . $siteURL . '" target="' . $options['target'] . '">';
            $out .= '<img src="' . $siteFilePath . '" alt="' . $siteName . '">';
            if ($options['link']) {
                $out .= '<p>' . $siteName . '</p>';
            }
            $out .= '</a>';
            $out .= '</div>';
        } else {
            $out = $this->msg_inline($siteURL . ' : ' . $msgerr);
        }

        return $out;
    }

    // run

    /*
     * Retourne
     */
    private function recentFileTime($pattern)
    {
        $time = 0;
        foreach (glob($pattern) as $file) {
            if (filemtime($file) > $time) {
                $time = filemtime($file);
            }
        }
        return $time;
    }
}

// class
