<?php

/**
 * permet de créer une entité HTML (balise) avec classe(s), style et attribut sans passer en mode code
 *
 * <b>Syntaxe 1 :</b>
 * {up html=div | class=foo | id=x123}contenu{/up html}
 * --> < div id="x123" class="foo">contenu< /div>
 *
 * <b>Syntaxe 2 :</b>
 * {up html=img | class=foo;border:1px red solid | src=images/img.jpg}
 * --> < img class="foo" style="1px red solid" src="images/img.jpg" >
 * note: toutes les options sont considérées comme des attributs de la balise
 *
 * <b>Syntaxe 3 :</b>
 * {up html=h1.foo.xx} équivaut à {up html=h1 | class=foo xx}
 *
 * @version  UP-1.0
 * @author   LOMART 2017-08
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    HTML
 */

/*
 * v1.1  fermeture auto balise
 * v1.7  gestion des balises auto-fermantes
 * v1.95 prise en charge class & style non différenciés
 * v2.2  possibilité saisie rapide {up html=h1.foo.xx} équivaut à {up html=h1 | class=foo xx}
 */
defined('_JEXEC') or die;

class html extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // contenu non obligatoire, ex: IMG
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => 'div', // balise html
            'id' => '', // ID spécifique
            'class' => '', // classe(s)
            'style' => '', // style inline
            'xxx' => 'yyy'  // couple attribut-valeur. ex: title=le titre, href=//google.fr
        );

        // On accepte toutes les options. Il faut les ajouter avant contrôle
        foreach (array_diff_key($this->options_user, $options_def) as $key => $val) {
            $options_def[$key] = '';
        }

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options[__class__] = ($options[__class__] == 1) ? 'div' : $options[__class__];

        // === le code HTML
        // -- toutes les options sont des attributs sauf html, class et style
        $outer_div = $options;
        unset($outer_div[__class__]);
        unset($outer_div['class']);
        unset($outer_div['style']);
        unset($outer_div['xxx']);
        // -- analyse et ajout class et style
        $this->get_attr_style($outer_div, $options['class'], $options['style']);

        // === v2.2 - saisie rapide balise et ses classes. ex: html=h2.t-red.bg-yellow
        $tmp = explode('.', $options[__class__]);
        $options[__class__] = $tmp[0];
        unset($tmp[0]);
        foreach ($tmp as $e) {
            $outer_div['class'] .= ' ' . $e;
        }

        //
        // -- le code en retour
        //
        if ($this->content) {
            $out = $this->set_attr_tag($options[__class__], $outer_div, $this->content);
        } else {
            $tag_not_close = array_map('trim', explode(',', 'area, br, hr, img, input, link, meta, param'));
            $close = (array_search($options[__class__], $tag_not_close) === false);
            $out = $this->set_attr_tag($options[__class__], $outer_div, $close);
        }

        return $out;
    }

    // run
}

// class
