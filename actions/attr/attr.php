<?php

/**
 * Ajoute des attributs à la première balise interne
 *
 * syntaxe {up attr | class=bg-yellow}< p>texte< /p>{/up attr}
 * syntaxe {up attr | class=bg-yellow | tag=img}<p>< img src="image.jpg" ></p>{/up attr}
 *
 * @version  UP-2.6
 * @author Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    HTML
 *
 */
defined('_JEXEC') or die;

class attr extends upAction {

    function init() {
        return true;
    }

    function run() {

        // si cette action a obligatoirement du contenu
        if (!$this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // classe(s) et/ou style
            'tag' => '', // balise recherchée, sinon la première
            /*[st-attr] Toutes les autres clés sont considérées comme des attributs */
            'xxx' => 'yyy',  // couple attribut-valeur. ex: title=le titre, href=//google.fr
            /*[st-annexe]options secondaires*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc interne
            'style' => '', // style inline pour bloc interne
            'css-head' => '', // style ajouté dans le HEAD de la page
        );

        // On accepte toutes les options. Il faut les ajouter avant contrôle
        foreach (array_diff_key($this->options_user, $options_def) AS $key => $val) {
            $options_def[$key] = '';
        }

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // si attr!='', c'est un style/classe
        $this->add_str($options['style'], $options[__class__], ';');

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === la première balise interne
        // $tag : la balise complete
        // $tagname : nom de la balise
        // $tagattr : les attributs trouvés dans la balise

        $tagname = (empty($options['tag'])) ? '\b[a-zA-Z]*\b' : $options['tag'];
        $regex = '#<' . $tagname . '(.*)>#Ui';

        if (preg_match($regex, $this->content, $match) === false)
            return $this->content;

        // === c'est OK
        $tag = $match[0];
        $tagname = $options['tag'];
        $pos = strpos($match[1], ' ');
        if ($pos === false) {
            // pas d'attribut
            $tagname = ($tagname) ? $tagname : $match[1];
            $attrList = '';
            $tagattr = array();
        } else {
            $tagname = ($tagname) ? $tagname : substr($match[1], 0, $pos);
            $attrList = substr($match[1], $pos);
            $attrList = str_replace('\'', '"', $attrList);
            // on récupère les attributs existants
            $regex = '#(.*)="(.*)"#U';
            if (preg_match_all($regex, $attrList, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) { //2.9
                    $tagattr[$matches[1][$i]] = $matches[2][$i];
                }
            }
        }

        // === le code HTML
        // -- toutes les options sont des attributs sauf attr, tagname, css-head, class et style
        $sc_attr = $options;
        unset($sc_attr[__class__]);
        unset($sc_attr['tagname']);
        unset($sc_attr['css-head']);
        unset($sc_attr['class']);
        unset($sc_attr['style']);
        unset($sc_attr['xxx']);

        // -- on fusionne les attributs de style
        $this->get_attr_style($tagattr, $options['class'], $options['style']);
        // -- on remplace les attributs de la balise
        foreach ($sc_attr AS $k => $v) {
            $tagattr[$k] = $v;
        }
        // on construit la nouvelle balise
        $newTag = $this->set_attr_tag($tagname, $tagattr);

        // code en retour
        $content = str_replace($tag, $newTag, $this->content);

        return $content;
    }

// run
}

// class
