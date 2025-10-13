<?php

/**
 * Ajoute une image ou un texte sur une image
 *
 * syntaxe {up image-logo=prefset,image_logo ou texte}image{/up image-logo}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  image
 */
/*
 * v3.1 : bbcode sur argument principal
 */
defined('_JEXEC') or die();

class image_logo extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        // $this->load_file('xxxxx.css');
        // $this->load_file('xxxxx.js');
        return true;
    }

    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        // - 0 pour cacher l'action dans l'aide générale
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // prefset,image_logo ou texte
            'pos' => 'right,bottom', // position horizontale (left, gauche, right, droite, center, centre), verticale (top, haut, bottom, bas, center, centre)
            'width' => '', // largeur logo en px, rem, %. % par défaut
            /* [st-logo] Style pour le logo */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            /* [st-bloc] Style pour le bloc externe */
            'main-class' => '', // classe(s) pour le bloc du contenu
            'main-style' => '', // style inline pour le bloc du contenu
            /* [st-divers] Divers */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // == consolidation options
        // -- LOGO ou LIBELLE ?
        // -- est-ce une image ?
        $logo = $options[__class__];
        // if (is_file(JURI::root() . $logo)) {
        if (file_exists($logo)) {
            $img_attr['src'] = $logo;
            $img_attr['alt'] = $this->link_humanize($logo);
            $logo = $this->set_attr_tag('img', $img_attr);
        } else {
            $logo = $this->get_bbcode($logo); // v3.1
        }

        // -- contenu. supprime les paragraphes vides
        $content = $this->supertrim($this->content);
        $content = str_replace("<p>\xC2\xA0</p>", '', $content);
        $content = str_replace('<p> </p>', '', $content);
        $content = str_replace('<br />', '', $content);
        $content_1 = $this->supertrim($content);
        // -- contenu - supprime bloc p principal
        $content = $this->preg_string('#^<p>(.*)</p>$#', $content_1);
        if ($content == '')
            $content = $content_1;
        // -- Position logo
        $pos_ctrl = $this->strtoarray('left:l,gauche:l,right:r,droite:r,droit:r,center:c,centre:c,top:t,haut:t,bottom:b,bas:b', ',', ':', false);
        $sep = (strpos($options['pos'], ',') === false) ? '-' : ',';
        list ($pos1, $pos2) = array_map('trim', explode($sep, strtolower($options['pos'])));
        $pos = (isset($pos_ctrl[$pos1])) ? $pos_ctrl[$pos1] : '';
        $pos .= (isset($pos_ctrl[$pos2])) ? $pos_ctrl[$pos2] : '';
        if (strlen($pos) != 2)
            return $this->info_debug('error pos=' . $options['pos']);
        $css_translate = '';
        switch ($pos) {
            case 'lt':
            case 'tl':
                $css_posx = 'left:0';
                $css_posy = 'top:0';
                break;
            case 'lc':
            case 'cl':
                $css_posx = 'left:0';
                $css_posy = 'left:50%';
                $css_translate = 'transform:translate(-50%, 0)';
                break;
            case 'lb':
            case 'bl':
                $css_posx = 'left:0';
                $css_posy = 'bottom:0';
                break;
            case 'tc':
            case 'ct':
                $css_posx = 'left:50%';
                $css_posy = 'top:0';
                $css_translate = 'transform:translate(0,-50%)';
                break;
            case 'cc':
                $css_posx = 'left:50%';
                $css_posy = 'top:50%';
                $css_translate = 'transform:translate(-50%,-50%)';
                break;
            case 'bc':
            case 'cb':
                $css_posx = 'right:50%';
                $css_posy = 'bottom:0';
                $css_translate = 'transform:translate(0,-50%)';
                break;
            case 'rt':
            case 'tr':
                $css_posx = 'right:0';
                $css_posy = 'top:0';
                break;
            case 'rc':
            case 'cr':
                $css_posx = 'right:0';
                $css_posy = 'top:50%';
                $css_translate = 'transform:translate(-50%, 0)';
                break;
            case 'rb':
            case 'br':
                $css_posx = 'right:0';
                $css_posy = 'bottom:0';
                break;
        }
        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // attributs du bloc logo
        $attr_logo = array();
        $this->get_attr_style($attr_logo, $options['class'], $options['style']);
        $attr_logo['class'] .= ' display-inline-block';
        if ($options['width'])
            $this->add_style($attr_logo['style'], 'width', $this->ctrl_unit($options['width'], '%, px, em, rem'));
        $this->add_str($attr_logo['style'], 'z-index:10;
		position:absolute', ';
		');
        $this->add_str($attr_logo['style'], $css_posx, ';
		');
        $this->add_str($attr_logo['style'], $css_posy, ';
		');
        $this->add_str($attr_logo['style'], $css_translate, ';
		');

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['main-class'], $options['main-style']);
        $attr_main['class'] .= 'pos-relative display-inline-block';

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main);
        $html[] = $content;
        $html[] = $this->set_attr_tag('div', $attr_logo, $logo);
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
