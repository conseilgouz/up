<?php

/**
 * affiche de 1 à 6 blocs enfants sur une même ligne
 *
 * syntaxe 1 : {up cell=x1-x2}contenu avec 2 blocs enfants{/up cell}
 * syntaxe 2 : {up cell=x1-x2}contenu cell-1 {====} contenu cell-2{/up cell}
 *
 * x1-x2 sont les largeurs sur la base d'une grille de 12 colonnes
 * exemple cell=6-6 pour 2 colonnes égales.
 * On utilise les largeurs de la classe UP-width
 *
 * @author  Lomart
 * @version UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Layout-static
 *
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class cell extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // aucune
    }

    function run() {

        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
          __class__ => '12', // nombre de colonnes
          'mobile' => '', // nombre de colonnes sur petit écran
          'tablet' => '', // nombre de colonnes sur moyen écran
          /* [st-child] style des blocs enfants (colonnes) */
          'class-*' => '', // class pour tous les blocs colonnes. sinon voir class-1 à class-6
          'style-*' => '', // style inline pour tous les blocs colonnes. sinon voir style-1 à style-6
          /*[st-annexe]style et options secondaires */
          'id' => '', // identifiant
          'class' => '', // class bloc principal
          'style' => '', // style inline bloc parent
          'filter' => '', // conditions. Voir doc action filter  (v1.8)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);
        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // -- ajout options utilisateur dans la div principale
        $outer_div['class'] = 'cell-row';
        UpHelper::add_class($this,$outer_div['class'], $options['class']);
        $outer_div['style'] = $options['style'];

        // ======== les styles des colonnes
        // -- taille des colonnes (version rwd)
        $col[0] = array_map('intval', explode('-', $options[__class__]));
        $tmp = UpHelper::str_append($this,$options['mobile'], '0-0-0-0-0-0', '-');
        $col[1] = array_map('intval', explode('-', $tmp));
        $tmp = UpHelper::str_append($this,$options['tablet'], '0-0-0-0-0-0', '-');
        $col[2] = array_map('intval', explode('-', $tmp));
        // le nombre de colonnes est défini par col
        $nbcol = count($col[0]);

        // ajout des styles pour les colonnes
        // note: le style général est toujours appliqué
        // exemple: bordure identique pour toutes les colonnes + fond pour une spécifique
        for ($i = 0; $i < $nbcol; $i++) {
            $bloc[$i]['class'] = 'cell w' . $col[0][$i];
            if ($options['mobile']) {
                UpHelper::add_class($this,$bloc[$i]['class'], 'ws' . $col[1][$i]);
            }
            if ($options['tablet']) {
                UpHelper::add_class($this,$bloc[$i]['class'], 'wm' . $col[2][$i]);
            }
            UpHelper::add_class($this,$bloc[$i]['class'], $options['class-*']);
            UpHelper::add_class($this,$bloc[$i]['class'], $options['class-' . ($i + 1)]);
            $bloc[$i]['style'] = $options['style-*'];
            UpHelper::add_str($this,$bloc[$i]['style'], $options['style-' . ($i + 1)], ';');
        }

        // RECUPERATION & ANALYSE CONTENU
        // si les 2 colonnes ne sont pas séparées par {====}
        // on prend les maxi 6 premiers blocs enfants
        if (UpHelper::ctrl_content_parts($this,$this->content) === false) {
            // === analyse structure HTML du content
            $dom = new domDocument;
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $this->content);
            $xpath = new DOMXpath($dom);
            $nodes = $xpath->query('/html/body/*');

            $i = 0;
            foreach ($nodes as $node) {
                if (isset($bloc[$i % $nbcol]['class'])) {
                    $tmp = @$nodes->item($i)->getAttribute('class');
                    $nodes->item($i)->setAttribute('class', UpHelper::str_append($this,$tmp, $bloc[$i % $nbcol]['class']));
                }
                if (isset($bloc[$i % $nbcol]['style'])) {
                    $tmp = @$nodes->item($i)->getAttribute('style');
                    $nodes->item($i)->setAttribute('style', UpHelper::str_append($this,$tmp, $bloc[$i % $nbcol]['style'], ';'));
                }
                $i++;
            }

            $this->content = $dom->saveHTML($dom->documentElement);
            $this->content = preg_replace('~<(?:/?(?:html|head|body))[^>]*>\s*~i', '', $this->content);
        } else { // séparation par {============}
            // recup texte des colonnes sans le tag P ajouté par éditeur
            $coltxt = UpHelper::get_content_parts($this,$this->content);
            // mise en forme
            $this->content = '';
            for ($i = 0; $i < $nbcol; $i++) {
                $this->content .= UpHelper::set_attr_tag($this,'div', $bloc[$i]);
                $this->content .= $coltxt[$i];
                $this->content .= '</div>';
            }
        }

        // === le code HTML en retour
        $out = UpHelper::set_attr_tag($this,'div', $outer_div);
        $out .= $this->content;
        $out .= '</div>';

        return $out;
    }

// run
}

// class
