<?php

/**
 * affiche du contenu avec des animations sur scroll de page (librarie en pure JS)
 *
 * syntaxe 1 : anime le contenu du shortcode
 * {up anim-aos} votre texte {/up anim-aos}
 * syntaxe 2 : anime les tags indiqués dans le contenu
 * {up anim-aos | repeat=liste tags} contenu avec les tags cibles {/up anim-aos}
 * syntaxe 3 : anime tous les tags indiqués à partir de la position du shortcode jusqu'à la fin de l'article
 * {up anim-aos | repeat=liste tags}
 *
 *
 * @author    Conseilgouz
 * @version   UP-1.6.3
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    Script de <a href="https://michalsnik.github.io/aos/" target="_blank">michalsnik</a>
 * @tags      Layout-dynamic
 */
/*
 * animation :
 * fade-up, fade-down, fade-right, fade-left, fade-up-right, fade-up-left, fade-down-right, fade-down-left,
 * flip-left, flip-right, flip-up, flip-down,
 * zoom-in, zoom-in-up, zoom-in-down, zoom-in-left, zoom-in-right, zoom-out, zoom-out-up, zoom-out-down, zoom-out-right, zoom-out-left
 *
 * easing :
 * linear, ease, ease-in, ease-out, ease-in-out, ease-in-back, ease-out-back, ease-in-out-back, ease-in-sine,
 * ease-out-sine, ease-in-out-sine, ease-in-quad, ease-out-quad, ease-in-out-quad, ease-in-cubic, ease-out-cubic,
 * ease-in-out-cubic, ease-in-quart, ease-out-quart, ease-in-out-quart
 *
 * anchor-placement:
 * top-bottom, center-bottom, bottom-bottom, top-center, bottom-center, center-center, top-top, bottom-top
 */

/*
 * v1.7  - ajout option once
 * v1.72 - fix UTF lors prise en charge globale de la page
 * v2.11 - suppression test chargement XML
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class anim_aos extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('aos.css');
        $this->load_file('aos.js');
        $this->load_file('init_aos.js');
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => 'zoom-in-right', // nom de l'animation
            'delay' => '', // délai en millisecondes (50-3000 ms)
            'duration' => '', // durée animation en millisecondes (50-3000 ms)
            'easing' => '', // effet: linear, ease-in-back, ease-out-cubic, ease-in-sine
            'offset' => '', // en px, hauteur pour déclenchement par rapport au bas de l'écran.
            'anchor-placement' => '', // déclenche l'effet lorsque le scrolling de l'élément arrive à certaines positions
            'once' => '', // one-time effect or not
            'repeat' => '', // applique l'effet à tous les tags. Ex: h2,h3
            'css-before' => '', // style de l'élément avant l'animation
            'css-after' => '', // style de l'élément après l'animation
            'id' => '', // identifiant
            'style' => '', // style inline appliqué au bloc
            'class' => '', // classe(s) appliquée(s) au bloc
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ==== controle type de syntaxe shortcode
        if ($options['repeat'] == '') {
            if (!$this->ctrl_content_exists()) {
                return false;
            }
        }
        // === Effet sur mesure
        if ($options['css-before'] && $options['css-after']) {
            $name = $options[__class__];
            $css = '[data-aos=' . $name . ']{' . $options['css-before'] . '}';
            $css .= '[data-aos=' . $name . '].aos-animate{' . $options['css-after'] . '}';
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
            $wa->addInlineStyle($css);
        }
        // === le code HTML
        // --- STYLES
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);
        // l'options principale d'aos
        $attr_main['data-aos'] = $options[__class__];
        // toutes autres options d'aos
        foreach (array('delay', 'duration', 'easing', 'offset', 'anchor-placement') as $key) {
            $attr_main['data-aos-' . $key] = $options[$key];
        }
        if ($options['once']) {
            $attr_main['data-aos-once'] = 'true';
        }
        // le shortcode cible-t-il des tags du contenu
        if ($options['repeat'] != '') { // repeat effect on div/p/h2,...depending on repeat parameter
            // les memes attributs pour tous les tags cibles
            $attr_main['id'] = '';
            $attrs = $this->set_attr_tag('', $attr_main);
            // on les affecte aux tags
            $repeat = str_replace(',', '|', $options['repeat']); // remplace , du parametre par |
            $regex = '/(?!.*data-aos)(?:<(' . $repeat . '))/'; // ne pas remplacer si data-aos present
            if ($this->content) {
                $out = $this->dom_add_class($this->content, $repeat, $attr_main);
            } else {
                // $out['tag'] = ''; pour non traitement par up.php
                $txt_avant = substr($this->article->text, 0, $this->replace_deb);
                $txt_traite = substr($this->article->text, ($this->replace_deb + $this->replace_len));
                $txt_traite = preg_replace($regex, '$0 ' . $attrs . ' ', $txt_traite, -1, $count);
                $out['all'] = $txt_avant . $txt_traite;
            }
        } else {
            // si pas de repeat, on applique à tous le contenu
            $out = $this->set_attr_tag('div', $attr_main, $this->content);
        }
        return $out;
    }

    // run

    public function dom_add_class($content, $selector, $attr)
    {
        if (is_string($selector)) {
            $selector = explode('|', $selector);
        }
        // analyse de la structure du contenu
        $dom = new domDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $xpath = new DOMXpath($dom);
        $nodes = $xpath->query('/html/body/*');
        foreach ($nodes as $node) {
            if (in_array($node->tagName, $selector)) {
                foreach ($attr as $key => $val) {
                    $tmp = $node->getAttribute($key);
                    $sep = ($key == 'style') ? ';' : ' ';
                    $node->setAttribute($key, trim($val . $sep . $tmp));
                }
            }
        }
        $content = $dom->saveHTML($dom->documentElement);
        $content = preg_replace('~<(?:/?(?:html|head|body))[^>]*>\s*~i', '', $content);
        return $content;
    }

}

// class
