<?php

/**
 * Affiche un texte sur ruban dans un angle
 *
 * syntaxe 1 (body) : {up corner=texte}
 * syntaxe 2 (bloc) : {up corner=texte}contenu du bloc{/up corner}
 *
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Body
 *
 */

/*
 * v1.63 - ajout option filter, suppression datemin et datemax
 */

defined('_JEXEC') or die;

class corner extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
          __class__ => '', // texte affiché dans le coin ou ruban
          /* [st-style] position, taille et style du corner */
          'position' => 'top-left', // angle sous la forme top-left ou tl
          'width' => '100px', // coté du carré
          'height' => '100px', // coté du carré
          'angle' => '45', // angle en valeur absolue
          'shadow' => '0', // ajoute une ombre 'orientée' au corner. La valeur indiquée est la force de l'ombre
          'color' => '#ffffff', // couleur du texte
          'bgcolor' => '#ff0000', // couleur de fond du coin
          'style' => '', // styles CSS (non proposés ci-dessus) pour le coin
          'class' => '', // idem style
          /* [st-link] si le corner est clicable */
          'url' => '', // lien
          'target' => '_blank', //  ou _self pour ouvrir le site dans le même onglet
          /* [st-bloc] style du bloc si le shortcode a un contenu */
           'bloc-class' => '', // classe(s) pour bloc
          'bloc-style' => '', // style inline pour bloc
          /* [st-annexe] style et options secondaire */
          'id' => '', // identifiant
          'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
          'filter' => '', // chaine de conditions. Voir documentation filter
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options[__class__] = $this->get_bbcode($options[__class__], false);
        $options['width'] = $this->ctrl_unit($options['width']);
        $options['height'] = $this->ctrl_unit($options['height']);

        // === affichage limité dans le temps  ?
        $corner_view = ($this->filter_ok($options['filter']) === true);
        // === affiché dans un bloc ou sur la page
        // sur la page si pas de contenu
        $onBody = ($this->content == '');

        // === css-head
        $this->load_css_head($options['css-head']);

        // === URL
        if ($options['url']) {
            //$attr_url['title'] = $options['url'];
            $attr_url['href'] = $options['url'];
            $attr_url['target'] = ($options['target'] != '_blank') ? '_self' : '_blank';
        }

        // === calcul position
        $w = (int) $options['width'];
        $h = (int) $options['height'];
        $ang = (int) $options['angle'];
        // angle 45°
        //		$voffset = round((($h / 2) + (($h - $w) / (2 * sqrt(2)))) * -1, 1);
        //		$hoffset = round((($w / 2) + (($h - $w) / (2 * sqrt(2)))) * -1, 1);
        // angle libre
        $angrad = deg2rad($ang);
        $voffset = round((-$h / 2) * (1 + cos($angrad)) + ($w / 2) * sin($angrad), 1);
        $hoffset = round((-$w / 2) * (1 - cos($angrad)) - ($h / 2) * sin($angrad), 1);

        // === CSS placement
        $saisie = array('left', 'gauche', 'right', 'droite', 'droit', 'top', 'haut', 'h', 'g', 'bottom', 'bas', '-', 'hg', 'hd', 'bg', 'bd');
        $keypos = array('l', 'l', 'r', 'r', 'r', 't', 't', 't', 'l', 'b', 'b', '', 'tl', 'tr', 'bl', 'br');
        $position = str_replace($saisie, $keypos, strtolower($options['position']));

        switch ($position) {
            case 'tl':
                $style[] = 'top:' . $voffset . 'px';
                $style[] = 'left:' . $hoffset . 'px';
                $style[] = 'align-items: flex-end';
                $ombre = '%3px %3px %3px -%3px ';
                $rotation = '-' . $ang . 'deg';
                break;
            case 'tr':
                $style[] = 'top:' . $voffset . 'px';
                $style[] = 'right:' . $hoffset . 'px';
                $style[] = 'align-items: flex-end';
                $ombre = '%1px %3px %3px -%3px ';
                $rotation = $ang . 'deg';
                break;
            case 'bl':
                $style[] = 'bottom:' . $voffset . 'px';
                $style[] = 'left:' . $hoffset . 'px';
                $style[] = 'align-items: flex-start';
                $ombre = '%3px -%3px %3px -%3px ';
                $rotation = $ang . 'deg';
                break;
            case 'br':
                $style[] = 'bottom:' . $voffset . 'px';
                $style[] = 'right:' . $hoffset . 'px';
                $style[] = 'align-items: flex-start';
                $ombre = '%1px -%3px %3px -%3px ';
                $rotation = '-' . $ang . 'deg';
                break;
        }

        // === Padding selon rotation
        $ang = strval($ang);
        if ($ang != 45) {
            if ($ang > 45 && $ang <= 80) {
                $offset_padding = floor(($ang - 45) / 35 * ($w * .8));
                $pp = ($position == 'tl' || $position == 'bl') ? 'left' : 'right';
                $style[] = 'padding-' . $pp . ':' . $offset_padding . 'px';
            } elseif ($ang > 10 && $ang < 45) {
                $offset_padding = floor(($ang - 10) / 35 * ($w * .8));
                $pp = ($position == 'tr' || $position == 'br') ? 'left' : 'right';
                $style[] = 'padding-' . $pp . ':' . $offset_padding . 'px';
            } else {
                $this->msg_error($this->trad_keyword('ERR_ANGLE'));
            }
        }

        // === style particulier si on BODY
        $style[] = ($onBody) ? 'position:fixed' : 'position: absolute';
        $style[] = ($onBody) ? 'z-index:99999' : 'z-index:99';

        // === style generaux sur bloc
        $style[] = 'width:' . $options['width'];
        $style[] = 'height:' . $options['height'];
        $style[] = 'display:flex';
        $style[] = 'justify-content: center';
        $style[] = 'transform: rotate(' . $rotation . ')';
        $style[] = '-webkit-transform: rotate(' . $rotation . ')';
        if ($options['shadow']) {
            $val = strval($options['shadow']);
            $ombre = str_replace('%1', $val, $ombre);
            $ombre = str_replace('%3', ($val * 3), $ombre) . ' #737373';
            $style[] = '-webkit-box-shadow:' . $ombre;
            $style[] = 'box-shadow:' . $ombre;
        }
        // === style du texte
        $style[] = 'color:' . $options['color'];
        $style[] = 'background:' . $options['bgcolor'];
        $style[] = 'text-align:center';
        $style[] = 'font-weight:bolder';
        $style[] = 'line-height:20px';
        $style[] = 'letter-spacing: 1px';

        $this->get_attr_style($attr_corner, implode(';', $style), $options['style']);
        $attr_corner['class'] = $options['class'];

        // === attributs du bloc principal
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['bloc-class'];
        $this->get_attr_style($attr_main, 'position:relative;overflow: hidden;', $options['bloc-style']);

        // === code en retour
        // ==================
        if ($onBody && $corner_view) {
            // ajout classes à BODY
            if ($attr_main['class']) {
                $this->load_jquery_code('$("body").addClass("' . $attr_main['class'] . '");');
            }
            // charge le style pour le corner dans HEAD
            $css = '.' . $options['id'] . '{' . $attr_corner['style'] . '}';
            $this->load_css_head($css);
            // Le code HTML pour le corner est mis au debut de BODY
            $code = '<div class=\'' . $options['id'] . '\'>' . $options[__class__] . '</div>';
            if (isset($attr_url)) {
                $code = $this->set_attr_tag('a', $attr_url, $code, false);
            }
            $html[] = $this->load_jquery_code('$("' . $code . '").prependTo("body");', false);
        } else {
            $html[] = $this->set_attr_tag('div', $attr_main);
            if ($corner_view) {
                $code = $this->set_attr_tag('div', $attr_corner, '<span>' . $options[__class__] . '</span>');
                if (isset($attr_url)) {
                    $code = $this->set_attr_tag('a', $attr_url, $code);
                }
                $html[] = $code;
            }
            $html[] = $this->content;
            $html[] = '</div>';
        }
        return implode(PHP_EOL, $html);
    }

// run
}

// class










