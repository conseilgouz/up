<?php

/**
 * Affiche une grosse info-bulle lors d'un clic sur un élément.
 *
 * syntaxe {up popover=texte appel en bbcode}contenu popover{/up popover}
 *
 * @author   LOMART
 * @version  UP-2.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/markembling/jquery-gpopover-plugin" target"_blank">script jquery-gpopover-plugin de markembling</a>
 * @tags    Editor
 *
 * */

/*
 * v3.0 : modif JS. L'affichage du popover donne le focus au premier élément si focusable
 */
defined('_JEXEC') or die();

class popover extends upAction
{
    /**
     * charger les ressources communes à toutes les instances de l'action
     */
    public function init()
    {
        $this->load_file('jquery.gpopover.css');
        $this->load_file('jquery.gpopover.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // bbcode pour élément déclencheur
            /* [st-pop] Style de la fenêtre popup */
            'max-height' => '', // hauteur maxi du popover (ex: 90vh)
            'pop-bg-color' => '', // couleur de fond du popover
            'pop-class' => '', // classe du popover
            'pop-style' => '', // style inline du popover
            /* [st-btn] Style de l'élément déclencheur */
            'tag' => 'button', // balise pour élément déclencheur
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) pour element déclencheur
            'style' => '', // style inline pour element déclencheur
            'css-head' => '', // style ajouté dans le HEAD de la page
            'filter' => '' // conditions. Voir doc action filter
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour la configuration du popover */
            'width' => 250, // largeur du popover en px
            'top' => false, // true : popover au-dessus du trigger, sinon au-dessous
            'arrow' => true, // affichage de la fleche
            'offset' => 0, // décalage entre trigger et popover
            'viewportSideMargin' => 10, // Espace à laisser sur le côté lorsqu'il est contre le bord de la fenêtre (pixels)
            'fadeInDuration' => 65, // Durée de l'animation de fondu enchaîné popover (ms)
            'fadeOutDuration' => 65, // Durée de l'animation de fondu sortant du popover (ms)
            'preventHide' => true, // Empêcher le masquage lors d'un clic dans le popover
            'onShow' => '', // fonction à exécuter lorsque le popover est affiché. c'est l'élément déclencheur et le premier argument passé à la fonction est l'élément popover (tous deux enveloppés dans jQuery).
            'onHide' => '' // Callback à exécuter lorsque le popover est masqué. Identique à onShow.
        );

        // affecter l'option principale à une option JS
        // $this->options_user['xxx'] = $this->options_user[__class__];
        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }
        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);
        if ($options['pop-bg-color']) {
            $css = '#id-content{background-color:' . $options['pop-bg-color'] . '}';
            $css .= '#id-content > .gpopover-arrow{border-bottom-color:' . $options['pop-bg-color'] . '}';
            $css .= '#id-content > .gpopover-arrow.bottom{border-top-color:' . $options['pop-bg-color'] . '}';
            $this->load_css_head($css);
        }

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options);

        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").gpopover(';
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $attr_trigger['id'] = $options['id'];
        $attr_trigger['data-popover'] = $options['id'] . '-content';
        $this->get_attr_style($attr_trigger, $options['class'], $options['style']);
        $trigger_content = $this->get_bbcode($options[__class__]);
        // -- attribut contenu
        $attr_content['id'] = $options['id'] . '-content';
        $attr_content['class'] = 'gpopover';
        if ($options['max-height']) {
            $this->content = '<div style="max-height:' . $options['max-height'] . ';overflow:auto">' . $this->content . '</div>';
        }
        $this->get_attr_style($attr_content, $options['pop-class'], $options['pop-style']);

        // ==== code en retour
        // ---- le déclencheur
        $out['tag'] = $this->set_attr_tag($options['tag'], $attr_trigger, $trigger_content);
        // ---- le contenu popover
        $out['after'] = $this->set_attr_tag('div', $attr_content, $this->content);

        return $out;
    }

    // run
}

// class
