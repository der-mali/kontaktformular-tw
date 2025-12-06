<?php
/**
 * Plugin Name: Kontaktformular TW
 * Description: Ein einfaches Kontaktformular mit Shortcode [contact-form-tw]
 * Version: 20251206.1
 * Author: der-mali
 */

if (!defined('ABSPATH')) {
    exit;
}

class KontaktformularTW {
    
    private $form_message = '';
    private $form_errors = array();
    private $options;
    
    public function __construct() {
        // Optionen laden
        $this->options = get_option('kontaktformular_tw_options', array(
            'empfaenger_email' => get_option('admin_email'),
            'datenschutz_url' => '/datenschutz'
        ));
        
        add_shortcode('contact-form-tw', array($this, 'formular_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'lade_scripts'));
        
        // Formularverarbeitung bei init
        add_action('init', array($this, 'verarbeite_formular_frueh'));
        
        // CSS inline einbinden
        add_action('wp_head', array($this, 'lade_css'));
        
        // Admin Menü hinzufügen
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    // Admin Menü erstellen
    public function add_admin_menu() {
        add_options_page(
            'Kontaktformular TW Einstellungen',
            'Kontaktformular TW',
            'manage_options',
            'kontaktformular-tw',
            array($this, 'options_page')
        );
    }
    
    // Einstellungen initialisieren
    public function settings_init() {
        register_setting('kontaktformular_tw', 'kontaktformular_tw_options');
        
        add_settings_section(
            'kontaktformular_tw_section',
            'Einstellungen für das Kontaktformular',
            array($this, 'settings_section_callback'),
            'kontaktformular_tw'
        );
        
        // Empfänger E-Mail Feld
        add_settings_field(
            'empfaenger_email',
            'Empfänger E-Mail',
            array($this, 'empfaenger_email_render'),
            'kontaktformular_tw',
            'kontaktformular_tw_section'
        );
        
        // Datenschutz URL Feld
        add_settings_field(
            'datenschutz_url',
            'Datenschutz Seite',
            array($this, 'datenschutz_url_render'),
            'kontaktformular_tw',
            'kontaktformular_tw_section'
        );
    }
    
    public function empfaenger_email_render() {
        ?>
        <input type='email' name='kontaktformular_tw_options[empfaenger_email]' 
               value='<?php echo esc_attr($this->options['empfaenger_email']); ?>' 
               style='width: 300px;'
               placeholder='<?php echo get_option('admin_email'); ?>'>
        <p class="description">Standard: <?php echo get_option('admin_email'); ?></p>
        <?php
    }
    
    public function datenschutz_url_render() {
        $pages = get_pages();
        ?>
        <select name='kontaktformular_tw_options[datenschutz_url]' style='width: 300px;'>
            <option value=''>-- Manuelle URL eingeben --</option>
            <?php foreach ($pages as $page): ?>
                <option value='<?php echo get_permalink($page->ID); ?>' 
                    <?php selected($this->options['datenschutz_url'], get_permalink($page->ID)); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">oder manuelle URL: 
            <input type='text' name='kontaktformular_tw_options[datenschutz_url_custom]' 
                   value='<?php echo esc_attr($this->options['datenschutz_url']); ?>' 
                   style='width: 300px; margin-top: 5px;' 
                   placeholder='/datenschutz'>
        </p>
        <?php
    }
    
    public function settings_section_callback() {
        echo 'Konfiguriere hier die Einstellungen für das Kontaktformular';
    }
    
    public function options_page() {
        // Manuelle URL verarbeiten
        if (isset($_POST['kontaktformular_tw_options']['datenschutz_url_custom']) && !empty($_POST['kontaktformular_tw_options']['datenschutz_url_custom'])) {
            $_POST['kontaktformular_tw_options']['datenschutz_url'] = sanitize_text_field($_POST['kontaktformular_tw_options']['datenschutz_url_custom']);
        }
        ?>
        <div class="wrap">
            <h1>Kontaktformular TW Einstellungen</h1>
            
            <form action='options.php' method='post'>
                <?php
                settings_fields('kontaktformular_tw');
                do_settings_sections('kontaktformular_tw');
                submit_button();
                ?>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                <h3>Verwendung</h3>
                <p>Verwende den Shortcode <code>[contact-form-tw]</code> auf jeder Seite oder in jedem Beitrag.</p>
                
                <h3>Aktuelle Einstellungen</h3>
                <ul>
                    <li><strong>Empfänger E-Mail:</strong> <?php echo esc_html($this->options['empfaenger_email']); ?></li>
                    <li><strong>Datenschutz Link:</strong> <?php echo esc_html($this->options['datenschutz_url']); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function verarbeite_formular_frueh() {
        // Prüfen ob es sich um einen Formular-Aufruf handelt
        if (isset($_POST['kontaktformular_action']) && $_POST['kontaktformular_action'] === 'submit') {
            $this->verarbeite_formular();
        }
    }
    
    public function lade_scripts() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'kontaktformular-tw-script',
            plugin_dir_url(__FILE__) . 'kontaktformular-tw.js',
            array('jquery'),
            '4.1',
            true
        );
        
        wp_localize_script(
            'kontaktformular_tw_script',
            'kontaktformular_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kontaktformular_nonce')
            )
        );
    }

	public function lade_css() {
    ?>
    <style>
    /* Abstand oben reduzieren */
    #kontaktformular-tw {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    .contentbox > #kontaktformular-tw,
    .contentbox > form#kontaktformular-tw {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Spezifisch für WordPress Content */
    .entry-content #kontaktformular-tw {
        margin-top: 0 !important;
    }
    
    .formular-gruppe {
        margin-bottom: 15px;
    }
    
    .formular-gruppe label {
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .formular-gruppe input, 
    .formular-gruppe textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        box-sizing: border-box;
        background-color: #f9f9f9;
    }
    
    .formular-gruppe input:focus, 
    .formular-gruppe textarea:focus {
        background-color: #fff;
        border-color: #0073aa;
        outline: none;
    }
    
    /* Checkbox Styling */
    .checkbox-gruppe {
        margin: 20px 0;
    }
    
    .checkbox-label {
        display: flex !important;
        align-items: flex-start;
        font-weight: normal !important;
        cursor: pointer;
    }
    
    .checkbox-label input[type="checkbox"] {
        width: auto !important;
        margin-right: 10px;
        margin-top: 3px;
    }
    
    .checkbox-text {
        font-size: 14px;
        line-height: 1.4;
    }
    
    .checkbox-text a {
        color: #0073aa;
        text-decoration: underline;
    }
    
    .checkbox-text a:hover {
        color: #005a87;
    }
    
    .fehler-meldung {
        color: #dc3545;
        font-size: 12px;
        display: block;
        margin-top: 5px;
    }
    
    .formular-buttons {
        margin-top: 20px;
    }
    
    .formular-buttons button {
        padding: 10px 20px;
        margin-right: 10px;
        border: none;
        cursor: pointer;
    }
    
#absenden-btn {
    background-color: #28a745;
    color: #FFFFFF;
}

#absenden-btn:hover:not(:disabled) {
    background-color: #218838;
}

#loeschen-btn {
    background-color: #dc3545;
    color: #FFFFFF;
}

#loeschen-btn:hover {
    background-color: #c82333;
}
    
    /* Erfolgs- und Fehler-Boxen */
    .boxerfolg {
        color: #000000;
        padding: 15px;
        margin-bottom: 20px;
        border: 2px solid #28a745;
        font-weight: bold;
    }
    
    .boxfehler {
        color: #000000;
        padding: 15px;
        margin-bottom: 20px;
        border: 2px solid #dc3545;
        font-weight: bold;
    }
    </style>
    <?php
    }

    public function formular_shortcode() {
        ob_start();
        
        // Datenschutz URL aus Einstellungen holen
        $datenschutz_url = !empty($this->options['datenschutz_url']) ? $this->options['datenschutz_url'] : '/datenschutz';
        ?>
        <!-- Nachricht anzeigen falls vorhanden -->
        <?php if (!empty($this->form_message)): ?>
            <div id="kontaktformular-tw-meldung"><?php echo $this->form_message; ?></div>
        <?php else: ?>
            <div id="kontaktformular-tw-meldung"></div>
        <?php endif; ?>
        
        <form id="kontaktformular-tw" method="post">
            <!-- Verstecktes Feld für die Action -->
            <input type="hidden" name="kontaktformular_action" value="submit">
            <?php wp_nonce_field('kontaktformular_nonce', 'kontaktformular_nonce'); ?>
            
            <div class="formular-gruppe">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>" minlength="5" required>
                <span class="fehler-meldung" id="name-fehler">
                    <?php if (!empty($this->form_errors['name'])) echo $this->form_errors['name']; ?>
                </span>
            </div>
            
            <div class="formular-gruppe">
                <label for="telefon">Telefonnummer *</label>
                <input type="tel" id="telefon" name="telefon" value="<?php echo isset($_POST['telefon']) ? esc_attr($_POST['telefon']) : ''; ?>" minlength="5" required>
                <span class="fehler-meldung" id="telefon-fehler">
                    <?php if (!empty($this->form_errors['telefon'])) echo $this->form_errors['telefon']; ?>
                </span>
            </div>
            
            <div class="formular-gruppe">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                <span class="fehler-meldung" id="email-fehler"></span>
            </div>
            
            <div class="formular-gruppe">
                <label for="text">Nachricht *</label>
                <textarea id="text" name="text" rows="5" minlength="30" required><?php echo isset($_POST['text']) ? esc_textarea($_POST['text']) : ''; ?></textarea>
                <span class="fehler-meldung" id="text-fehler">
                    <?php if (!empty($this->form_errors['text'])) echo $this->form_errors['text']; ?>
                </span>
            </div>

            <div class="formular-gruppe">
                <label for="name">Validierung: geben Sie mindestens 12 beliebige Zeichen ein *</label>
                <input type="text" id="validierung" name="validierung" value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>" minlength="12" required>
                <span class="fehler-meldung" id="validierung-fehler">
                    <?php if (!empty($this->form_errors['name'])) echo $this->form_errors['name']; ?>
                </span>
            </div>
            
            * <em>Pflichtfeld</em>
            <div class="formular-gruppe checkbox-gruppe">
                <label class="checkbox-label">
                    <input type="checkbox" id="datenschutz" name="datenschutz" <?php echo isset($_POST['datenschutz']) ? 'checked' : ''; ?> required>
                    <span class="checkbox-text">Ich bin mit der Verarbeitung meiner Daten einverstanden.<br>
                    Ich habe die <a href="<?php echo esc_url($datenschutz_url); ?>" target="_blank"><u>Datenschutzerklärung</u></a> gelesen.</span>
                </label>
                <span class="fehler-meldung" id="datenschutz-fehler">
                    <?php if (!empty($this->form_errors['datenschutz'])) echo $this->form_errors['datenschutz']; ?>
                </span>
            </div>
            
            <br>
            
            <div class="formular-buttons">
                <button type="submit" id="absenden-btn">SENDEN</button>&nbsp;<button type="button" id="loeschen-btn"><small>löschen</small></button>
            </div>

            <!-- SPAM SCHUTZ FELDER AM ENDE DES FORMULARS (unsichtbar) -->
            <div style="display: none !important; visibility: hidden; height: 0; overflow: hidden;">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
            </div>
            
            <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">
        </form>
        <?php
        return ob_get_clean();
    }

    public function verarbeite_formular() {
        // 1. EINFACHER SPAM SCHUTZ: Honeypot Check
        if (!empty($_POST['website'])) {
            // SPAM erkannt - einfach ignorieren (silent fail)
            return;
        }
        
        // 2. EINFACHER SPAM SCHUTZ: Time-Based Check (mindestens 3 Sekunden)
        if (isset($_POST['form_timestamp']) && (time() - intval($_POST['form_timestamp'])) < 3) {
            $this->form_message = '<div class="boxfehler">Bitte warten Sie einen Moment, bevor Sie das Formular absenden.</div>';
            return;
        }
        
        // 3. Nonce Überprüfung
        if (!wp_verify_nonce($_POST['kontaktformular_nonce'], 'kontaktformular_nonce')) {
            $this->form_message = '<div class="boxfehler">Sicherheitsfehler. Bitte Seite neu laden.</div>';
            return;
        }
        
        // Daten empfangen und sanitizen
        $name = sanitize_text_field($_POST['name']);
        $telefon = sanitize_text_field($_POST['telefon']);
        $email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $text = sanitize_textarea_field($_POST['text']);
        $datenschutz = isset($_POST['datenschutz']) ? true : false;
        
        // 4. EINFACHE Inhaltsvalidierung
        if ($this->ist_spam($name, $email, $text)) {
            $this->form_message = '<div class="boxfehler">Ihre Nachricht enthält verdächtige Inhalte.</div>';
            return;
        }
        
        // Validierung
        $fehler = array();
        
        if (empty($name)) {
            $fehler['name'] = '<div class="boxfehler">Bitte geben Sie Ihren Namen ein.</div>';
        }
        
        if (empty($telefon)) {
            $fehler['telefon'] = '<div class="boxfehler">Bitte geben Sie Ihre Telefonnummer ein.</div>';
        }

        if (empty($text)) {
            $fehler['text'] = '<div class="boxfehler">Bitte geben Sie eine Nachricht ein.</div>';
        }
        
        if (!$datenschutz) {
            $fehler['datenschutz'] = '<div class="boxfehler">Bitte stimmen Sie der Datenverarbeitung zu</div>';
        }
        
        // Wenn Fehler vorhanden sind
        if (!empty($fehler)) {
            $this->form_errors = $fehler;
            return;
        }
        
        // Empfänger E-Mail aus Einstellungen holen
        $empfaenger_email = !empty($this->options['empfaenger_email']) ? $this->options['empfaenger_email'] : get_option('admin_email');
        
        // E-Mail an Empfänger senden
        $erfolg_empfaenger = $this->sende_email_an_empfaenger($name, $telefon, $email, $text, $empfaenger_email);
        
        // Kopie an Absender senden (wenn E-Mail angegeben)
        $erfolg_absender = true;
        if (!empty($email)) {
            $erfolg_absender = $this->sende_kopie_an_absender($name, $telefon, $email, $text);
        }

        if ($erfolg_empfaenger && $erfolg_absender) {
            $this->form_message = '<br><div class="boxerfolg">Vielen Dank für Ihre Kontaktaufnahme!<br>Ihre Nachricht wurde erfolgreich versendet.' . (!empty($email) ? '<br>Sie erhalten eine Kopie an Ihre E-Mail-Adresse.' : '') . '</div>';
            
            // Formular zurücksetzen für den nächsten Versuch
            unset($_POST['name'], $_POST['telefon'], $_POST['email'], $_POST['text'], $_POST['datenschutz']);
        } else {
            $this->form_message = '<br><div class="boxfehler">Beim Senden der E-Mail ist ein Fehler aufgetreten.<br>Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt unter ' . esc_html($empfaenger_email) . '</div>';
        }
    }
    
    /**
     * EINFACHE SPAM-Erkennung
     */
    private function ist_spam($name, $email, $text) {
        // Sehr offensichtliche SPAM-Keywords
        $spam_keywords = array('viagra', 'cialis', 'casino', 'nude', 'porn', 'xxx');
        
        $combined_text = strtolower($name . ' ' . $email . ' ' . $text);
        
        foreach ($spam_keywords as $keyword) {
            if (strpos($combined_text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function sende_email_an_empfaenger($name, $telefon, $email, $text, $empfaenger) {
        $website_url = parse_url(home_url(), PHP_URL_HOST);
        $website_url = str_replace('www.', '', $website_url);
        $betreff = 'Nachricht von ' . $website_url;
        
        $nachricht = "
N E U E   K O N T A K T A N F R A G E
Ü B E R   D A S   K O N T A K T F O R M U L A R



NAME:
{$name}

TELEFON:
{$telefon}

E-MAIL:
{$email}



NACHRICHT:
{$text}



-- 



Gesendet vom Kontaktformular
Website: " . home_url() . "
Zeit: " . date('d.m.Y H:i:s') . "
        ";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $name . ' <' . get_option('admin_email') . '>'
        );

        if (!empty($email)) {
            $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
        }
        
        return wp_mail($empfaenger, $betreff, $nachricht, $headers);
    }
    
    private function sende_kopie_an_absender($name, $telefon, $email, $text) {
        $website_url = parse_url(home_url(), PHP_URL_HOST);
        $website_url = str_replace('www.', '', $website_url);
        $website_url_clean = $website_url;
        
        $betreff = 'Kopie Ihrer Nachricht an ' . $website_url_clean;
        
        $nachricht = "
Sehr geehrte/r {$name},


vielen Dank für Ihre Nachricht!
Hier ist eine Kopie Ihrer Kontaktanfrage:


Ihre Angaben:
Name: {$name}
Telefon: {$telefon}
E-Mail: {$email}


Ihre Nachricht:
{$text}


-- 


Wir werden uns schnellstmöglich bei Ihnen melden.


Mit freundlichen Grüßen
Ihr Team von {$website_url_clean}


Website: https://{$website_url_clean}
Datum: " . date('d.m.Y H:i:s') . "
        ";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $website_url_clean . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($email, $betreff, $nachricht, $headers);
    }
}

new KontaktformularTW();
