<?php

/**
 * Genere des codes a barres
 *
 * syntaxe {up barcode=<text> | format=... [|format= html/svg/png]}
 *
 * type : C39, C39+, C39E, C39E+, C93, S25, S25+, I25, C128, C128A, C128B,
 *        EAN2, EAN5, EAN8, EAN13, UCPA, UPCE, MSI, MSI+,POSTNET,PLANET,RMS4CC,
 *        KIX, IMB, IMBPRE, CODABOR, CODE11, PHARMA, PHARMA2T
 *
 * @author   ConseilGouz
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags      Widget
 * */
/*
 * format : format de sortie html/svg/png
 * remarque : png ne fonctionne que si GD ou IMAGICK actif.
 * De plus, la couleur est au format RGB, par exemple 0,0,0 = noir
 *
 * https://tcpdf.org/
 *
 */
defined('_JEXEC') or die();

class barcode extends upAction
{

    function init()
    {
        return true;
    }

    function run()
    {
        include_once ('tcpdf_barcodes_1d.php');

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => 'text', // valeur alphanumérique du code code barre
            'type' => 'EAN13', // type de code a barres
            'format' => 'html', // format de sortie
            'height' => '30', // hauteur du code barre
            'width' => '1', // espacement du code barre
            'color' => '#000', // couleur impérativement sous la forme '#rrggbb'
            'align' => 'center', // alignement code barres et texte (left, center, right)
            'showtext' => '1', // montrer le texte ayant permis de générer le code barres
            /* styles */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // regles CSS definies par le webmaster (ajout dans le head)
        );
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // controle type barcode
        $typeList = ',EAN13,EAN2,EAN5,EAN8,EAN13,C39,C39+,C39E,C39E+,C93,S25,S25+,I25,C128,C128A,C128B';
        $typeList .= ',UCPA,UPCE,MSI,MSI+,POSTNET,PLANET,RMS4CC,KIX,IMB,IMBPRE,CODABOR,CODE11,PHARMA,PHARMA2T';
        $type = $this->ctrl_argument($options['type'], $typeList, false);
        if ($type == '')
            return $this->msg_inline($this->trad_keyword('ERR_TYPE', $options['type']));
        if (empty($options['color']))
            $options['color'] = '#000';
        // === base-css
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);
        $barcode = new TCPDFBarcode($options['barcode'], $options['type']);

        $text = $options['showtext'] ? $options['barcode'] : '';
        // code en retour
        switch ($options['format']) {
            case 'html':
                $content = $barcode->getBarcodeHTML($options['width'], $options['height'], $options['color'], $options['align']);
                if ($text != '') {
                    $content .= $text;
                }
                break;
            case 'svg':
                $content = $barcode->getBarcodeSVG($options['width'], $options['height'], $options['color']);
                if ($text != '') {
                    $content .= '<br>' . $text;
                }
                break;
            case 'png':
                $color = sscanf($options['color'], "#%02x%02x%02x");
                $content = $barcode->getBarcodePNG($options['width'], $options['height'], $color);
                if ($text != '') {
                    $content .= '<br>' . $text;
                }
                break;
            default:
                $content = $this->msg_inline($this->trad_keyword('ERR_FORMAT', $options['format']));
                break;
        }
        $content = '<div style="text-align:' . $options["align"] . '">' . $content . '</div>';
        return $this->set_attr_tag('div', $attr_main, $content);
    }

    // run
}

// class
