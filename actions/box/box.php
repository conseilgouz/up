<?php

/**
 * affiche une boite avec un tite, sous-titre, image, contenu et action
 *
 * syntaxe: {up box=model | title=title}contenu HTML{/up box}
 *
 * ##title## ##title-link## ##subtitle## ##subtitle-link## ##title-subtitle## ##title-subtitle-link##
 * ##link## ##target##
 * ##image## ##image-link## ##image-css## ##image-css-xxx##
 * ##action-link## ##action-text##
 * ##content##
 * ##head## & ##/head## : une balise DIV avec les attributs définis par head-class & head-style
 * ##body## & ##/body##
 *
 * @author      LOMART
 * @version     UP-1.9.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags      Layout-static
 *
 */

/*
 * v1.95 - refonte complête. Possibilité de template et de multibox
 * v2.5 - ajout mot-clé : ##link## ##target## ##action-text##
 * - fix : autoriser les shortcodes de LM-Prism
 * v5.2 : nouveau modèle bg-image-only
 */
defined('_JEXEC') or die();

class box extends upAction
{
    public function init()
    {
        return;
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // model [danger, info] ou prefset
            'template' => '##head## ##title-link## ##subtitle## ##image## ##/head## ##content## ##action-link##', // modèle
            'multibox' => '3-2-1', // nombre de blocs sur la même ligne (desktop-tablet-mobile)
            'multibox-class' => 'fg-vspace-between-1', // classe(s) pour le bloc externe
            'multibox-style' => '', // style CSS pour le bloc externe
            /* box */
            'id' => '',
            'class' => '', // classe(s) pour la box
            'style' => '', // style inline pour la box
            /* title */
            'title' => '', // titre. Si vide et demandé : on prend le premier hx du contenu
            'title-tag' => 'h4', // balise pour titre
            'title-class' => '', // class user pour titre
            'title-style' => '', // style inline pour titre
            'title-link-class' => '', // class user pour titre avec lien
            'title-link-style' => '', // style inline pour titre avec lien
            /* subtitle */
            'subtitle' => '', // sous-titre
            'subtitle-tag' => 'h5', // mise en forme du sous-titre
            'subtitle-class' => '', // class user pour sous-titre
            'subtitle-style' => '', // style inline pour sous-titre
            'subtitle-link-class' => '', // class user pour sous-titre avec lien
            'subtitle-link-style' => '', // style inline pour sous-titre avec lien
            /* image */
            'image' => '', // image. Si vide et demandée : on prend la première image du contenu
            'image-alt' => '', // texte alternatif pour l'image. Par défaut, le nom du fichier humanisé
            'image-class' => '', // class user pour image
            'image-style' => '', // style inline pour image
            'image-link-class' => '', // class user pour image avec lien
            'image-link-style' => '', // style inline pour image avec lien
            /* action */
            'action' => '', // texte du bouton action
            'action-tag' => 'div', // mise en forme du bouton action
            'action-class' => '', // class user pour action
            'action-style' => '', // style inline pour action
            'action-link-class' => '', // class user pour action avec lien
            'action-link-style' => '', // style inline pour action avec lien
            /* link */
            'link' => '', // lien. . Si vide et demandé : on prend le premier lien sur title ou image
            'link-target' => '', // _blank pour nouvelle fenêtre
            /* header */
            'head-class' => '', // class pour le bloc entête. en général title, subtitle, image
            'head-style' => '', // style pour le bloc entête
            /* body */
            'body-class' => '', // class pour le bloc. en général content et action
            'body-style' => '', // style pour le bloc. en général content et action
            /* general */
            'css-head' => '', // style CSS inséré dans le HEAD
            'align-vertical' => 'fg-vspace-between-1' // type de repartition verticale en multibox
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // charge model CSS
        $model = strtolower($options[__class__]);
        $this->load_model($model, $options);
        $options['template'] = $this->get_bbcode($options['template']);

        // CSS dans le head
        if ($options['css-head']) {
            $this->load_css_head($options['css-head']);
        }

        // ==================================
        // ===== DONNEEES GLOBALES
        // ==================================
        // il est possible d'avoir plusieurs contenus séparé par {===}
        // dans ce cas titre et image sont dans le content
        $contents = $this->get_content_parts($this->content);
        $multibox = (count($contents) > 1);

        if ($multibox) {
            // par defaut 3-2-1
            $rwd = explode('-', $options['multibox']);
            for ($i = 0; $i < 3; $i++) {
                if (! isset($rwd[$i])) {
                    $rwd[$i] = 3 - $i;
                }
            }
            $attr_multibox['id'] = $options['id'];
            $attr_multibox['class'] = 'up-multibox-' . $model;
            $attr_multibox['class'] .= ' fg-row fg-gap';
            $attr_multibox['class'] .= ' fg-auto-' . $rwd[0];
            $attr_multibox['class'] .= ' fg-auto-m' . $rwd[1];
            $attr_multibox['class'] .= ' fg-auto-s' . $rwd[2];
            $this->get_attr_style($attr_multibox, $options['multibox-class'], $options['multibox-style']);
            $html[] = $this->set_attr_tag('div', $attr_multibox);
        }

        // les valeurs par défaut
        $kw_1['title']['text'] = $options['title'];
        $kw_1['subtitle']['text'] = $options['subtitle'];
        $kw_1['image']['src'] = $options['image'];
        $kw_1['image']['alt'] = $options['image-alt'];
        $kw_1['action']['text'] = $options['action'];
        $kw_1['link']['href'] = $options['link'];
        $kw_1['link']['target'] = $options['link-target'];

        // ==== Style bloc
        if (! $multibox) {
            $attr_box['id'] = $options['id'];
        }
        $model = ($model) ? 'up-box-' . $model : '';
        $this->get_attr_style($attr_box, 'up-box', $model, $options['class'], $options['style']);

        $kw_1['title']['class'] = $options['title-class'];
        $kw_1['title']['style'] = $options['title-style'];
        $kw_1['title']['link-class'] = $options['title-link-class'];
        $kw_1['title']['link-style'] = $options['title-link-style'];
        $kw_1['subtitle']['class'] = $options['subtitle-class'];
        $kw_1['subtitle']['style'] = $options['subtitle-style'];
        $kw_1['subtitle']['link-class'] = $options['subtitle-link-class'];
        $kw_1['subtitle']['link-style'] = $options['subtitle-link-style'];
        $kw_1['image']['class'] = $options['image-class'];
        $kw_1['image']['style'] = $options['image-style'];
        $kw_1['image']['link-class'] = $options['image-link-class'];
        $kw_1['image']['link-style'] = $options['image-link-style'];
        $kw_1['action']['class'] = $options['action-class'];
        $kw_1['action']['style'] = $options['action-style'];
        $kw_1['action']['link-class'] = $options['action-link-class'];
        $kw_1['action']['link-style'] = $options['action-link-style'];

        // ===== ANALYSE TEMPLATE
        $has_title = (stripos($options['template'], '##title') !== false);
        $has_image = (stripos($options['template'], '##image') !== false);
        $has_link = (stripos($options['template'], 'link##') !== false);

        // ==================================
        // ==== TRAITEMENT PAR BOX
        // ==================================
        foreach ($contents as $content) {

            // préparation contenu pour analyse regex
            $content = str_replace(PHP_EOL, '', $content);

            // récupération et suppression des shortcodes secondaires dans le contenu
            $kw_2 = $this->get_subshortcode($content);
            // on surcharge les options générales avec les shortcodes contenu
            $kw_box = $this->options_merge($kw_1, $kw_2);
            // init
            $kw_3 = array();

            // === RECHERCHE INFOS DANS CONTENU
            // --- LINK
            if ($has_link && empty($kw_box['link']['href'])) {
                if (preg_match('#<a.*>#Ui', $content, $old) == 1) {
                    $kw_3['link'] = $this->get_attr_tag($old[0]);
                }
            }
            // --- TITRE
            if ($has_title && empty($kw_box['title']['text'])) {
                // [0]=><h2 class="foo"><a href="#" target="_blank">title</a></h2>
                // [1]=><h2 class="foo">
                // [2]=>h2
                // [3]=><a href="#">title</a>
                if (preg_match('#(<(h.?)\b.*>)(.*)</h.?>#Ui', $content, $old) == 1) {
                    $kw_3['title'] = $this->get_attr_tag($old[1]);
                    $kw_3['title']['text'] = $old[3];
                    // supprimer a eventuel !!!!!
                    $content = str_replace($old[0], '', $content);
                }
            }
            // --- IMAGE
            if ($has_image && empty($kw_box['image']['src'])) {
                // [0]=>'<p><a href="#"><img src="..."></a></p>'
                // [1]=>'<p>'
                // [2]=>'<a href="#">'
                // [3]=>'a'
                // [4]=>'<img src="...">'
                if (preg_match('#(<[p|div]>)?(<(a) .*>)?(<img.*>)#Ui', $content, $old) == 1) {
                    $kw_3['image'] = $this->get_attr_tag($old[4]);
                    // alt = humanize !!!
                    $len_img = strlen($old[0]);
                    $len_img += ($old[1]) ? strlen($old[1]) + 1 : 0; // </p> ou </div>
                    $len_img += ($old[2]) ? 4 : 0; // </a>
                    $tmp = substr($content, strpos($content, $old[0]), $len_img);
                    $content = str_replace($tmp, '', $content);
                }
            }
            // ==== PREPARATION CONTENU FINAL
            $kw_box = $this->options_merge($kw_box, $kw_3);
            $kw_box['title']['text'] = $this->get_bbcode($kw_box['title']['text']);
            $kw_box['subtitle']['text'] = $this->get_bbcode($kw_box['subtitle']['text']);
            $kw_box['action']['text'] = $this->get_bbcode($kw_box['action']['text']);

            // ==== CONTENU
            // les shortcodes et blocs récupérés ont été supprimés
            $kw_box['content']['text'] = preg_replace('/^<\/.*>/U', '', trim($content));

            // ==== PREPARATION VARIABLES
            $attr = array(); // reset
            $this->get_attr_style($attr['title'], 'up-box-title', $kw_box['title']['class'], $kw_box['title']['style']);
            $this->get_attr_style($attr['title-link'], 'up-box-title-link', $kw_box['title']['link-class'], $kw_box['title']['link-style']);
            $this->get_attr_style($attr['subtitle'], 'up-box-subtitle', $kw_box['subtitle']['class'], $kw_box['subtitle']['style']);
            $this->get_attr_style($attr['subtitle-link'], 'up-box-subtitle-link', $kw_box['subtitle']['link-class'], $kw_box['subtitle']['link-style']);
            $this->get_attr_style($attr['action'], 'up-box-action', $kw_box['action']['class'], $kw_box['action']['style']);
            $this->get_attr_style($attr['action-link'], 'up-box-action-link', $kw_box['action']['link-class'], $kw_box['action']['link-style']);
            $this->get_attr_style($attr['image'], 'up-box-image', $kw_box['image']['class'], $kw_box['image']['style']);
            $this->get_attr_style($attr['image-link'], 'up-box-image-link', $kw_box['image']['link-class'], $kw_box['image']['link-style']);

            // ==========================
            // ==== CODE HTML POUR RETOUR
            // ==========================

            $html[] = $this->set_attr_tag('div', $attr_box);
            $tmpl = $options['template'];

            // ===== CONTENT
            $this->kw_replace($tmpl, 'content', $kw_box['content']['text']);

            // ===== LINK
            $this->kw_replace($tmpl, 'link', $kw_box['link']['href']);
            $this->kw_replace($tmpl, 'target', $kw_box['link']['target']);

            // ===== TITLE
            if (stripos($tmpl, '##title') !== false) {
                $str = $kw_box['title']['text'];
                if ($str) {
                    $str = $this->set_attr_tag($options['title-tag'], $attr['title'], $str);
                }
                $this->kw_replace($tmpl, 'title', $str);
            }

            if (stripos($tmpl, '##title-link') !== false) {
                $str = $kw_box['title']['text'];
                if (! empty($str) && $kw_box['link']['href']) {
                    $tmp = array_merge($kw_box['link'], $attr['title-link']);
                    $str = $this->set_attr_tag('a', $tmp, $str);
                }
                if ($str) {
                    $str = $this->set_attr_tag($options['title-tag'], $attr['title'], $str);
                }
                $this->kw_replace($tmpl, 'title-link', $str);
            }

            // ===== SUBTITLE
            if (stripos($tmpl, '##subtitle') !== false) {
                $str = $kw_box['subtitle']['text'] ?? ''; // 3.0
                if ($str) {
                    $str = $this->set_attr_tag($options['subtitle-tag'], $attr['subtitle'], $str);
                }
                $tmpl = str_ireplace('##subtitle##', $str, $tmpl);
            }

            if (stripos($tmpl, '##subtitle-link##') !== false) {
                $str = $kw_box['subtitle']['text'];
                if (! empty($str) && $kw_box['link']['href']) {
                    $tmp = array_merge($kw_box['link'], $attr['subtitle-link']);
                    $str = $this->set_attr_tag('a', $tmp, $str);
                }
                if ($str) {
                    $str = $this->set_attr_tag($options['subtitle-tag'], $attr['subtitle'], $str);
                }
                $this->kw_replace($tmpl, 'subtitle-link', $str);
            }

            // ===== TITLE + SUBTITLE
            // <tag-title.up-box-title><a.up-box-title-link>TITRE<small.up-box-subtitle>SUBTITLE</small></a></tag-title>
            // si title seul : <tag-title.up-box-title><a.up-box-title-link>TITRE</a></tag-title>
            // si subtitle seul : <tag-subtitle.up-box-subtitle><a.up-box-subtitle-link>SUBTITLE</a></tag-subtitle>
            if (stripos($tmpl, '##title-subtitle') !== false) {
                $str = '';
                $title = $kw_box['title']['text'];
                $subtitle = $kw_box['subtitle']['text'];
                $is_link = (stripos($tmpl, '##title-subtitle-link##') !== false);
                $attr_title = array_merge($kw_box['link'], $attr['title-link']);
                $attr_subtitle = array_merge($kw_box['link'], $attr['subtitle-link']);
                if ($title && $subtitle) {
                    $str = $title . $this->set_attr_tag('small', $attr['subtitle'], $subtitle);
                    if ($is_link) {
                        $str = $this->set_attr_tag('a', $attr_title, $str);
                    }
                    $str = $this->set_attr_tag($options['title-tag'], $attr['title'], $str);
                } elseif ($title) {
                    $str = $title;
                    if ($is_link) {
                        $str = $this->set_attr_tag('a', $attr_title, $str);
                    }
                    $str = $this->set_attr_tag($options['title-tag'], $attr['title'], $str);
                } elseif ($subtitle) {
                    $str = $subtitle;
                    if ($is_link) {
                        $str = $this->set_attr_tag('a', $attr_subtitle, $str);
                    }
                    $str = $this->set_attr_tag($options['subtitle-tag'], $attr['subtitle'], $str);
                }
                $this->kw_replace($tmpl, 'title-subtitle', $str);
                $this->kw_replace($tmpl, 'title-subtitle-link', $str);
            }

            // ===== ACTION
            $str = $kw_box['action']['text'] ?? ''; // 3.0
            $tmpl = str_ireplace('##action-text', $str, $tmpl); // v2.5
            if (stripos($tmpl, '##action') !== false) {
                if ($str) {
                    $str = $this->set_attr_tag($options['action-tag'], $attr['action'], $str);
                }
                $this->kw_replace($tmpl, 'action', $str);
            }
            $str = $kw_box['action']['text'] ?? ''; // 3.0
            if (stripos($tmpl, '##action-link') !== false) {
                if (! empty($str) && $kw_box['link']['href']) {
                    $tmp = array_merge($kw_box['link'], $attr['action-link']);
                    $str = $this->set_attr_tag('a', $tmp, $str);
                }
                if ($str) {
                    $str = $this->set_attr_tag($options['action-tag'], $attr['action'], $str);
                }
                $this->kw_replace($tmpl, 'action-link', $str);
            }

            // ===== IMAGE
            if (stripos($tmpl, '##image') !== false) {
                // -- comme background
                if (stripos($tmpl, '##image-css') !== false) {
                    preg_match('/##image-css(.*)##/Ui', $tmpl, $matches);
                    $str = $kw_box['image']['src'];
                    if ($str) {
                        // $css = 'background:url("/' . $str . '") no-repeat center center;background-size:cover;';
                        $css = 'background:url("' . $this->get_url_absolute($str) . '") no-repeat center center;background-size:cover;'; // v5.1
                        if ($matches[1] == '') {
                            // sur le bloc principal
                            $sel = ($multibox) ? '#id .up-box' : '#id.up-box';
                        } else {
                            // sur le bloc indiqué. ex: image-css-head
                            $sel = '#id .up-box' . $matches[1];
                        }
                        $this->load_css_head($sel . '{' . $css . '}');
                    }
                    // on conserve l'ancienne méthode
                    $tmpl = str_ireplace($matches[0], '', $tmpl);
                }

                // -- comme img
                $attr['image']['alt'] = (empty($kw_box['image']['alt'])) ? $this->link_humanize($kw_box['image']['src']) : $kw_box['image']['alt'];
                if (stripos($tmpl, '##image##') !== false) {
                    $str = $kw_box['image']['src'];
                    if ($str) {
                        $attr['image']['src'] = $str;
                        $str = $this->set_attr_tag('img', $attr['image']);
                    }
                    $this->kw_replace($tmpl, 'image', $str);
                }
                // --------- ##image-link##
                if (stripos($tmpl, '##image-link') !== false) {
                    $str = $kw_box['image']['src'];
                    $attr['image']['src'] = $str;
                    if ($str) {
                        $str = $this->set_attr_tag('img', $attr['image']);
                    }
                    if (! empty($str) && $kw_box['link']['href']) {
                        $tmp = array_merge($kw_box['link'], $attr['image-link']);
                        $str = $this->set_attr_tag('a', $tmp, $str);
                    }
                    $this->kw_replace($tmpl, 'image-link', $str);
                }
            }

            // -- les blocs pour style

            $tmpl = preg_replace('/##head##\s*##\/head##/', '', $tmpl);
            if (stripos($tmpl, '##head##') !== false) {
                $attr = array();
                $this->get_attr_style($attr, 'up-box-head', $options['head-class'], $options['head-style']);
                $tmpl = str_ireplace('##head##', $this->set_attr_tag('div', $attr), $tmpl);
                $tmpl = str_ireplace('##/head##', '</div>', $tmpl);
            }

            $tmpl = preg_replace('/##body##\s*##\/body##/', '', $tmpl);
            if (stripos($tmpl, '##body##') !== false) {
                $attr = array();
                $this->get_attr_style($attr, 'up-box-body', $options['body-class'], $options['body-style']);
                $tmpl = str_ireplace('##body##', $this->set_attr_tag('div', $attr), $tmpl);
                $tmpl = str_ireplace('##/body##', '</div>', $tmpl);
            }

            // ajout du bloc pour 1 box
            $html[] = $tmpl;
            $html[] = '</div>';
        } // foreach box
        if ($multibox) {
            $html[] = '</div>';
        }
        return implode(PHP_EOL, $html);
    }

    // run

    /*
     * get_subshortcode
     * analyse des shortcodes secondaires
     * retourne $out : array avec les options
     * actualise $content
     * v2.5 ajout \w dans regex pour ecarter les <b>{</b>
     */
    public function get_subshortcode(&$content)
    {
        $out = array();
        $search = array(
            'image',
            'link',
            'subtitle',
            'title',
            'action'
        );
        $replace = array(
            'src',
            'href',
            'text',
            'text',
            'text'
        );
        $regex = '#(?:<p>)?{(\w.*[\s\=\|].*)}(?:<\/p>)*?#siU';
        if (preg_match_all($regex, $content, $matches) > 0) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $arr = explode('|', $matches[1][$i]);
                $optname = '';
                foreach ($arr as $tmp) {
                    $tmp = preg_split("/=/", trim($tmp), 2);
                    if ($optname == '') {
                        $optname = $tmp[0];
                        if (isset($out[$optname])) {
                            break;
                        }
                        $tmp[0] = str_replace($search, $replace, $tmp[0]);
                    }
                    // sa valeur (true si aucune)
                    $value = (count($tmp) == 2) ? trim($tmp[1]) : true;

                    $out[$optname][$tmp[0]] = $value;
                }
                $content = str_replace($matches[0][$i], '', $content);
            }
        }
        // nettoyage wisiwyg
        $content = trim($content);
        while (substr($content, 0, 6) == '<br />') {
            $content = substr($content, 6);
        }
        while (substr($content, - 6, 6) == '<br />') {
            $content = substr($content, 0, - 6);
        }

        return $out;
    }

    /*
     * Charge le fichier CSS
     * et initialise les options avec le fichier model.ini
     * sauf si définies par user ou prefs.ini
     */
    public function load_model($model, &$options)
    {
        if (empty($model)) {
            return;
        }
        // charge fichier CSS
        $this->load_file('model/' . $model . '.css');
        // surcharge des options par celle de model.ini
        $inifile = $this->get_custom_path('model/' . $model . '.ini', null, false);
        if ($inifile !== false) {
            $modelini = $this->load_inifile($inifile, true);
            if ($modelini !== false) {
                foreach ($modelini as $key => $val) {
                    $key = strtolower($key);
                    if (isset($options[$key])) {
                        if (! isset($this->options_user[$key])) {
                            $options[$key] = $val;
                        }
                    } else {
                        $this->msg_error($this->trad_keyword('OPTION_NOT_FOUND', $key, $inifile));
                    }
                }
            }
        }
    }

    // ajoute les clé-valeurs de $arr2 dans $arr1
    // $arr1 contient les options du shortcode par type de mot-clé
    // $arr2 contient les options saisie dans le contenu
    // exemple :
    // $a1['title'] = array('title'=>'TITRE','class'=>'foo')
    // $a2['title'] = array('title'=>'TITRE-2','style'=>'color:red')
    // return ['title'] = array('title'=>'TITRE-2','class'=>'foo','style'=>'color:red')
    public function options_merge($arr1, $arr2)
    {
        if (! empty($arr2)) {
            foreach ($arr2 as $arr2key => $arr2val) {
                foreach ($arr2val as $key => $val) {
                    if (! empty($val)) {
                        if ($key == 'style' || $key == 'class' && $val[0] == '+') {
                            $val[0] = ';';
                            $arr1[$arr2key][$key] .= trim($val);
                        } else {
                            $arr1[$arr2key][$key] = trim($val);
                        }
                    }
                }
            }
        }
        return $arr1;
    }
}

// class
