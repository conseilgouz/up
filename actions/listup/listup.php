<?php

/**
 * Personnaliser des listes simples et ordonnées
 *
 * syntaxe {up listup=style puce} list UL/OL {/up listup}
 *
 * @version  UP-2.6
 * @author   Lomart
 * @license  <a href="http: //www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Editor
 *
 */
/*
 * v2.61 - fix min/maj pour les noms de couleurs
 * v2.8 - php8
 * v3 - fix si autres tags que UL/OL dans les LI
 * v5.3.3 : php 8.4 compatibility
 */
defined('_JEXEC') or die();

class listup extends upAction
{
    public $kwc_formula = [];
    public $kwc_pref = [];

    public function init()
    {
        $this->load_file('listup.css');
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // style des puces (séparateur point-virgule) par niveaux (markers) (séparateur virgule)
            /* [st-default] Valeurs par défaut */
            'ul-default' => 'square;t-c1', // type liste par défaut
            'ol-default' => 'decimal;t-c1', // type liste numérotée par défaut
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc principal
            'style' => '', // class ou style inline pour bloc principal
            'style-*' => '', // class ou style pour les niveaux des puces
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-divers] Divers */
            'start' => '', // indice pour début liste numérotée
            'valid-type' => 'rounded-alpha,squared-alpha,circled-decimal,rounded-decimal,decimal-leading-zero,lower-alpha,upper-alpha,lower-roman,upper-roman', // types de liste (numérotée) autorisés
            'fonticon' => 'Font Awesome 6 Free' // police d'icônes installée sur le site
        );

        // === les mots-clés pour définir les valeurs pour :before{content}
        // --- formule pour content
        $this->kwc_formula['decimals'] = "var(--upli-prefix) counters(upli, '.') var(--upli-suffix)";
        $this->kwc_formula['decimal'] = "var(--upli-prefix) counter(upli) var(--upli-suffix)";

        // --- Préf
        // standards redéfinis pour utilisation par :before
        $this->kwc_pref['square'] = 'Ux25A0';
        $this->kwc_pref['circle'] = 'Ux26AC';
        $this->kwc_pref['disc'] = 'Ux2B24';
        // --- les bullets définis par le webmaster
        $pref_user_file = $this->actionPath . 'custom/prefs.ini';
        if (file_exists($pref_user_file)) {
            $pref_user = $this->load_inifile($this->actionPath . 'custom/prefs.ini', true);
            if ($pref_user !== false && isset($pref_user['bullets'])) {
                $this->kwc_pref = array_merge($this->kwc_pref, $pref_user['bullets']);
            }
        }
        // === les variables CSS définies dans liste_style.scss
        // appliqué à la ligne : .25rem 0 0 2rem (pour marge gauche 2rem)
        $cssvar_li = array(
            'gap',
            'margin-top',
            'margin-right',
            'margin-bottom',
            'margin-left',
            'padding-left',
            'position'
        );
        // disponibles pour liste par content ou type
        $cssvar_marker = array(
            'color',
            'font-family',
            'font-size',
            'font-weight',
            'line-height',
            'vertical-align'
        );
        // uniquement disponibles pour les listes par content
        $cssvar_before = array(
            'gap',
            'background',
            'background-size',
            'text-align',
            'border',
            'border-radius',
            'padding',
            'width',
            'height'
        );
        $cssvar_line = array(
            'line-border',
            'line-top',
            'line-bottom',
            'line-left',
            'line-right'
        );
        $this->varStyle = array_merge($cssvar_li, $cssvar_marker, $cssvar_before, $cssvar_line);
        $this->varStyleString = array(
            'prefix',
            'suffix'
        );

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // les types autorisés par laa classe upli-type
        $this->valid_type = array_map('trim', explode(',', $this->options['valid-type']));
        // l'option principale est un raccourci pour style-* (séparateur virgule)
        // ex: {up listup=square;t-red,disc;color:rgb(255,0,0)}
        // Ils sont prioritaire sur style-*
        // permet isolation des virgules entre parenthèses
        preg_match_all('#\(.*\)#', $this->options[__class__], $matches);
        foreach ($matches as $match) {
            $this->options[__class__] = str_replace($match, str_replace(',', '§§', $match), $this->options[__class__]);
        }
        $this->styles_main = array_pad(explode(',', $this->options[__class__]), 8, '');
        foreach ($this->styles_main as &$val) {
            $val = str_replace('§§', ',', $val);
        }
        // $this->styles_main = array_pad(explode(',', $this->options[__class__]), 8, '');

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // le contenu
        require_once($this->upPath . '/assets/lib/simple_html_dom.php');
        $html = new simple_html_dom();
        $html->load($this->content);
        // exploration de la liste
        $html = $this->update_list($html);
        // le bloc externe de la liste
        $content = $html->save();
        $html->clear();

        // on supprime les codes styles au début des items
        $content = preg_replace('#\>\[.*\]#U', '>', $content);
        // code en retour
        return $content;
    }

    // run

    /*
     * function update_list()
     * ----------------------
     * Affecte les classes et styles aux éléments de la liste
     */
    public function update_list($node, $niv = 1)
    {
        $child = $node->find('ul,ol', 0); // ul/ol
        if ($niv == 1) {
            $attr['style'] = '';
            $attr['class'] = '';
            if (isset($child->tag)) { // v2.8
                $attr = $this->get_elem_style($child->tag, $this->options['style-1'], $this->styles_main[0]);
            }
            $attr['id'] = $this->options['id'];
            $this->main_class = $attr['class'];
            $this->get_attr_style($attr, $this->options['class'], $this->options['style']);
            $this->multicpt = (strpos($attr['style'], 'counters') !== false);
            if ($this->options['start']) {
                if (strpos($attr['class'], 'upli-type') !== false) {
                    $attr['start'] = $this->options['start'];
                } else {
                    // methode perso
                    $attr['style'] .= ' counter-increment:upli ' . ((int) $this->options['start'] - 1);
                }
            }
            foreach ($attr as $k => $v) {
                if (! is_null($child)) {
                    $child->setAttribute($k, $v);
                }
            }
        } else {
            if ($this->multicpt) {
                $this->attr[$niv]['class'] = $this->main_class;
            } else {
                if (! isset($this->attr[$niv]) && isset($child->tag)) {
                    $this->attr[$niv] = $this->get_elem_style($child->tag, $this->options['style-' . $niv], $this->styles_main[$niv - 1]);
                }
                // la meme classe pour les enfants
                $this->attr[$niv]['class'] = 'upli-reset ' . $this->main_class;
            }
            foreach ($this->attr[$niv] as $k => $v) {
                if (! is_null($child)) {
                    $child->setAttribute($k, $v);
                }
            }
        }
        // --- les items
        $child = $node->find('li', 0); // ul/ol
        while ($child) {
            if ($child->tag == 'li') {
                $text = trim($child->plaintext);
                if ($text[0] == '[') {
                    $style = substr($text, 1, strpos($text, ']') - 1);
                    $attr = $this->get_elem_style('li', $style);
                    foreach ($attr as $k => $v) {
                        if (! is_null($child)) {
                            $child->setAttribute($k, $v);
                        }
                    }
                }

                // les niveaux enfants (recursion)
                if ($child->find('ul,ol', 0) !== null) { // v3.0
                    $niv++;
                    $this->update_list($child, $niv);
                    $niv--;
                }
            }
            $child = $child->next_sibling();
        }

        return $node;
    }

    /*
     * function get_elem_style($elem, ...$args)
     * @$elem : type de balise à laquelle on attribue les $args
     * @args : plusieurs chaines contenant les attributs pour styler l'élément
     *
     */
    public function get_elem_style($elem, ...$args)
    {
        $elem = strtolower($elem);
        // ajout de la valeur par défaut selon le type de liste
        if ($elem !== 'li') {
            // verif attribution type de liste si UL/OL
            if (isset($this->options[$elem . '-default'])) { // v2.8
                array_unshift($args, $this->options[$elem . '-default']);
            }
            $varcss['position'] = '';
        }

        do {
            $args[0] = html_entity_decode($args[0]);
            $infos = array_map('trim', explode(';', $args[0]));
            array_shift($args);
            foreach ($infos as $info) {
                $info_min = trim(strtolower($info));
                if (empty($info)) {
                    continue;
                } elseif ($info == 'reset') {
                    // classe pour réinitialer les varcss
                    $reset = true;
                } elseif (isset($this->kwc_pref[$info_min])) {
                    // un motclé pour contenu
                    if (count($infos) > 1) {
                        // reanalyser les autres arguments après ceux du motclé pour priorité saisie user
                        $infos2 = $infos;
                        unset($infos2[array_search($info, $infos)]);
                        array_unshift($args, implode(';', $infos2));
                    }
                    array_unshift($args, $this->kwc_pref[$info_min]);
                } elseif (mb_strlen($info) <= 3) {
                    // un ou deux caractères littéraux
                    $varcss['content'] = '\'' . $info . '\'';
                } elseif (strpos($info, ':') !== false) {
                    // style css pour les propriétés ayant une varcss
                    list($k, $v) = explode(':', $info, 2);
                    $k = strtolower($k);
                    if (in_array($k, $this->varStyle)) {
                        $varcss[$k] = $v;
                    } elseif (in_array($k, $this->varStyleString)) {
                        $v = trim($v, '\'\"');
                        $varcss[$k] = '\'' . $v . '\'';
                    } else {
                        $this->msg_error('ERR_STYLE', $k);
                    }
                } elseif (strpos($info, '.') !== false) {
                    // une image (par defaut dans le dossier icon de l'action)
                    if (dirname($info) === '.') {
                        $info = str_replace('\\', '/', $this->actionPath) . 'icon/' . $info;
                    }
                    $varcss['content'] = 'url(\'' . $info . '\')';
                } elseif (substr($info, 0, 2) === 'Ux') {
                    // un caractère Unicode ou entité HTML sous la forme Ux..
                    $varcss['content'] = '\'' . str_replace('Ux', '&#x', $info) . '\'';
                } elseif ($info[0] === '\\') {
                    // un caractère font Awesome
                    $varcss['content'] = '\'' . $info . '\'';
                    $varcss['font-family'] = '\''.$this->options['fonticon'].'\'';
                    // fix 5.2
                    $varcss['font-weight'] = '900';
                    $varcss['color'] = 'var(--upli-color)';
                    $varcss['font-size'] = 'var(--upli-font-size)';
                } elseif (isset($this->kwc_formula[$info_min])) {
                    // formule pour compteurs
                    $varcss['content'] = $this->kwc_formula[$info_min];
                } elseif (substr($info, 0, 2) === 't-') {
                    // une couleur texte
                    $varcss['color'] = 'var(--' . substr($info, 2) . ')';
                } elseif (substr($info, 0, 3) === 'bg-') {
                    // une couleur de fond
                    $varcss['background'] = 'var(--' . substr($info, 3) . ')';
                } elseif (in_array($info_min, $this->valid_type)) {
                    // type standard
                    $varcss['type'] = $info_min;
                    $varcss['content'] = '';
                } else {
                    $this->msg_error('ERR_ATTRIBUT : '. $info); // v52
                }
            }
        } while (count($args) > 0);
        // la classe pour UL/OL
        // | content='' | content<>''
        // ni bg, ni outside | upli-type | upli-bg-inside
        // outside | upli-type + pos | upli-bg-outside
        // bg + outside | upli-type * | upli-bg-outside
        // bg | upli-type * | upli-bg-inside
        // * : bg non pris en compte

        if ($elem !== 'li') {
            $out['class'] = 'upli-type';
            $varcss['type'] = (empty($varcss['type'])) ? 'none' : $varcss['type'];
            if (! empty($varcss['content'])) {
                unset($varcss['type']);
                $out['class'] = ($varcss['position'] == 'outside') ? 'upli-bg-outside' : 'upli-bg-inside';
            }
            if (isset($reset)) {
                $out['class'] .= ' upli-reset';
            }
        }
        // Préparation pour retour
        $out['style'] = '';
        foreach ($varcss as $k => $v) {
            if ($v !== '') {
                $out['style'] .= '--upli-' . $k . ':' . $v . ';';
            }
        }
        return $out;
        // end get_elem_style
    }
}
