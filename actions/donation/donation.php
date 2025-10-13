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

class donation extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

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
        $options = $this->ctrl_options($options_def);

        // --- Controle argument
        if (strpos($options[__class__], '@') === false) {
            return $this->msg_inline($this->lang('en=main option is not an email;fr=L\'option principale n\'est pas un email valide'));
        }
        // si saisi par rédacteur
        if (! empty($this->options_user['currency-list'])) {
            $currency_authorized = explode(',', 'EUR,USD,GBP,CHF,AUD,HKD,CAD,JPY,NZD,SGD,SEK,DKK,PLN,NOK,HUF,CZK,ILS,MXN');
            $options['currency-list'] = strtoupper($options['currency-list']);
            $currency_user = explode(',', $options['currency-list']);
            $diff = array_diff($currency_user, $currency_authorized);
            if (! empty($diff)) {
                return $this->msg_inline(implode(',', $diff) . ' devise non autorisée');
            }
        }
        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        $tmpl = $this->get_bbcode($options['template']);

        // --- attributs du bloc principal
        $attr_form['id'] = $options['id'];
        $attr_form['method'] = 'post';
        $attr_form['target'] = 'paypal';
        $this->get_attr_style($attr_form, $options['class'], $options['style']);
        $sandbox = ($options['use-sandbox']) ? 'sandbox.' : '';
        $attr_form['action'] = 'https://www.' . $sandbox . 'paypal.com/fr/cgi-bin/webscr';
        $html[] = $this->set_attr_tag('form', $attr_form);
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
            $title_html = $this->get_bbcode($options['title']);
            if ($options['title-style']) {
                // si style, on ajoute une balise SPAN
                $tag = ($options['title-tag']) ? $options['title-tag'] : 'span';
                $this->get_attr_style($attr_title, $options['title-style']);
                $title_html = $this->set_attr_tag($tag, $attr_title, $title_html);
            }
            $tmpl = str_replace('##title##', $title_html ?? '', $tmpl);
        }
        // --- le text
        if (stripos($tmpl, '##text##') !== false) {
            $text_html = $this->get_bbcode($options['text']);
            if ($options['text-style'] || $options['text-tag']) {
                // si style, on ajoute une balise SPAN
                $tag = ($options['text-tag']) ? $options['text-tag'] : 'span';
                $this->get_attr_style($attr_text, $options['text-style']);
                $text_html = $this->set_attr_tag($tag, $attr_text, $text_html);
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
            $this->get_attr_style($attr_amount, $options['amount-style']);
            $amount_html = $this->set_attr_tag('input', $attr_amount);
            // --- devise
            $attr_currency['name'] = 'currency_code';
            $attr_currency['style'] = 'width:70px;height:inherit;display:inline;padding:4px';
            $this->get_attr_style($attr_currency, $options['currency-style']);
            $currency_html = $this->set_attr_tag('select', $attr_currency);
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
        $button = $this->get_bbcode($options['button']);
        $img_only = (preg_match('#.jpg|.gif|.png#', strtolower($button)) == 1);
        $img_only = ($img_only && strpos(strtolower($button), '<img') === false);
        if ($img_only) {
            // le bouton est une image
            // uniquement les styles spécifiés par le redacteur
            $this->get_attr_style($attr_button, $this->options_user['button-style']);
            $attr_button['type'] = 'image';
            $locale = str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag());
            $attr_button['src'] = 'https://www.paypal.com/' . $locale . '/i/btn/' . $button;
            $button = false;
            $button_html = $this->set_attr_tag('input', $attr_button, $button);
        } else {
            // le bouton est du texte
            $this->get_attr_style($attr_button, $options['button-style']);
            $attr_button['type'] = 'submit';
            $button_html = $this->set_attr_tag('button', $attr_button, $button);
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
