<?php

/**
 * liste déroulante d'options
 *
 * syntaxe : {up form-select=action_onchange}liste options CSV (label;value){/up form-select}
 *
 * @author   LOMART
 * @version  UP-1.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Expert
 */

/*
 * v3.0 - ajout option size pour nombre de lignes affichées
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class form_select extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => 'url', // mot-clé (url, url-blank) ou argument pour onchange ou prefset
            'file' => '', // fichier CSV pour contenu
            'separator' => ';', // séparateur colonnes du fichier CSV
            'size' => '1', // nombre de lignes affichées (6) ou hauteur de la liste (ex: 50vh).
            'no-content-html' => 'en=content not found : %s;fr=contenu non trouvé : %s', // message erreur. %s:nom fichier
            /* [st-label] Label avant le select */
            'label' => '', // texte ajouté au dessus de la liste
            'label-style' => '', // classes et style inline pour le label
            /* [st-btn] bouton après le select */
            'btn' => '', // texte du bouton pour valider le choix dans la liste. active raccourci: enter et double-clic sur liste
            'btn-style' => '', // classes et style inline pour le bouton
            /* [st-css] Styles CSS */
            'id' => '', // Identifiant
            'style' => '', // classes et styles
            'css-head' => '', // style ajouté dans le HEAD de la page
            'filter' => '' // conditions. Voir doc action filter
        );

        // ======> fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        $id = $options['id'];

        // ========================
        // === ID et Style
        // ========================
        // --- CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);
        // --- Attributs
        $attr_select['id'] = $id;
        // --- size en nombre de lignes ou en height
        $size = UpHelper::ctrl_unit($this,$options['size'], ',vh,px,rem');
        if ((int) $size == $size) {
            $attr_select['size'] = strval($options['size']);
        } else {
            $attr_select['size'] = '99';
            $attr_select['style'] = 'height:' . $size;
        }
        UpHelper::get_attr_style($this,$attr_select, $options['style']);

        // ========================
        // === recup du contenu CSV
        // ========================
        // 1 - le texte entre les shortcodes (sans html)
        $content = $this->content;
        // 2 - le contenu d'un fichier
        $filename = $options['file'];
        if ($content == '' and $filename != '') {
            $filename = UpHelper::get_url_absolute($this,$filename);
            $content = file_get_contents($filename);
        }

        // retour sans prévenir, le contenu peut être envoyé par une autre action
        // voir no-content-html si argument vide !!!!!!
        if ($content == '') {
            return sprintf($options['no-content-html'], $filename);
        }

        // === Analyse et nettoyage du contenu
        // ===================================

        // 5.3 : if content has been created by another action, remove divs
        $content = preg_replace("/(<div[^>]*>|<\/div>)/i", PHP_EOL, $content);

        $content = UpHelper::get_content_csv($this,$content, false);
        // === analyse et nombre de colonnes du tableau

        foreach ($content as $key => $val) {
            if (! trim($val) == '') {
                $csv[$key] = str_getcsv($val . $options['separator'], $options['separator'], '"', '\\'); // 5.2
            }
        }
        // ============================
        // traitement pour valeur retour
        // ============================
        switch (strtolower($options[__class__])) {
            case 'url':
            case 'url-self':
                // $attr_select['onchange'] = 'document.location.href = this.value;';
                $action = 'document.location.href = document.getElementById(\'' . $id . '\').options[document.getElementById(\'' . $id . '\').selectedIndex].value';
                break;
            case 'url-blank':
                // $attr_select['onchange'] = 'if (this.value) window.open(this.value)';
                $action = 'window.open(document.getElementById(\'' . $id . '\').options[document.getElementById(\'' . $id . '\').selectedIndex].value)';
                break;
            default:
                $action = $options[__class__];
        }

        if ($options['btn']) {
            $attr_btn['onclick'] = $action;
        } else {
            $attr_select['onchange'] = $action;
        }

        // // ============================
        // // accesskey enter sur btn
        // // ============================
        $js = '';
        if ($options['btn']) {
            $js .= 'window.addEventListener("keypress", ({key}) => {';
            $js .= 'if (key == "Enter" && document.activeElement.id=="' . $id . '")  { ';
            $js .= 'document.getElementById("btn' . $id . '").click();';
            $js .= '}';
            $js .= '});';
            $js .= 'document.getElementById("' . $id . '").addEventListener("dblclick", () => {';
            $js .= 'document.getElementById("btn' . $id . '").click();';
            $js .= '});';
            $js = UpHelper::load_js_code($this,$js, false);
        }

        // ============================
        // formatage HTML pour retour
        // ============================

        // --- le label avant select
        if ($options['label']) {
            $attr_select['name'] = $options['id'];
            // $attr_select['autofocus'] = '1';
            $attr_label['for'] = $id;
            UpHelper::get_attr_style($this,$attr_label, $options['label-style']);
            $html[] = UpHelper::set_attr_tag($this,'label', $attr_label, UpHelper::get_bbcode($this,$options['label']));
        }

        // --- le select
        $html[] = UpHelper::set_attr_tag($this,'select', $attr_select, false);
        $selected = ' selected';
        foreach ($csv as $val) {
            $html[] = sprintf('<option value="%s"' . $selected . '>%s</option>', $val[1], $val[0]);
            $selected = '';
        }
        $html[] = '</select>';

        // --- Bouton pour valider la sélection
        if ($options['btn']) {
            $attr_btn['id'] = 'btn' . $id;
            $attr_btn['type'] = 'button';
            $attr_btn['accesskey'] = 'enter';
            $attr_btn['value'] = $options['btn'];
            UpHelper::get_attr_style($this,$attr_btn, $options['btn-style']);
            $html[] = UpHelper::set_attr_tag($this,'input', $attr_btn);
        }

        $html[] = $js;

        return implode(PHP_EOL, $html);
    }

    // -- run
}

//-- class
