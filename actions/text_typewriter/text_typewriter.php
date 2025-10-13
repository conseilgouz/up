<?php

/**
 * Afficher plusieurs phrases avec un effet machine à écrire
 *
 * syntaxe 1 : {up text-typewriter=mot1, mot2, ..., motN}
 * syntaxe 2 : {up text-typewriter}alternatives dans blocs enfants {/up text-typewriter}
 *
 * @version  UP-2.4
 * @author  LOMART
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.cssscript.com/highly-configurable-text-typing-library-typed-js/" target"_blank">script typed.js de mattboldt</a>
 * @tags    HTML
 *
 */
/*
v5.2 - fix options booleenne JS
*/
defined('_JEXEC') or die();

class text_typewriter extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('typed.min.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // liste de mots séparés par des virgules
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ===== paramétres attendus par le script JS
        $js_options_def = array(
        /* [st-speed] paramétres JS :  vitesse affichage */
            'typeSpeed' => 0, // vitesse de frappe en ms
            'startDelay' => 0, // délai en ms avant chaque série
            'backSpeed' => 0, // vitesse de l'espacement arrière en millisecondes
            'backDelay' => 700, // délai en ms avant effacement
            /* [st-fade] paramétres JS : gestion du fondu pour transition */
            'fadeOut' => false, // Fondu au lieu de retour en arrière
            'fadeOutClass' => 'typed-fade-out', // classe CSS pour l'animation du fondu
            'fadeOutDelay' => 500, // Durée du fondu en millisecondes
            /* [st-loop] paramétres JS : affichage en boucle */
            'loop' => false, // chaînes en boucle
            'loopCount' => 'Infinity', // nombre de boucles
            /* [st-div] paramétres JS : divers */
            'smartBackspace' => true, // n'efface que ce qui ne correspond pas à la chaîne précédente
            'shuffle' => false, // mélange les phrases
            'showCursor' => true, // montrer le curseur
            'cursorChar' => '|', // caractère pour le curseur
            'autoInsertCss' => true, // insérer le CSS pour le curseur et le fadeOut dans le HTML <head>
            'attr' => null, // attribut pour la saisie. Ex: input placeholder, value, or just HTML text
            'bindInputFocusEvents' => false, // lier le focus et le blur si élément est une entrée de texte
            'contentType' => 'html' // 'html' ou 'null' pour texte brut
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);
        // le code JS
        // les options saisies par l'utilisateur concernant le script JS
        $js_options = $this->only_using_options($js_options_def);
        // on normalise la saisie des booleens v5.2
        foreach (array('fadeOut','loop','smartBackspace','shuffle','showCursor','autoInsertCss','autoInsertCss','bindInputFocusEvents') as $option) {
            if (isset($js_options[$option])) {
                $js_options[$option] = (empty($js_options[$option])) ? 'false' : 'true';
            }
        }
        // on force loop si loopCount demandé. voir modif code JS by LM
        if ($options['loopCount'] != 'Infinity') {
            $js_options['loop'] = true;
        }

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === la liste des mots
        if ($options[__CLASS__]) {
            $str = $this->get_bbcode($options[__CLASS__]);
            $str = str_replace('"', '\'', $str);
            $wordlist = explode(',', $str);
            $js_words = '[';
            foreach ($wordlist as $word) {
                $js_words .= '"' . $word . '",';
            }
            $js_words = rtrim($js_words, ',') . ']';
            $js_options['strings'] = $js_words;
        } elseif ($this->content) {
            $attr_string['id'] = $options['id'] . '-strings';
            $js_options['stringsElement'] = '#' . $attr_string['id'];
        } else {
            $this->msg_error('Mots non trouvés / Words not found');
        }

        // === attributs des blocs
        $attr_span['id'] = $options['id']; // le bloc affiché
        $this->get_attr_style($attr_span, $options['class'], $options['style']);

        // === initialisation JS
        $js_code[] = 'var ' . str_replace('-', '_', $attr_span['id']) . ' = new Typed("#' . $attr_span['id'] . '",';
        // $js_code[] = $this->json_arrtostr($js_options, 2);
        $optString = $this->json_arrtostr($js_options, 2); // v5.2
        $optString = str_replace(array('"false"','"true"'), array('false','true'), $optString);
        $js_code[] = $optString;
        $js_code[] = ');';
        $js_code = $this->load_jquery_code(implode(PHP_EOL, $js_code), false);

        // === code en retour
        $html[] = $this->set_attr_tag('span  ', $attr_span, true);
        if (isset($js_options['stringsElement'])) {
            $html[] = $this->set_attr_tag('div  ', $attr_string, $this->content);
        }
        // --- le jquery
        $html[] = $js_code;

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
