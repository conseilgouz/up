<?php

/**
 * Génére des QRCodes avec Google API
 *
 * syntaxe {up qrcode=type | xxx=...}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Widget
 */
defined('_JEXEC') or die();

class qrcode extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => 'text', // type de QRCode : text/url/sms/email/phone/location/wifi/contact
            /* [st-qrstyle] Style du QRCode */
            'size' => '200', // Largeur
            'margin' => '', // Espace autour de l'image QR
            'light' => '', // Code hexadécimal de la couleur de fond du QRcode. ex: FFFFFF = blanc
            'dark' => '', // Code couleur hexadécimal du QRcode. ex: 000000 = noir
            'centerImageUrl' => '', // URL absolue vers image au centre du qrcode
            'centerImageSizeRatio' => '', // ratio image / qrcode
            'centerImageWidth' => '', // Largeur de l'image centrale en pixels
            'centerImageHeight' => '', // Hauteur de l'image centrale en pixels
            /* [st-field] Les types de contenus */
            'name' => '', // nom pour contact
            'text' => '', // texte libre pour text, sms, email, contact
            'phone' => '', // numéro de téléphone pour sms, tel, contact
            'url' => '', // site Internet pour url, contact
            'email' => '', // adresse email pour email, contact
            'subject' => '', // sujet pour email
            'latitude' => '', // pour geo
            'longitude' => '', // pour geo
            'address' => '', // adresse pour contact
            'ssid' => '', // identifiant point d'accés pour wifi
            'auth' => 'WPA', // WPA ou WEP pour wifi
            'password' => '', // clé wifi du point d'accés pour wifi
            /* [st-divers] Divers */
            'alt' => '', // texte alternatif, si le qrcode n'est pas affiché
            'encoding' => 'UTF-8', // code pour texte OBSOLETE
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // -- consolidation
        if (strpos($options['url'], '//') === false) {
            $options['url'] = '//' . $options['url'];
        }

        // https://quickchart.io/documentation/qr-codes/
        $url = 'https://quickchart.io/qr?';
        $url .= 'size=' . $options['size'];
        $url .= (empty($options['margin'])) ? '' : '&margin='.$options['margin'];
        $url .= (empty($options['light'])) ? '' : '&light='.$options['light'];
        $url .= (empty($options['dark'])) ? '' : '&dark='.$options['dark'];
        $url .= (empty($options['centerImageUrl'])) ? '' : '&centerImageUrl='.$options['centerImageUrl'];
        $url .= (empty($options['centerImageSizeRatio'])) ? '' : '&centerImageSizeRatio='.$options['centerImageSizeRatio'];
        $url .= (empty($options['centerImageWidth'])) ? '' : '&centerImageWidth='.$options['centerImageWidth'];
        $url .= (empty($options['centerImageHeight'])) ? '' : '&centerImageHeight='.$options['centerImageHeight'];
        $url .= '&text=';
        switch ($options[__class__]) {
            case 'url':
                $url .= 'URL:' . $options['url'];
                break;
            case 'sms':
                $url .= 'SMSTO:' . $options['phone'];
                $url .= ':' . $options['text'];
                break;
            case 'email':
                $url .= 'MATMSG:TO:' . $options['email'];
                $url .= ';SUB:' . $options['subject'];
                $url .= ';BODY:' . $options['text'];
                break;
            case 'phone':
                $url .= 'TEL:' . $options['phone'];
                break;
            case 'geo':
                $url .= 'GEO:' . $options['latitude'] . ',' . $options['longitude'];
                break;
            case 'wifi':
                $url .= 'WIFI:S:' . $options['ssid'];
                $url .= ';T:' . $options['auth']; // WPA/WEP
                $url .= ';P:' . $options['password'];
                break;
            case 'contact':
                $url .= 'MECARD:N:' . $options['name'];
                $url .= ';TEL:' . $options['phone'];
                $url .= ';URL:' . $options['url'];
                $url .= ';EMAIL:' . $options['email'];
                $url .= ';ADR:' . $options['address'];
                $url .= ';NOTE:' . $options['text'];
                break;
            case 'text':
            default:
                $url .= $options['text'];
                break;
        }
        // === css-head
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);
        $attr_main['src'] = str_replace(' ', '+', $url);
        $attr_main['alt'] = $options['alt'];

        // code en retour
        $html[] = $this->set_attr_tag('img', $attr_main, true);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
