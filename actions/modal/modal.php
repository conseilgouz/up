<?php

/**
 * Affiche du contenu dans une fenêtre modale (popup)
 *
 * les types autorisés sont : inline, iframe, image, video, ajax
 *
 * syntaxe 1 : {up modal=contenu popup | label=texte du lien}
 * syntaxe 2 : {up modal=vide,'html','images' ou contenu | label=texte lien}contenu{/up modal}
 *
 * si vide ou 'html' : le contenu du popup est le code entre les shortcodes
 * si 'img' : chaque image du code entre les shortcodes sera un popup
 * sinon on analyse la valeur du paramètre pour déterminer son type
 *   - video vimeo, youtube ou dailymotion {up modal=//youtu.be/H9fa9aWFbLM}
 *   - image unique si {up modal=images/xx.jpg} ou png, ...
 *   - bloc inline si id de bloc {up modal=#bloc}
 *   - iframe si url {up modal=//lomart.fr} ou {up modal=doc/xx.pdf} ou {up modal=?index/...}
 * on peut forcer le type par type=inline, iframe, image, video, ajax
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit https://www.jqueryscript.net/lightbox/Lightbox-Popup-Plugin-Flashy.html
 * @tags    layout-dynamic
 */
/*
 * v1.63 - correction sur contenu inline
 * v1.7 - option zoom-suffix en remplacement de la constante '-mini'
 * v1.91 - ajout option filter (pascal) + bug overlayClose
 * v2.9 - applique class et style pour url si label est le contenu
 * v3.0 - possibilité de parcourir le contenu de toutes les modales d'une page
 */
defined('_JEXEC') or die();

class modal extends upAction
{
    public function init()
    {
        // charger les ressources communes a toutes les instances de l'action

        $this->load_file('flashy.css');
        $this->load_file('jquery.flashy.min.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // contenu ou type de contenu
            'type' => '', // pour forcer le type : inline, iframe, image, video, ajax
            'label' => '', // texte du lien pour afficher le popup. bbcode accepté
            /*[st-divers] options diverses*/
            'filter' => '', // conditions. Voir doc action filter (v1.8)
            'close-left' => 0, // croix de fermeture en haut à gauche. haut-droite par défaut
            'zoom-suffix' => '-mini', // suffixe pour les versions vignettes des images
            'base-js-params' => '', // règles JS définies par le webmaster (ajout dans init JS)
            /*[st-css] Gestion des styles */
            'id' => '', // identifiant. identique pour lier des modales
            'class' => '', // classe(s) pour bloc label
            'style' => '', // style inline pour bloc label
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // ===== parametres attendus par le script JS
        // important: valeurs par defaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indique ici.
        $js_options_def = array(
            /*[st-js] Options pour Javascript */
            'overlayClose' => 1, // 1 pour fermer la fenêtre modale en cliquant sur la zone grisée autour du contenu
            'videoAutoPlay' => 0, // 1 pour démarrer la video à l'ouverture du popup
            'gallery' => 1, // 0 pour traiter les images individuellement
            'title' => 1, // afficher le titre
            'width' => '', // largeur avec unité. Ex: 80%, 500px, ...
            'height' => '' // hauteur avec unité. Ex: 80%, 500px, ...
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);
        $this->ctrl_unit($options['width'], '%, px');
        $this->ctrl_unit($options['height'], 'px, %');

        // check filter options
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }
        $options['label'] = $this->get_bbcode($options['label'], false);
        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela evite de toutes les renvoyer au script JS
        $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options, 2);
        if ($options['base-js-params']) {
            $js_params = str_replace('{', '{' . $options['base-js-params'] . ',', $js_params);
        }
        $id = $options['id'];

        // === CSS inline
        if ($options['close-left']) {
            // $css = '#' . $id . ' .flashy-overlay .flashy-close{left:0}';
            // pour toutes les occurrences
            $css = '.flashy-overlay .flashy-close{left:0}';
            $this->load_css_head($css);
        }
        // === css-head
        $this->load_css_head($options['css-head']);

        // ==== Type de contenu
        $type = $this->ctrl_argument($options['type'], ',,inline, iframe, image, video, ajax');
        $main = $options[__class__];
        if ($type == 'inline' || $main == '' || $main == 'html') {
            // ---- INLINE ----
            $html[] = $this->get_code_inline($this->content, $options);
        } elseif ($main[0] == '#' || $main[0] == '.') {
            // ---- INLINE ID ---- c'est un lien !!!!!!!!
            $html[] = $this->get_code_url($main, $options, 'inline');
        } elseif ($type == 'image' || $main == 'img' || $main == 'image' || $main == 'images') {
            // ---- IMAGE GALLERY ----
            $html[] = $this->get_code_image($this->content, $options);
        } elseif (preg_match('#\.(jpg|jpeg|png|gif){1}$#iU', $main)) {
            // ---- IMAGE UNIQUE ----
            $html[] = $this->get_code_image('<img src="' . $main . '">', $options);
        } elseif ($type == 'video' || preg_match('#(?:youtube(-nocookie)?\.com|youtu\.be|vimeo.com)/#', $main) == 1) {
            // ---- VIDEO ----
            $html[] = $this->get_code_url($main, $options, 'video');
        } elseif ($type == 'ajax') {
            // ---- VIDEO ----
            $html[] = $this->get_code_url($main, $options, 'ajax');
        } else {
            // ---- IFRAME ----
            $html[] = $this->get_code_url($main, $options, 'iframe');
        }

        // -- init JS
        $this->load_jquery_code('$(".' . $id . '").flashy(' . $js_params . ');');
        // code en retour
        return implode(PHP_EOL, $html);
    }

    // fin fonction run

    /*
     * ==== lien pour inline
     */
    public function get_code_inline($content, $options)
    {
        // $id = $options['id'].'-'.uniqid(); //v5.2
        $id = $options['id'];
        if (! isset($this->options_user['id'])) {
            $id .= '-'.uniqid();
        }
        // -- le lien pour ouvrir le popup
        $a_attr['class'] = $options['class'];
        $a_attr['class'] = $this->str_append($a_attr['class'], $options['id'], ' ');
        $a_attr['style'] = $options['style'];
        $a_attr['data-flashy-type'] = 'inline';
        $a_attr['href'] = '#' . $id;
        $out[] = $this->set_attr_tag('a', $a_attr);
        $out[] = $options['label'];
        $out[] = '</a>';
        // -- le contenu de la popup
        $bloc_attr['id'] = $id;
        $bloc_attr['style'] = 'display:none';
        $out[] = $this->set_attr_tag('div', $bloc_attr);
        // $out[] = '<div class="inline">';
        $out[] = $content;
        // $out[] = '</div>';
        $out[] = '</div>';
        return implode(PHP_EOL, $out);
    }

    /*
     * ==== lien pour image(s)
     */
    public function get_code_image($content, $options)
    {
        $regex = '#<img .*>#iU';
        if (preg_match_all($regex, $content, $imglist)) {
            foreach ($imglist[0] as $img) {
                $img_attr = $this->get_attr_tag($img, 'alt');
                $a_attr['href'] = str_ireplace($options['zoom-suffix'], '', $img_attr['src']);
                $a_attr['class'] = $options['id'];
                $a_attr['data-flashy-type'] = 'image';
                if ($img_attr['alt'] == '') {
                    $img_attr['alt'] = $this->link_humanize($a_attr['href']);
                }
                if (empty($img_attr['title']) && $options['title']) {
                    $a_attr['title'] = $img_attr['alt'];
                }
                // cas d'une seule image avec label indique
                $code = $this->set_attr_tag('a', $a_attr);
                if (count($imglist) == 1 && $options['label']) {
                    $code .= $options['label'];
                } else {
                    $code .= $this->set_attr_tag('img', $img_attr);
                }
                $code .= '</a>';
                $content = str_replace($img, $code, $content);
            }
        }
        return $content;
    }

    /*
     * ==== lien pour iframe, ajax et video
     * <a class="upmodal" data-flashy-type="iframe" href="https://example.com/">iFrame</a>
     * <a id="up85-2" data-flashy-type="iframe" href="https://example.com/">iFrame</a>
     */
    public function get_code_url($content, $options, $type)
    {
        // -- la balise pour ouvrir le popup
        $a_attr['class'] = $options['id'];
        $a_attr['data-flashy-type'] = $type;
        $a_attr['href'] = $content;
        $a_attr['class'] = $this->str_append($a_attr['class'], $options['class'], ' ');
        $a_attr['style'] = $options['style'];
        $label = ($options['label']) ? $options['label'] : $this->content;
        // -- mise en forme
        return $this->set_attr_tag('a', $a_attr, $label);
    }
}

// --- class
