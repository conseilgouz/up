<?php

/**
 * retourne la valeur d'une couleur de la feuille de style de UP
 *
 * syntaxe {up color=UP-COLOR-NAME}
 *
 * @version  UP-2.5
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    HTML
 *
 */
defined('_JEXEC') or die;

/*
 * La valeur est lue dans le fichier up/assets/color.ini
 */

class color extends upAction {

    function init() {
        return true;
    }

    function run() {


        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // nom UP de la couleur (français ou anglais) ou de la variable CSS (--red)
            'default' => '#000', // couleur retournée si nom couleur inexistant
            'info' => 0 // affiche la liste des couleurs avec leurs valeurs
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // --- cas d'une variable CSS (case sensitive)
        if (substr($options[__class__], 0, 2) === '--') {
            return 'var(' . strtolower($options[__class__]) . ')';
        }
        // --- cas d'une couleur UP
        // traite en fin car l'option info est prioritaire
        $colorname = strtoupper($options[__class__]);
        // liste des couleurs avec tous les noms en MAJ
        $colorlist = $this->load_inifile($this->upPath . 'assets/colorname.ini');

        // === liste des couleurs (sans retour valeur)
        if ($options['info']) {
            // idem $colorlist avec tous les noms en min/maj
            $colorlistRef = $this->load_inifile($this->upPath . 'assets/colorname-ref.ini');
            // les noms de base en francais (min/maj) avec les couleurs non modifiées pour le site
            $colorlistOrig = $this->load_inifile($this->actionPath . 'colorname-orig.ini');
            $info = '<div class="fg-row fg-auto-5 fg-auto-m3 fg-auto-s2 colorgamme">';
            $this->load_css_head('.colorgamme > div [border-top:16px solid red; border-bottom:16px solid red; padding: 10px; text-align:center ]');
            $name1 = array_key_first($colorlistRef);
            $color1 = reset($colorlistRef);
            foreach ($colorlistRef AS $name => $color) {
                if ($color != $color1) {
                    // fin pour une couleur
                    $borderTopColor = 'border-top-color:';
                    $borderTopColor .= (empty($colorlistRef[$name1])) ? 'transparent' : $colorlistOrig[$name1];
                    $name2 = ($name2 == '') ? $name1 : $name2;
                    $borderTopColor .= ($name1 == 'c0') ? '; color:red' : '';
                    $borderBottomColor = 'border-bottom-color:var(--' . $name2 . ',' . $colorlistRef[$name2] . ')';
                    $info .= '<div class="bg-' . $name1 . '" style="' . $borderTopColor . ';' . $borderBottomColor . '">';
                    $info .= $name1 . '<br><i>' . $name2 . '</i><br>' . $colorlistRef[$name1];
                    $info .= '</div>';
                    // reinit
                    $color1 = $color;
                    $name1 = $name;
                    $name2 = '';
                } elseif ($name1 != $name) {
                    $color2 = $color;
                    $name2 = $name;
                }
            }

            // la derniere
            $borderTopColor = 'border-top-color:';
            $borderTopColor .= (empty($colorlistRef[$name1])) ? 'transparent' : $colorlistOrig[$name1];
            $name2 = ($name2 == '') ? $name1 : $name2;
            $borderTopColor .= ($name1 == 'c0') ? '; color:red' : '';
            $borderBottomColor = 'border-bottom-color:var(--' . $name2 . ',' . $colorlistRef[$name2] . ')';
            $info .= '<div class="bg-' . $name1 . '" style="' . $borderTopColor . ';' . $borderBottomColor . '">';
            $info .= $name1 . '<br><i>' . $name2 . '</i><br>' . $colorlistRef[$name1];
            $info .= '</div>';
            // la fin
            $info .= '</div>';

            return $info;
        }

        // === retour
        if (isset($colorlist[$colorname])) {
            return $colorlist[$colorname];
        } else {
            $this->msg_error($this->lang('fr=Couleur inconnue:; en=Unknown color:') . $options[__class__]);
            return $options['default'];
        }
    }

// run
}

// class
