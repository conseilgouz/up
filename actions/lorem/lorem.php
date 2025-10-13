<?php

/**
 * Affiche du texte aléatoire (enrichissement possible)
 * .
 * Syntaxe :  {up lorem=2,decorate,headers}
 * - (integer) - En premier, le nombre de paragraphes (P) générés. 4 par défaut
 * - short, medium, verylong ou long - La longueur moyenne d'un paragraphe. medium par défaut.
 * - allcaps - TOUT EN MAJUSCULES.
 * - plaintext - Retourne text sans balise HTML. Idem si options max-char ou max-word.
 * - decorate - ajoute bold, underline, italique, mark, ...
 * - link - ajoute d'un lien par paragraphe.
 * - headers - ajoute un titre défini par header-tag (h3 par défaut) avant le premier paragraphe.
 *
 * === contenu retourné. P par défaut ===
 * - ul - ajoute listes.
 * - ol - ajoute listes ordonnées.
 * - bq - ajoute bloc citation
 * - dl - ajoute listes description.
 * - code - ajoute exemple de code.
 * si un seul type, tous les paragraphes seront de ce type.
 *
 * Pour avoir un texte sans aucun tag, utilisez : {up lorem=2,plaintext | tag=0}.
 * Même sans balise P, le nombre de paragraphes influe sur la longueur du texte retourné.
 *
 * @author  Lomart
 * @version UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
/*
 * v1.61 - nouveau param 'tag=DIV'
 * v1.95 - strip_tags si max-words ou max-chars
 * v5.2 - reprise totale sans dépendance API externe
 */
defined('_JEXEC') or die();

class lorem extends upAction
{
    public function init()
    {
        // aucune
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            $this->name => '', // nombre de paragraphe et mots-clés séparés par des virgules
            /* [st-divers] Divers */
            'max-char' => 0, // nombre maxima de caractères
            'max-word' => 0, // nombre maxima de mots
            'start-with-lorem' => '', // débute le premier paragraphe par "Lorem ipsum dolor sit amet,"
            'header-tag' => '', // h1 à h6. tag pour le titre
            'decorate-tags' => 'b,i,u,mark', // liste des balises pour décorer les paragraphes
            /* [st-css] Style CSS*/
            'tag' => 'div', // (v1.6) tag du bloc contenant le texte en retour ou tag=0 pour aucun.
            'id' => '', // pour forcer l'id (sans effet, si tag=0)
            'class' => '', // classe(s) pour bloc (sans effet, si tag=0)
            'style' => '', // style inline pour bloc (sans effet, si tag=0)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ==== les arguments
        $args = preg_split("/[\s\,\/\.]+/", strtolower($options[$this->name]));
        // ---- le nombre et la taille des blocs
        $blockNbre = intval($options[__CLASS__]) ?: 4;
        if (in_array('short', $args)) {
            $blockSize = 10;
        } elseif (in_array('verylong', $args) || in_array('long', $args)) {
            $blockSize = 68; // nombre de mots de $loremIpsum
        } else {
            $blockSize = 30;
        }
        if (in_array('plaintext', $args)) {
            $args = array();
            $blockSize = 60;
        }
        // ---- le type de bloc
        $args_type_blocks = array_intersect($args, array('p','ul', 'ol', 'bq', 'code', 'dl'));
        $args_type_blocks = array_values($args_type_blocks); // réindexation
        if (empty($args_type_blocks)) {
            $args_type_blocks = array('p');
        }
        $nb = count($args_type_blocks);
        if ($nb > 1) {
            // on complete par des P pour arriver à $blockNbre
            $args_type_blocks = array_pad($args_type_blocks, max($blockNbre, $nb), 'p');
            shuffle($args_type_blocks);
        } else {
            $args_type_blocks = array_pad($args_type_blocks, $blockNbre, $args_type_blocks[0]);
        }

        // ---- decorate
        $this->decorate = (in_array('decorate', $args));
        $this->link = (in_array('link', $args));

        // ==== le texte à utiliser
        $loremIpsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
        if (in_array('allcaps', $args)) {
            $loremIpsum = strtoupper($loremIpsum);
        }
        $words = explode(" ", $loremIpsum); // tableau de mots
        $text = ""; // texte final

        // ==== style du bloc retour
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];

        // =======================================
        // ========== DEBUT TEXTE BRUT ===========
        // =======================================
        $out = array();
        if (in_array('plaintext', $args)) {
            for ($i = 0; $i < $blockNbre; $i++) {
                shuffle($words);
                $out = array_merge($out, array_slice($words, 0, $this->approximately($blockSize)));
            }
            $text = implode(" ", $out);
        } elseif ($options['max-char']) {
            while (strlen($text) < $options['max-char']) {
                shuffle($words);
                $out = array_merge($out, $words);
                $text .= implode(" ", $out);
            }
            $text = substr($text, 0, $options['max-char']);
        } elseif ($options['max-word']) {
            while (count($out) < $options['max-word']) {
                shuffle($words);
                $out = array_merge($out, $words);
            }
            $text = implode(' ', array_slice($out, 0, $options['max-word']));
        }
        // ==== code en retour
        if (!empty($text)) {
            if ($options['tag'] < "A") {
                $out = $text;
            } else {
                $out = $this->set_attr_tag($options['tag'], $attr_main, $text);
            }
            return $out;
        }

        // =======================================
        // ====== DEBUT TEXTE MIS EN FORME =======
        // =======================================

        // ====== Générer un titre
        if (in_array('headers', $args) || !empty($options['header-tag'])) {
            if (empty($options['start-with-lorem'])) {
                shuffle($words);
            }
            $title = ucfirst(implode(" ", array_slice($words, 0, rand(3, 6))));
            $htag = $this->ctrl_argument($options['header-tag'], 'h3,h1,h2,h4,h5,h6', false);
            $text .= "<$htag>$title</$htag>\n";
        }

        // ====== Générer les paragraphes
        for ($i = 0; $i < $blockNbre; $i++) {
            switch ($args_type_blocks[$i]) {
                case 'ul':
                    $text .= "<ul>\n";
                    $numItems = rand(2, 4);
                    for ($j = 0; $j < $numItems; $j++) {
                        shuffle($words);
                        $numWordsItem = rand(2, 5);
                        $listItem = implode(" ", array_slice($words, 0, $numWordsItem));
                        $text .= "<li>$listItem</li>\n";
                    }
                    $text .= "</ul>\n";
                    break;
                case 'ol':
                    $text .= "<ol>\n";
                    $numItems = rand(2, 4);
                    for ($j = 0; $j < $numItems; $j++) {
                        shuffle($words);
                        $numWordsItem = rand(2, 5);
                        $listItem = implode(" ", array_slice($words, 0, $numWordsItem));
                        $text .= "<li>$listItem</li>\n";
                    }
                    $text .= "</ol>\n";
                    break;
                case 'bq':
                    $text .= "<blockquote>\n";
                    shuffle($words);
                    $numWords = rand(10, 20);
                    $quote = implode(" ", array_slice($words, 0, $numWords));
                    $text .= "<p>$quote</p>\n";
                    $text .= "</blockquote>\n";
                    break;
                case 'code':
                    $text .= "<pre>\n";
                    shuffle($words);
                    $numWords = rand(10, 20);
                    $code = implode(" ", array_slice($words, 0, $numWords));
                    $text .= "<code>$code</code>\n";
                    $text .= "</pre>\n";
                    break;
                case 'dl':
                    $text .= "<dl>\n";
                    $numItems = rand(2, 4);
                    for ($j = 0; $j < $numItems; $j++) {
                        shuffle($words);
                        $numWordsTerm = rand(1, 3);
                        $term = implode(" ", array_slice($words, 0, $numWordsTerm));
                        $numWordsDef = rand(2, 5);
                        $def = implode(" ", array_slice($words, $numWordsTerm, $numWordsDef));
                        $text .= "<dt>$term</dt>\n";
                        $text .= "<dd>$def</dd>\n";
                    }
                    $text .= "</dl>\n";
                    break;
                default:
                    $testStart = ($i == 0 && $options['start-with-lorem']) ? 'Lorem ipsum dolor sit amet, ' : '';
                    shuffle($words);
                    $words[0] = ucfirst($words[0]);
                    if (isset($this->options_user['decorate-tags'])) {
                        // si défini par utilisateur, on les prends tous
                        $tags = $options['decorate-tags'];
                    } else {
                        $tags = '';
                        $tmp = explode(",", $options['decorate-tags']);
                        $nb = count($tmp);
                        $ktmp = array_rand($tmp, rand(ceil($nb / 2), $nb));
                        foreach ($ktmp as $k => $v) {
                            $tags .= $tmp[$v] . ',';
                        }
                        $tags = trim($tags, ',');
                    }
                    $str = $this->add_decorate(array_slice($words, 0, $this->approximately($blockSize)), $tags);
                    $str = trim($str, ' .').'.';
                    $text .= "<p>$str</p>\n";
            }
        }

        // ==== code en retour
        if ($options['tag'] < "A") {
            $out = $text;
        } else {
            $out = $this->set_attr_tag($options['tag'], $attr_main, $text);
        }

        return $out;
    }

    public function approximately($value, $percentage = 10)
    {
        $delta = intval($value * $percentage / 100);
        $min = $value - $delta;
        $max = $value + $delta;
        return rand($min, $max);
    }

    public function add_decorate($words, $tags = 'b,i,u,mark')
    {
        if ($this->decorate) {
            $tags = explode(',', $tags);
            foreach ($tags as $tag) {
                //if (rand(0, 1) == 0) {
                $nbWords = count($words);
                $start = rand(1, $nbWords - 4);
                array_splice($words, $start, 0, "<$tag>#");
                $end = rand($start + 2, $nbWords);
                array_splice($words, $end, 0, "#</$tag>");
                //}
            }
        }
        if ($this->link) {
            if (rand(0, 1) == 0) {
                $nbWords = count($words);
                $start = rand(1, $nbWords - 6);
                array_splice($words, $start, 0, "<a href='#'>#");
                $end = rand($start + 2, min($start + 4, $nbWords));
                array_splice($words, $end, 0, "#</a>");
            }
        }
        $out = implode(" ", $words);
        $out = str_replace(array(' #<', '># '), array('<', '>'), $out);
        return $out;
    }

}

// class lorem
