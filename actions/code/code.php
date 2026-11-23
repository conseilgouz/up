<?php
/**
 * Affiche du code source sans l'executer, dans un cadre avec bouton de copie.
 * Les accolades des shortcodes UP contenus dans le bloc et ne devant pas être interprétées
 * doivent être en gras.
 *
 * Syntaxe :
 * {up code}...code a afficher...{/up code}
 * {up code | title="Exemple" | lang=html | copy=1 | normalize=1}...{/up code}
 *
 * @author  Sergio pour LOMART / UP
 * @version 1.1.0
 * @license GNU/GPLv3
 * @tags    Editor, HTML, Code
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class code extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        return true;
    }

    public function run()
    {
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__    => '', 
            'title'      => '', // titre 
            'lang'       => '', // language utilisé tel que CSS, PHP 
            'copy'       => '1', // montrer ou non le bouton copy
            'normalize'  => '1', // "Normaliser les retours à la ligne et les balises de mise en forme ajoutés par un éditeur WYSIWYG 
            'background' => '#eeeeee', // Couleur de fond du bloc
            'color'      => '#333333', // Couleur du texte
            'style'      => '' ,// style associé à l'action
            'class'      => '' // classe associée à l'action
            
        );

        $options = UpHelper::ctrl_options($this, $options_def);
        $content = ($this->content !== null && $this->content !== '') ? $this->content : $options[__class__];

        if ($content === '' || $content === null) {
            return UpHelper::msg_inline($this, 'UP_CODE_NO_CONTENT');
        }

        UpHelper::load_file($this, 'code.css');
        if ($options['copy'] != '0') {
            UpHelper::load_file($this, 'code.js');
        }

        // Les éditeurs WYSIWYG ajoutent parfois des <p>, <br> et <strong>.
        // On ne traite que les vraies balises HTML ; les balises encodées (&lt;br&gt;)
        // restent du texte et seront affichées comme telles après décodage.
        if ($options['normalize'] != '0') {
            $content = $this->normalizeEditorMarkup((string) $content);
        }

        // Décode une seule fois les entités provenant de l'éditeur, puis ré-échappe
        // entièrement pour garantir qu'aucun HTML ni shortcode ne soit exécuté.
        $content = html_entity_decode((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = str_replace("\xC2\xA0", ' ', $content); // espace insécable -> espace normal
        $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // Les accolades restent visibles dans le navigateur mais ne forment plus
        // des shortcodes dans le HTML transmis aux éventuels plugins suivants.
        $escaped = str_replace(array('{', '}'), array('&#123;', '&#125;'), $escaped);

        $background = $this->safeCssValue($options['background'], '#eeeeee');
        $color = $this->safeCssValue($options['color'], '#333333');
        $id = isset($this->options_user['id']) ? $this->options_user['id'] : 'up-code-' . uniqid();
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
        if ($id === '') {
            $id = 'up-code-' . uniqid();
        }

        $title = trim((string) $options['title']);
        $lang = trim((string) $options['lang']);
        $copy = ($options['copy'] != '0');
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escapedLang = htmlspecialchars($lang, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $style = 'background-color:' . $background . ';color:' . $color . ';'.$options['style'];
        $class = 'up-code '.$options['class'];
        $out = '<div id="' . $id . '" class="'.$class.'" style="' . $style . '">';
        if ($title !== '' || $lang !== '' || $copy) {
            $out .= '<div class="up-code__toolbar">';
            if ($title !== '') {
                $out .= '<span class="up-code__title">' . $escapedTitle . '</span>';
            } elseif ($lang !== '') {
                $out .= '<span class="up-code__title">' . $escapedLang . '</span>';
            }
            if ($copy) {
                $out .= '<button type="button" class="up-code__copy" title="Copier le code" aria-label="Copier le code">';
                $out .= '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="8" y="8" width="12" height="12" rx="1.5"></rect><path d="M16 8V5.5A1.5 1.5 0 0 0 14.5 4h-9A1.5 1.5 0 0 0 4 5.5v9A1.5 1.5 0 0 0 5.5 16H8"></path></svg>';
                $out .= '<span class="up-code__copy-label">Copier</span></button>';
            }
            $out .= '</div>';
        }
        $out .= '<pre class="up-code__pre"><code>' . $escaped . '</code></pre>';
        $out .= '<span class="up-code__status" aria-live="polite"></span>';
        $out .= '</div>';
        return $out;
    }

    /** Convertit la mise en forme WYSIWYG structurelle en retours à la ligne. */
    private function normalizeEditorMarkup($content)
    {
        $content = preg_replace('/<br\\s*\\/?\\s*>/i', "\n", $content);
        $content = preg_replace('/<\\/p\\s*>\\s*<p\\b[^>]*>/i', "\n", $content);
        $content = preg_replace('/<p\\b[^>]*>/i', '', $content);
        $content = preg_replace('/<\\/p\\s*>/i', "\n", $content);
        // Enlève les enrichissements typographiques ajoutés par l'éditeur,
        // sans supprimer le texte qu'ils entourent.
        $content = preg_replace('/<\\/?(?:strong|b|em|i|u)\\b[^>]*>/i', '', $content);
        $content = preg_replace('/<\\/?span\\b[^>]*>/i', '', $content);
        // Les CRLF/CR sont uniformisés en LF.
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        return trim($content, "\n");
    }

    /** Accepte une valeur CSS simple, sans déclaration additionnelle. */
    private function safeCssValue($value, $fallback)
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^[#(),.%a-zA-Z0-9\\s_-]+$/', $value)) {
            return $fallback;
        }
        return $value;
    }
}
