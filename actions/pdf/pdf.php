<?php

/**
 * Affiche un PDF dans le contenu ou génère un bouton pour l'afficher dans une fenêtre
 *
 * Le fichier PDF peut-être hébergé sur le serveur ou ailleurs (url absolue)
 *
 * syntaxe {up pdf=chemin du fichier PDF}
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="https://mozilla.github.io/pdf.js/" target="_blank">Mozilla PDF.js</a>
 * integration libraire TurnJs http://turnjs.com/ depuis https://github.com/iberan/pdfjs-flipbook
 * @tags  File
 */

/*
 * v1.63 - correction valeur défaut 'download-text'
 * v2.1 - ajout option background pour couleur fond perdu du PDF (merci Pascal)
 * v2.2 - fix largeur popup (modif css de modal-flashy)
 * v2.9 - mode magazine pour method=pdfjs
 * v3.1.1 - option maxi pour afficher les pdf les plus récent correspondant au masque
 * v3.1.3 - option zoom pour pour method=pdfjs
 * v 5.1.4 update pdfjs 5.3.31 (Pascal)
 * v 5.2 - fix dossier wasm, viewer.ftl dans dossiers 'locale'. fix zoom (Pascal)
 *       - embed à la place de pdfjs sous Windows-8
 * v 5.3.3 - nouveau parametre bgbtns : couleur arrière-plan des boutons en mode magazine
 */
defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;

class pdf extends upAction
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
            __class__ => '', // chemin du fichier pdf ou masque si plusieurs (v311)
            'method' => 'PDFJS', // ou Google, Embed ... ou 0
            'maxi' => 0, // nombre maxi de fichiers affichés. 0 sans limite
            'view' => 1, // 0 = masque le PDF
            'width' => '100%', // largeur iframe
            'height' => '500px', // hauteur iframe
            /* [st-dl] Gestion du lien pour télécharger le PDF */
            'download' => 1, // 1 pour afficher lien téléchargement
            'download-name' => '', // nom du fichier téléchargé
            'download-text' => 'en=Download %s;fr=Telecharger %s', // texte pour lien
            'download-icon' => '', // image affichée devant le lien
            'download-class' => '', // class(s) pour bouton lien seul
            'download-style' => '', // style inline pour bouton lien seul
            /* [st-btn] Gestion bouton pour afficher le PDF */
            'btn' => 0, // 1 pour afficher un bouton
            'btn-target' => '_blank', // cible : _blank, _parent, popup ou _popup
            'btn-text' => 'voir %s', // texte pour bouton
            'btn-icon' => '', // image affichée devant le texte du bouton
            'btn-class' => 'btn btn-primary', // class(s) pour bouton lien seul
            'btn-style' => '', // style inline pour bouton lien seul
            /* [st-divers] Divers */
            'close-left' => 0, // 1=croix de fermeture en haut à gauche. 0=haut-droite par défaut
            'flip' => 0, // activer le mode flipbook uniquement si PDFJS
            'background' => '', // couleur fond perdu du PDF au format #rrggbb
            'bgbtns' => '', // couleur de fond des boutons magazine
            'zoom' => '', // zoom par défaut (100%)
            'pdfjs-model' => 'web', // ou mobile (non opérationnel)
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc parent
            'style' => '', // style inline pour bloc parent
            'tag' => 'div', // balise pour bloc parent - v2.9
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $this->ctrl_unit($options['width'], '%,vw');
        $this->ctrl_unit($options['height'], 'px,vh,rem');
        $options['background'] = ltrim($options['background'], ' #');
        $options['bgbtns'] = ltrim($options['bgbtns'], ' #');
        $options['zoom'] = ltrim($options['zoom'], ' ');
        // === CSS-HEAD v2.9
        $this->load_css_head($options['css-head']);

        // ===
        if (strtolower($options['method']) == 'pdfjs' && $this->preview_ok() === false) {
            $options['method'] = 'embed';
        }
        // style
        $attr_view['style'] = 'width:' . $options['width'];
        $this->add_style($attr_view['style'], 'height', $options['height']);

        $color = '';
        if ($options['background']) {
            $color = $options['flip'] ? '&' : '#';
            $color .= 'background=' . $options['background'];
        }
        $bgbtns = '';
        if ($options['bgbtns']) {
            $bgbtns = $options['flip'] ? '&' : '#';
            $bgbtns .= 'bgbtns=' . $options['bgbtns'];
        }
        $zoom = '';
        if ($options['zoom']) {
            $zoom = $options['flip'] ? '&' : '#';
            $zoom .= 'zoom=' . $options['zoom'];
        }

        error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

        // le ou les fichiers pdf
        $pdf_list = glob($options[__class__], GLOB_BRACE | GLOB_NOSORT);

        if (empty($pdf_list)) {
            return $this->msg_inline('aucun fichier trouvé pour ' . $options[__class__]);
        }

        // tri inverse
        rsort($pdf_list);

        // ==== PREPARATION CODE HTML RETOUR
        $html = array();

        $attr_item = array();
        $this->get_attr_style($attr_item, $options['class'], $options['style']);
        if ((count($pdf_list) == 1) || $options['maxi'] == 1) { // pas d'id si plusieurs blocs
            $attr_item['id'] = $options['id'];
        }

        $maxi = ($options['maxi'] == 0) ? count($pdf_list) : $options['maxi'];
        for ($i = 0; $i < $maxi; $i++) {
            $pdf_file = $pdf_list[$i];
            $ficext = strtolower(pathinfo($pdf_file, PATHINFO_EXTENSION));
            if ($ficext != 'pdf') {
                continue;
            }
            $pdf_url = $this->get_url_absolute($pdf_list[$i]);

            // Préparation lien pour affichage
            if ($options['view'] || $options['btn']) {
                switch (strtolower($options['method'])) {
                    case 'pdfjs':
                        $flip = ($options['flip']) ? '#magazineMode=true' : ''; // v2.9
                        $pdfjs_model = $this->ctrl_argument($options['pdfjs-model'], 'web,mobile,cdn');
                        $attr_view['src'] = Uri::root() . $this->actionPath;
                        $attr_view['src'] .= 'pdfjs/' . $pdfjs_model . '/viewer.html';
                        $attr_view['src'] .= '?file=' . $pdf_url . $flip . $color . $zoom;
                        $view = $this->set_attr_tag('iframe', $attr_view, true);
                        break;
                    case 'google':
                        // v5.2 service disparu en 2023. on utilise embed
                        // $attr_view['src'] = 'https://docs.google.com/gview';
                        // $attr_view['src'] .= '?url=' . $pdf_url . '&embedded=true';
                        // $attr_view['frameborder'] = '1';
                        // $view = $this->set_attr_tag('iframe', $attr_view, 'up error');
                        // break;
                    case 'embed':
                        $attr_view['src'] = $pdf_url;
                        $attr_view['frameborder'] = '1';
                        $view = $this->set_attr_tag('embed', $attr_view);
                        break;
                }
            }

            // ==== EXCLUSION METHOD
            if ($options['btn']) {
                $options['view'] = 0;
                $options['download'] = 0;
            }
            // Bloc item
            $html[] = $this->set_attr_tag($options['tag'], $attr_item);
            // ---- IFRAME
            if ($options['view']) {
                $html[] = $view;
            }
            // ---- LIEN DOWNLOAD
            if ($options['download'] && $this->on_server($pdf_url)) {
                // texte bouton et lien
                $human_name = ($options['download-name']) ? $options['download-name'] : basename($pdf_file);
                // info: sprintf = false si pas le nombre d'arguments
                $link_text = $this->sreplace('%s', $human_name, $options['download-text']);
                if ($options['download-icon'] > '') {
                    $img['src'] = $options['download-icon'];
                    $img['alt'] = 'PDF Icon';
                    $link_text = $this->set_attr_tag('img', $img) . ' ' . $link_text;
                }

                $attr_link['href'] = $pdf_url;
                $attr_link['download'] = $human_name; // on force le téléchargement
                $attr_link['class'] = $options['download-class'];
                $attr_link['style'] = $options['download-style'];
                $str = '<' . $options['tag'] . '>';
                $str .= $this->set_attr_tag('a', $attr_link, $link_text);
                $str .= '</' . $options['tag'] . '>';
                $html[] = $str;
            }

            // ---- BOUTON
            if ($options['btn']) {
                // texte bouton et lien
                $link_text = $this->sreplace('%s', basename($pdf_file), $options['btn-text']);
                if ($options['btn-icon'] > '') {
                    $img['src'] = $options['btn-icon'];
                    $img['alt'] = 'PDF Icon';
                    $link_text = $this->set_attr_tag('img', $img) . ' ' . $link_text;
                }
                $attr_btn['href'] = $pdf_url;
                $attr_btn['class'] = $options['btn-class'];
                $attr_btn['style'] = $options['btn-style'];
                $target = $this->ctrl_argument($options['btn-target'], '_blank,_parent,_self,_top,popup');
                if ($target == 'popup' || $target == '_popup') {
                    $this->load_file('../modal/flashy.css');
                    $this->load_file('../modal/jquery.flashy.min.js');
                    $attr_btn['data-flashy-type'] = 'iframe';
                    $this->add_class($attr_btn['class'], 'flashy');
                    if ($options['close-left']) {
                        $css = '.flashy-overlay .flashy-close{left:0}';
                        $this->load_css_head($css);
                    }
                } else {
                    $attr_btn['target'] = $target;
                }
                $flip = '';
                if ($options['flip']) { // on passe en pleine page
                    $flip = '#magazineMode=true';
                    $css = '.flashy-container .flashy-content.flashy-iframe {width: 100%!important;height: 96vh!important;}';
                    $css .= '.flashy-container .flashy-content-inner {padding: 2vh 2px;}';
                    $css .= '.flashy-overlay .flashy-prev.flashy-show, .flashy-overlay .flashy-next.flashy-show {display:none}';
                    $this->load_css_head($css);
                }
                $attr_btn['href'] = $this->actionPath . 'pdfjs/web/viewer.html?file=' . $pdf_url . $flip . $color . $bgbtns;
                $html[] = $this->set_attr_tag('a', $attr_btn, $link_text);
                $html[] = $this->load_jquery_code('if ($(".flashy").length > 0) $(".flashy").flashy({overlayClose:1})');
            }
            $html[] = '</' . $options['tag'] . '>';
        } // fin for

        return implode(PHP_EOL, $html);
    }

    // run

    /*
     * preview_ok()
     * return TRUE si l'OS ou navigateur visiteur permet pdfjs
     * XP: NT 5.1, W7: NT 6.1, W8: NT 6.2, W8.1: NT 6.3, W10: NT 10
     */

    public function preview_ok()
    {
        $ok = true;
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $regex = '/Windows NT ([0-9.]*)/';
        if (preg_match($regex, $userAgent, $version) == 1) {
            $ok = (intval($version[1]) > 6);
        }
        return $ok;
    }

}

// class
