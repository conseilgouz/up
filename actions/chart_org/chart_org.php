<?php

/**
 * Affiche le graphe d'une organisation
 *
 * syntaxe {up chart-org} liste UL / OL {/up chart-org}
 *
 * @version  UP-2.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   Lomart
 * @credit    <a href="https://www.cssscript.com/responsive-hierarchical-organization-chart-pure-css" target"_blank">script Responsive Hierarchical Organization Chart In Pure CSS de erinesullivan</a>
 * @tags     Widget
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class chart_org extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'chart_org.css');
        return true;
    }

    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        // - 0 pour cacher l'action dans l'aide générale
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // aucun argument
            'line-color' => '', // couleur liaisons entre bloc
            'color-bg' => '', // couleur arrière-plan par défaut des blocs
            'color-text' => '', // couleur par défaut des textes
            'border' => '', // bordure (outline) par défaut des blocs. ex: 1px red solid
            'radius' => '', // arrondi des blocs. border doit être none
            /* [st-child] style des blocs selon le niveau */
            'color-bg-*' => '', // couleur des blocs de niveau 1 à 6
            'color-text-*' => '', // couleur des blocs de niveau 1 à 6
            'border-*' => '', // bordure (outline) des blocs de niveau 1 à 6. ex: 1px red solid
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // === CSS
        $css = '';
        if ($options['line-color'])
            $css .= 'ol#id ol:before,ol#id ol:after,ol#id li:before,ol#id li:after,ol#id > li > div:before,ol#id > li > div:after {background-color: ' . $options['line-color'] . ';}';
        if ($options['color-bg'])
            $css .= 'ol#id li > div {background-color:' . $options['color-bg'] . '}';
        if ($options['color-text'])
            $css .= 'ol#id li > div {color:' . $options['color-text'] . '}';
        if ($options['border'])
            $css .= 'ol#id li > div {outline:' . $options['border'] . '}';
        if ($options['radius'] != '')
            $css .= 'ol#id li > div {border-radius:' . $options['radius'] . '}';
        for ($i = 1; $i <= 6; $i ++) {
            $sniv = str_repeat('li > ol > ', $i - 1);
            if ($options['color-bg-' . $i])
                $css .= 'ol#id > ' . $sniv . 'li > div {background:' . $options['color-bg-' . $i] . '}';
            if ($options['color-text-' . $i])
                $css .= 'ol#id > ' . $sniv . 'li > div {color:' . $options['color-text-' . $i] . '}';
            if ($options['border-' . $i])
                $css .= 'ol#id > ' . $sniv . 'li > div {outline:' . $options['border-' . $i] . '}';
        }
        UpHelper::load_css_head($this,$css);

        // Ajout bloc dans contenu
        $list = str_replace('<ul>', '<ol>', $this->content);
        $list = str_replace('</ul>', '</ol>', $list);
        // $list = preg_replace('#<li>#i', '<li><div>', $list);
        $list = preg_replace('#<li(.*)>#Ui', '<li$1><div>', $list);
        $list = preg_replace('#</li>#i', '</div></li>', $list);
        $list = preg_replace('#<ol>#i', '</div><ol>', $list);
        $list = preg_replace('#^\s*</div>\s*<ol>#is', '<ol id="' . $options['id'] . '" class="chart-org">', $list);
        // file_put_contents('test.html', '============== remplacements simples');
        // file_put_contents('test.html', $list);
        $list = preg_replace('#</ol>\s*</div>\s*</li>#is', '</ol></li>', $list);
        // file_put_contents('test.html', '============== remplacements </ol>\s*</div>\s*</li>');
        // file_put_contents('test.html', $list);
        $this->content = $list;

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $attr_main, $this->content);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
