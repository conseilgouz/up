<?php

/**
 * Conversion d'un contenu au format CSV en liste de définition (DL/DT/DD)
 *
 * 1/  {up csv2def=emplacement-fichier} // le contenu est lu dans un fichier
 * 2/  {up csv2def}
 *        [=item=]definition
 *        [=item1; "item;2"; ...=]
 * 		  definition1
 *        {===}
 *        definition 2
 *     {/up csv2def}
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Layout-static
 */
defined('_JEXEC') or die;

class csv2def extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('csv2def.css');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin vers fichier à afficher
            'separator' => ';', // séparateur des colonnes (fichier uniquement)
            'HTML' => '0', // pour fichier (contenu CSV): 0=affiche le rendu, 1=affiche le code ou liste des tags conservés (strip_tags)
            'model' => 'stack', // stack: empile par défaut. flex: sur 2 colonnes
            /* [st-dl] Style du bloc liste de définition */
            'class' => '', // classe(s) pour la balise DL
            'style' => '', // style inline pour la balise DL
            /* [st-term] Style des termes définis (DT)*/
            'term-class' => '', // classe(s) pour le bloc des termes
            'term-style' => '', // style pour le bloc des termes
            'term-style-separator' => '', // style pour séparer les différents termes
            /* [st-def] Style des définitions (DD) */
            'def-class' => '', // classe(s) pour le bloc des definitions
            'def-style' => '', // style pour le bloc des définitions
            'def-style-separator' => '', // style pour séparer les différentes définitions
            /* [st-flex] ne concerne que le mode FLEX */
            'flex-vgap' => '10', // espace vertical (px) entre les blocs dt/dd
            'flex-hgap' => '10', // espace horizontal (px) entre les blocs dt et dd
            'flex-term-width' => '25', // largeur en pourcentage de la colonne des termes
            /* [st-annexe] options secondaires */
            'id' => '', // identifiant
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $id = '#' . $options['id'];

        // === css-head
        $css[] = $options['css-head'];

        // === recup du contenu CSV
        $csv = array();
        if ($this->content == '') {
            // le contenu est dans un fichier
            // sous la forme term="def" (sur 1 ligne pas de multi-term et multi-def)
            $content = $this->get_html_contents($options[__class__]);
            $content = nl2br(trim($content));
            $content = explode('<br />', $content);
            // === Contenu et style de la liste
            foreach ($content as $key => $val) {
                if (trim($val)) {
                    list($dt, $dd) = str_getcsv($val . $options['separator'], $options['separator'], '"', '\\');
                    $csv[$key]['dt'][] = $dt;
                    $csv[$key]['dd'][] = $this->clean_HTML($dd, $options['HTML']);
                }
            }
        } else {
            // le contenu est le texte entre les shortcodes
            // sous la forme [term1;term2]def1{===}def2
            $content = $this->content;
            // 2 - le contenu d'un fichier
            $filename = $options[__class__];
            if ($content == '' and $filename != '') {
                $content = $this->get_html_contents($filename);
            }
            if ($content == '') {
                $content = $this->msg_inline('csv2def - content not found ' . $filename);
            }
            // 3 - conversion en tableau par terme-definition
            // attention, on peut avoir term=]def</p>
            $res = preg_split('#(?:\<p\>)?\[\=|\=\](?:\</p\>)?#', $content);
            // 4 - eclater en terme(s) et def(s)
            $key = 0;
            for ($i = 1; $i < count($res); $i += 2) {
                // le terme
                $str = strip_tags($res[$i], '<b><a><strong><i><em><u><mark><code><img><span>');
                $csv[$key]['dt'] = str_getcsv($str, $options['separator'], '"', '\\');
                // la definition
                $str = $this->get_content_parts($res[$i + 1]);
                $csv[$key]['dd'] = $str;
                $key++;
            }
        }

        // === Style de la liste
        // -- DL
        $attr_main['id'] = $options['id'];
        $attr_main['style'] = $options['style'];
        $attr_main['class'] = 'csv2def ' . $options['model'];
        $this->add_class($attr_main['class'], $options['class']);

        // -- DT
        $attr_dt['class'] = $options['term-class'];
        $attr_dt['style'] = $options['term-style'];
        if (trim($options['term-style-separator'])) {
            $css[] = 'dl' . $id . ' > div > dt{' . $options['term-style-separator'] . '}';
        }

        // -- DD
        $attr_dd['class'] = $options['def-class'];
        $attr_dd['style'] = $options['def-style'];  /* A METTRE DANS HEAD */
        if ($options['def-style-separator']) {
            $css[] = 'dl' . $id . ' > div > dd{' . $options['def-style-separator'] . '}';
        }
        // -- espace entre les blocs

        $wTerm = (int) $options_def['flex-term-width'];
        $vgap = (int) $options_def['flex-vgap'];
        $hgap = (int) $options_def['flex-hgap'];

        if ((int) $options['flex-vgap'] <> $vgap or (int) $options['flex-hgap'] <> $hgap or (int) $options['flex-term-width'] <> $wTerm) {
            $wTerm = ((int) $options['flex-term-width'] <> $wTerm) ? (int) $options['flex-term-width'] : $wTerm;
            $vgap = ((int) $options['flex-vgap'] <> $vgap) ? (int) $options['flex-vgap'] . 'px' : $vgap . 'px';
            $hgap = ((int) $options['flex-hgap'] <> $hgap) ? (int) $options['flex-hgap'] . 'px' : $hgap . 'px';
            $css[] = 'dl' . $id . '{padding:' . $vgap . ' ' . $hgap . ' 0 ' . $hgap . '}';
            $css[] = 'dl' . $id . ' > *:nth-child(odd){';
            $css[] = 'margin:0 ' . $hgap . ' ' . $vgap . ' 0;width:' . $wTerm . '%}';
            $css[] = 'dl' . $id . ' > *:nth-child(even){';
            $css[] = 'margin:0 0 ' . $vgap . ' 0;width:calc(' . (100 - $wTerm) . '% - ' . $hgap . ')}';
        }
        // -- envoi du CSS dans le head
        if (isset($css)) {
            $this->load_css_head(implode(PHP_EOL, $css));
        }

        // =================================================== formattage HTML
        $html[] = $this->set_attr_tag('dl', $attr_main);

        // -- contenu table
        foreach ($csv as $key => $lign) {
            switch (count($lign['dt'])) {
                case 0:
                    $html[] = $this->set_attr_tag('dt', $attr_dt, '');
                    break;
                case 1:
                    $html[] = $this->set_attr_tag('dt', $attr_dt, (string) $lign['dt'][0]);
                    break;
                default:
                    $html[] = $this->set_attr_tag('div', $attr_dt);
                    foreach ($lign['dt'] as $dt) {
                        $html[] = $this->set_attr_tag('dt', $attr_dt, $dt);
                    }
                    $html[] = '</div>';
            }
            switch (count($lign['dd'])) {
                case 0:
                    $html[] = $this->set_attr_tag('dd', $attr_dd, '');
                    break;
                case 1:
                    $html[] = $this->set_attr_tag('dd', $attr_dd, $lign['dd'][0]);
                    break;
                default:
                    $html[] = $this->set_attr_tag('div', $attr_dd);
                    foreach ($lign['dd'] as $dd) {
                        $html[] = $this->set_attr_tag('dd', $attr_dd, $dd);
                    }
                    $html[] = '</div>';
            }
        }
        // -- c'est fini
        $html[] = '</dl>';
        return implode(PHP_EOL, $html);
    }

    // run
}

// class
