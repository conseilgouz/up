<?php

/**
 * Formulaire de donation avec Paypal
 *
 * syntaxe {up donation=compte_paypal}
 *
 * @author   LOMART
 * @version  UP-2.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Widget
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class donation extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // Votre adresse e-mail PayPal enregistrée ou votre identifiant PayPal
            'template' => '##title## [span style="white-space:nowrap;padding:6px;display:inline-block"]##amount## ##currency##[/span] ##button##', // modèle mise en page
            /* [st-title] définition du titre  (##title##) */
            'title' => '', // texte pour ##title##. bbcode accepté
            'title-tag' => '', // balise pour le titre (##title##) span par défaut si title-style
            'title-style' => '', // classes et styles pour le titre
            /* [st-text] définition du texte d'accompagnement (##text##) */
            'text' => '', // texte bbcode pour motclé ##text##
            'text-tag' => '', // balise pour le titre (##text##) span par défaut si text-style
            'text-style' => '', // classes et styles pour texte d'accompagnement
            /* [st-button]  définition du bouton (##button##) */
            'button' => 'lang[en=Donate;fr=Faire un don]', // texte ou image Paypal pour ##button##
            'button-style' => 'b;t-grisFonce;background:#FFC439;border:#ECB300 1px outset;border-radius:50px;cursor:pointer', // classes et styles pour bouton (##button##)
            /* [st-amount] définition du montant et de la devise (##amount## & ##currency##) */
            'amount' => '10', // Montant du don. Inutile si ##amount##
            'currency-code' => 'EUR', // Devise. Inutile si ##currency##
            'currency-list' => 'EUR,USD,GBP,CHF,AUD,HKD,CAD,JPY,NZD,SGD,SEK,DKK,PLN,NOK,HUF,CZK,ILS,MXN', // liste des devises acceptées. 1ère par défaut
            'amount-style' => '', // classes et styles pour montant (##amount##)
            'currency-style' => '', // classes et styles pour choix devises (##currency##)
            /* [st-paypal] Données transmises et utilisées par le site de Paypal */
            'item-name' => 'Donation', // La raison de vos dons. Sera imprimé sur la confirmation PayPal
            'image-url' => '', // URL du logo de de votre organisme affiché sur la confirmation d Paypal
            'url-valid' => 'http://', // Chemin complet vers la page de retour après un paiement correct
            'url-cancel' => 'http://', // Chemin complet vers la page de retour après un échec de paiement
            'use-sandbox' => '0', // pour tester la donation
            /* [st-annexe] style et options secondaires */
            'id' => '', // id pour bloc externe
            'class' => 'tc', // classe(s) pour bloc externe
            'style' => '', // style inline pour bloc externe
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // --- Controle argument
        if (strpos($options[__class__], '@') === false) {
            return UpHelper::msg_inline($this,UpHelper::lang($this,'en=main option is not an email;fr=L\'option principale n\'est pas un email valide'));
        }
        // si saisi par rédacteur
        if (! empty($this->options_user['currency-list'])) {
            $currency_authorized = explode(',', 'EUR,USD,GBP,CHF,AUD,HKD,CAD,JPY,NZD,SGD,SEK,DKK,PLN,NOK,HUF,CZK,ILS,MXN');
            $options['currency-list'] = strtoupper($options['currency-list']);
            $currency_user = explode(',', $options['currency-list']);
            $diff = array_diff($currency_user, $currency_authorized);
            if (! empty($diff)) {
                return UpHelper::msg_inline($this,implode(',', $diff) . ' devise non autorisée');
            }
        }
        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        $tmpl = UpHelper::get_bbcode($this,$options['template']);

        // --- attributs du bloc principal
        $attr_form['id'] = $options['id'];
        $attr_form['method'] = 'post';
        $attr_form['target'] = 'paypal';
        UpHelper::get_attr_style($this,$attr_form, $options['class'], $options['style']);
        $sandbox = ($options['use-sandbox']) ? 'sandbox.' : '';
        $attr_form['action'] = 'https://www.' . $sandbox . 'paypal.com/fr/cgi-bin/webscr';
        $html[] = UpHelper::set_attr_tag($this,'form', $attr_form);
        // --- code hidden
        $html[] = '<input type="hidden" name="cmd" value="_donations">';
        $html[] = '<input type="hidden" name="business" value="' . $options[__class__] . '">';
        $html[] = '<input type="hidden" name="return" value="' . $options['url-valid'] . '">';
        $html[] = '<input type="hidden" name="cancel_return" value="' . $options['url-cancel'] . '">';
        $html[] = '<input type="hidden" name="undefined_quantity" value="0">';
        $html[] = '<input type="hidden" name="item_name" value="' . $options['item-name'] . '">';
        $html[] = '<input type="hidden" name="charset" value="utf-8">';
        $html[] = '<input type="hidden" name="no_shipping" value="1">';
        $html[] = '<input type="hidden" name="image_url" value="' . $options['image-url'] . '">';
        $html[] = '<input type="hidden" name="no_note" value="0">';
        // --- le titre
        if (stripos($tmpl, '##title##') !== false) {
            $title_html = UpHelper::get_bbcode($this,$options['title']);
            if ($options['title-style']) {
                // si style, on ajoute une balise SPAN
                $tag = ($options['title-tag']) ? $options['title-tag'] : 'span';
                UpHelper::get_attr_style($this,$attr_title, $options['title-style']);
                $title_html = UpHelper::set_attr_tag($this,$tag, $attr_title, $title_html);
            }
            $tmpl = str_replace('##title##', $title_html ?? '', $tmpl);
        }
        // --- le text
        if (stripos($tmpl, '##text##') !== false) {
            $text_html = UpHelper::get_bbcode($this,$options['text']);
            if ($options['text-style'] || $options['text-tag']) {
                // si style, on ajoute une balise SPAN
                $tag = ($options['text-tag']) ? $options['text-tag'] : 'span';
                UpHelper::get_attr_style($this,$attr_text, $options['text-style']);
                $text_html = UpHelper::set_attr_tag($this,$tag, $attr_text, $text_html);
            }
            $tmpl = str_replace('##text##', $text_html, $tmpl);
        }
        // --- une image d'illustration
        // --- le montant et devise
        $amount_html = '';
        $currency_html = '';
        if (stripos($tmpl, '##amount##') !== false) {
            $attr_amount['type'] = 'text';
            $attr_amount['name'] = 'amount';
            $attr_amount['placeholder'] = '';
            $attr_amount['maxlength'] = '6';
            $attr_amount['value'] = $options['amount'];
            $attr_amount['style'] = 'width:40px;height:inherit;text-align:right;display:inline;padding:4px';
            UpHelper::get_attr_style($this,$attr_amount, $options['amount-style']);
            $amount_html = UpHelper::set_attr_tag($this,'input', $attr_amount);
            // --- devise
            $attr_currency['name'] = 'currency_code';
            $attr_currency['style'] = 'width:70px;height:inherit;display:inline;padding:4px';
            UpHelper::get_attr_style($this,$attr_currency, $options['currency-style']);
            $currency_html = UpHelper::set_attr_tag($this,'select', $attr_currency);
            // les options
            $currency = explode(',', $options['currency-list']);
            foreach ($currency as $val) {
                $currency_html .= '<option value = "' . $val . '">' . $val . '</option>';
            }
            $currency_html .= '</select>';
        } else {
            $html[] = '<input type="hidden" name="amount" value="' . $options['amount'] . '">';
            $html[] = '<input type="hidden" name="currency_code" value="EUR" />';
        }
        $tmpl = str_replace('##amount##', $amount_html, $tmpl);
        $tmpl = str_replace('##currency##', $currency_html, $tmpl);
        // --- le bouton
        $attr_button['name'] = 'submit';
        $attr_button['alt'] = 'PayPal secure payments.';
        $button = UpHelper::get_bbcode($this,$options['button']);
        $img_only = (preg_match('#.jpg|.gif|.png#', strtolower($button)) == 1);
        $img_only = ($img_only && strpos(strtolower($button), '<img') === false);
        if ($img_only) {
            // le bouton est une image
            // uniquement les styles spécifiés par le redacteur
            UpHelper::get_attr_style($this,$attr_button, $this->options_user['button-style']);
            $attr_button['type'] = 'image';
            $locale = str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag());
            $attr_button['src'] = 'https://www.paypal.com/' . $locale . '/i/btn/' . $button;
            $button = false;
            $button_html = UpHelper::set_attr_tag($this,'input', $attr_button, $button);
        } else {
            // le bouton est du texte
            UpHelper::get_attr_style($this,$attr_button, $options['button-style']);
            $attr_button['type'] = 'submit';
            $button_html = UpHelper::set_attr_tag($this,'button', $attr_button, $button);
        }
        $tmpl = str_replace('##button##', $button_html, $tmpl);
        // --- fin
        $html[] = $tmpl;
        $html[] = '</form>';

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
