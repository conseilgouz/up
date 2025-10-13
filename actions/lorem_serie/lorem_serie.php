<?php

/**
 * Retourne une suite d'éléments comme arguments d'une action
 *
 * syntaxe {up lorem-serie=liste_or_num_alpha_alphanum | maxi=x}
 *
 * @version  UP-5.2
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   <a href="" target"_blank">script xxx de xxx</a>
 * @tags     Expert
 *
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class lorem_serie extends upAction
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
            __class__ => '',   // liste séparateur virgule ou mot-cle : NUM, ALPHA, ALPHANUM
            'maxi' => 1,       // nombre d'éléments retournés
            'format' => '',    // fonction PHP : strtolower, strtoupper, ucfirst
            /* [st-main] Bloc principal */
            'main-tag' => '0',  // balise du bloc principal. Aucun par défaut
            'main-id' => '',    // ID sous la forme up-idArticle-posShortcode
            'main-style' => '', // classe(s) ou style inline pour bloc
            /* [st-item] les éléments retournés */
            'item-tag' => 'div', // balise des items. 0 pour valeurs brutes séparées par item-sep
            'item-style' => '',  // classe(s) ou style inline pour l'item
            'item-id' => '',     // préfixe du compteur pour identifier l'item
            'item-sep' => ';',   // si item-tag=0, le séparateur entre les items
            /* [st-divers] Divers */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $maxi = $options['maxi'];
        if ((int) $maxi <= 0) {
            $this->msg_inline('Le paramètre maxi doit être un entier positif');
            return;
        }

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === Contenu
        $out = array();
        switch (strtoupper($options[__class__])) {
            case 'NUM':
                for ($i = 1; $i <= $options['maxi']; $i++) {
                    $out[] = $i;
                }
                break;
            case 'ALPHA':
                $maxi = min($options['maxi'] + 96, 122);
                for ($i = 97; $i <= $maxi; $i++) {
                    $out[] = chr($i);
                }
                break;

            case 'ALPHANUM':
                $lang = substr(Factory::getApplication()->getLanguage()->getTag(), 0, 2);
                if ($lang == 'fr') {
                    $num = explode(',', 'un,deux,trois,quatre,cinq,six,sept,huit,neuf,dix,onze,douze,treize,quatorze,quinze,seize,dix-sept,dix-huit,dix-neuf,vingt,vingt-et-un,vingt-deux,vingt-trois,vingt-quatre,vingt-cinq,vingt-six,vingt-sept,vingt-huit,vingt-neuf,trente');
                } else {
                    $num = explode(',', 'one,two,three,four,five,six,seven,eight,nine,ten,eleven,twelve,thirteen,fourteen,fifteen,sixteen,seventeen,eighteen,nineteen,twenty,twenty-one,twenty-two,twenty-three,twenty-four,twenty-five,twenty-six,twenty-seven,twenty-eight,twenty-nine,thirty');
                }
                $maxi = min($maxi, 30);
                for ($i = 0; $i < $maxi; $i++) {
                    $out[] = $num[$i];
                }
                break;

            default:
                $out = array_map('trim', explode(',', $options[__class__]));

        }
        if ($options['format']) {
            $out = array_map(strtolower($options['format']), $out);
        }

        // --- style bloc principal
        $attr_main['id'] = $options['main-id'];
        $this->get_attr_style($attr_main, $options['main-style']);
        // on force le bloc principal si nécessaire
        $main_tag = ($options['main-tag'] == 0) ? false : $options['main-tag'];
        if ($main_tag === false && $options['main-style']) {
            $main_tag = 'div';
        }

        // --- style bloc item
        $attr_item = array();
        $item_id = (empty($options['item-id'])) ? false : $options['item-id'];
        $item_tag = (empty($options['item-tag'])) ? false : $options['item-tag'];
        $this->get_attr_style($attr_item, $options['item-style']);

        // === HTML

        // code en retour
        if ($item_tag === false) {
            // valeurs brutes
            $html = implode($options['item-sep'], $out);
        } else {
            // valeurs mises en forme
            if ($main_tag) {
                $html[] = $this->set_attr_tag($main_tag, $attr_main);
            }
            $cpt = 0;
            foreach ($out as $item) {
                if ($item_id) {
                    $cpt++;
                    $attr_item['id'] = $item_id . $cpt;
                }
                $html[] = $this->set_attr_tag($item_tag, $attr_item, $item);
            }
            if ($main_tag) {
                $html[] = "</$main_tag>" ;
            }
            $html = implode(PHP_EOL, $html);
        }

        return $html;
    }

    // run
}

// class
