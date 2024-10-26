<?php
/*
Plugin Name: NewsAPI
Description: Bezieht Nachrichten von NewsAPI und erstellt deutsche Zusammenfassungen. Enthält SEO-Optimierung, automatische Updates und manuelle Freigabeoption.
Version: 1.2
Author: Mustafa Sahin
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class NewsAPI {

    private $api_url = 'https://api.github.com/repos/DEIN-GITHUB-NUTZERNAME/newsapi-plugin/releases/latest'; // GitHub URL zur letzten Version

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('newsapi_cron_hook', array($this, 'fetch_and_create_posts'));
        register_activation_hook(__FILE__, array($this, 'activate_cron'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_cron'));
        add_action('admin_post_manual_run', array($this, 'manual_run'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
    }

    // Prüfen auf Updates von GitHub
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Aktuelle Version des Plugins
        $current_version = '1.2';
        $plugin_slug = plugin_basename(__FILE__);

        // GitHub API abfragen
        $response = wp_remote_get($this->api_url);
        if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
            $release_data = json_decode($response['body'], true);
            if (isset($release_data['tag_name']) && version_compare($current_version, $release_data['tag_name'], '<')) {
                // Neues Update vorhanden
                $plugin_data = array(
                    'slug' => $plugin_slug,
                    'new_version' => $release_data['tag_name'],
                    'package' => $release_data['assets'][0]['browser_download_url'],
                    'url' => $release_data['html_url']
                );
                $transient->response[$plugin_slug] = (object)$plugin_data;
            }
        }

        return $transient;
    }

    // Plugin-Informationen für die Update-Seite
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information') {
            return $false;
        }

        $plugin_slug = plugin_basename(__FILE__);

        // Wenn nach unserem Plugin gesucht wird
        if ($plugin_slug === $response->slug) {
            // GitHub API abfragen
            $response = wp_remote_get($this->api_url);
            if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
                $release_data = json_decode($response['body'], true);

                // Füge Plugin-Infos hinzu
                $response->name = 'NewsAPI';
                $response->slug = $plugin_slug;
                $response->version = $release_data['tag_name'];
                $response->download_link = $release_data['assets'][0]['browser_download_url'];
                $response->tested = '5.8'; // WordPress-Version
                $response->requires = '5.0';
                $response->author = 'Mustafa Sahin';
                $response->homepage = $release_data['html_url'];
                $response->sections = array(
                    'description' => 'Bezieht Nachrichten von NewsAPI und erstellt deutsche Zusammenfassungen.'
                );
            }
        }

        return $response;
    }

    // Aktivierung des Cron-Jobs
    public function activate_cron() {
        $options = get_option('newsapi_settings');
        $cron_schedule = isset($options['newsapi_cron_schedule']) ? $options['newsapi_cron_schedule'] : 'hourly';

        if (!wp_next_scheduled('newsapi_cron_hook')) {
            wp_schedule_event(time(), $cron_schedule, 'newsapi_cron_hook');
        }
    }

    // Deaktivierung des Cron-Jobs
    public function deactivate_cron() {
        $timestamp = wp_next_scheduled('newsapi_cron_hook');
        wp_unschedule_event($timestamp, 'newsapi_cron_hook');
    }

    // Admin-Menü hinzufügen
    public function add_admin_menu() {
        add_options_page(
            'NewsAPI Einstellungen',
            'NewsAPI',
            'manage_options',
            'newsapi',
            array($this, 'options_page')
        );

        // Skript-Verwaltungsseite hinzufügen
        add_menu_page(
            'Skript-Verwaltung',
            'Skript-Verwaltung',
            'manage_options',
            'script_management',
            array($this, 'script_management_page'),
            'dashicons-schedule',
            90
        );
    }

    // Einstellungen initialisieren
    public function settings_init() {
        register_setting('newsapi', 'newsapi_settings');

        add_settings_section(
            'newsapi_section',
            __('API-Schlüssel und Einstellungen', 'wordpress'),
            array($this, 'settings_section_callback'),
            'newsapi'
        );

        add_settings_field(
            'newsapi_newsapi_key',
            __('NewsAPI-Schlüssel', 'wordpress'),
            array($this, 'newsapi_key_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_openai_key',
            __('OpenAI API-Schlüssel', 'wordpress'),
            array($this, 'openai_key_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_keyword',
            __('Keyword für NewsAPI-Suche', 'wordpress'),
            array($this, 'keyword_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_author',
            __('Beitragsautor auswählen', 'wordpress'),
            array($this, 'author_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_max_articles',
            __('Maximale Anzahl an Nachrichten', 'wordpress'),
            array($this, 'max_articles_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_manual_mode',
            __('Manuelle Überprüfung der Nachrichten', 'wordpress'),
            array($this, 'manual_mode_render'),
            'newsapi',
            'newsapi_section'
        );

        add_settings_field(
            'newsapi_cron_schedule',
            __('Cron-Zeitplan', 'wordpress'),
            array($this, 'cron_schedule_render'),
            'newsapi',
            'newsapi_section'
        );
    }

    // Einstellungsfelder rendern
    public function newsapi_key_render() {
        $options = get_option('newsapi_settings');
        ?>
        <input type='text' name='newsapi_settings[newsapi_newsapi_key]' value='<?php echo $options['newsapi_newsapi_key']; ?>'>
        <?php
    }

    public function openai_key_render() {
        $options = get_option('newsapi_settings');
        ?>
        <input type='text' name='newsapi_settings[newsapi_openai_key]' value='<?php echo $options['newsapi_openai_key']; ?>'>
        <?php
    }

    public function keyword_render() {
        $options = get_option('newsapi_settings');
        ?>
        <input type='text' name='newsapi_settings[newsapi_keyword]' value='<?php echo $options['newsapi_keyword']; ?>'>
        <?php
    }

    public function author_render() {
        $options = get_option('newsapi_settings');
        $selected_author = isset($options['newsapi_author']) ? $options['newsapi_author'] : '';

        $users = get_users(array('role__in' => array('administrator', 'editor', 'author')));
        ?>
        <select name="newsapi_settings[newsapi_author]">
            <?php foreach ($users as $user) { ?>
                <option value="<?php echo $user->ID; ?>" <?php selected($selected_author, $user->ID); ?>>
                    <?php echo esc_html($user->display_name); ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    public function max_articles_render() {
        $options = get_option('newsapi_settings');
        ?>
        <input type='number' name='newsapi_settings[newsapi_max_articles]' value='<?php echo isset($options['newsapi_max_articles']) ? $options['newsapi_max_articles'] : 5; ?>' min='1'>
        <?php
    }

    public function manual_mode_render() {
        $options = get_option('newsapi_settings');
        ?>
        <input type='checkbox' name='newsapi_settings[newsapi_manual_mode]' value='1' <?php checked(1, isset($options['newsapi_manual_mode']) ? $options['newsapi_manual_mode'] : 0); ?>>
        <?php
    }

    public function cron_schedule_render() {
        $options = get_option('newsapi_settings');
        $schedule = isset($options['newsapi_cron_schedule']) ? $options['newsapi_cron_schedule'] : 'hourly';
        ?>
        <select name="newsapi_settings[newsapi_cron_schedule]">
            <option value="hourly" <?php selected($schedule, 'hourly'); ?>>Stündlich</option>
            <option value="twicedaily" <?php selected($schedule, 'twicedaily'); ?>>Zweimal täglich</option>
            <option value="daily" <?php selected($schedule, 'daily'); ?>>Täglich</option>
        </select>
        <?php
    }

    public function settings_section_callback() {
        echo __('Gib die API-Schlüssel, das Keyword und den Autor ein.', 'wordpress');
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>NewsAPI Einstellungen</h2>
            <?php
            settings_fields('newsapi');
            do_settings_sections('newsapi');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Nachrichten abrufen, manuelle Funktion implementieren und Beiträge erstellen
    public function fetch_and_create_posts() {
        $options = get_option('newsapi_settings');
        $manual_mode = isset($options['newsapi_manual_mode']) ? $options['newsapi_manual_mode'] : 0;
        $articles = $this->fetch_news();

        if ($articles && !$manual_mode) {
            $max_articles = isset($options['newsapi_max_articles']) ? intval($options['newsapi_max_articles']) : 5;
            $count = 0;

            foreach ($articles as $article) {
                if ($count >= $max_articles) break;
                $this->create_post($article);
                $count++;
            }
        } elseif ($articles && $manual_mode) {
            // Zeige die Nachrichten zur manuellen Auswahl an
            $this->show_manual_approval($articles);
        }

        // Zeit der letzten Ausführung speichern
        update_option('newsapi_last_run', current_time('mysql'));
    }

    private function fetch_news() {
        $options = get_option('newsapi_settings');
        $newsapi_key = $options['newsapi_newsapi_key'];
        $keyword = $options['newsapi_keyword'];

        $url = 'https://newsapi.org/v2/everything?q=' . urlencode($keyword) . '&apiKey=' . $newsapi_key;
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        return $data->articles;
    }

    private function create_post($article) {
        $options = get_option('newsapi_settings');
        $author_id = isset($options['newsapi_author']) ? $options['newsapi_author'] : 1;

        $title = $article->title;
        $content = $this->translate_and_summarize($article->description);
        $tags = $this->generate_tags($article->title, $article->description);
        $meta_description = $this->generate_meta_description($article->description);
        $category_id = $this->find_matching_category($article);

        if (post_exists($title)) return;

        $post_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => $author_id,
            'post_category' => array($category_id)
        ));

        wp_set_post_tags($post_id, $tags);
        add_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
    }

    // SEO: Tags und Meta-Beschreibung generieren
    private function generate_tags($title, $description) {
        $keywords = array_unique(array_merge(explode(' ', $title), explode(' ', $description)));
        return array_slice($keywords, 0, 10);  // Maximal 10 Tags
    }

    private function generate_meta_description($description) {
        return substr($description, 0, 155);  // Maximal 155 Zeichen für die Meta-Beschreibung
    }

    private function translate_and_summarize($text) {
        $options = get_option('newsapi_settings');
        $openai_key = $options['newsapi_openai_key'];

        $openai_endpoint = 'https://api.openai.com/v1/completions';
        $prompt = "Du bist ein renommierter Journalist. Analysiere die folgende Nachricht und fasse sie auf Deutsch zusammen:\n\n$text";

        $body = json_encode(array(
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 150,
            'temperature' => 0.7,
        ));

        $response = wp_remote_post($openai_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type' => 'application/json',
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            return "Fehler bei der Übersetzung.";
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        return trim($response_body['choices'][0]['text']);
    }

    // Manuelle Nachrichtenüberprüfung anzeigen
    private function show_manual_approval($articles) {
        echo '<h2>Manuelle Überprüfung der Nachrichten</h2>';
        foreach ($articles as $article) {
            echo '<h3>' . esc_html($article->title) . '</h3>';
            echo '<p>' . esc_html($article->description) . '</p>';
            echo '<a href="' . esc_url(admin_url('admin-post.php?action=approve_article&article=' . urlencode(json_encode($article)))) . '">Hinzufügen</a>';
            echo '<hr>';
        }
    }

    // Skript-Verwaltungsseite für manuelle Ausführung und Anzeigedaten
    public function script_management_page() {
        $last_run = get_option('newsapi_last_run');
        echo '<h1>Skript-Verwaltung</h1>';
        echo '<p>Letzte Ausführung: ' . esc_html($last_run) . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="manual_run">';
        submit_button('Skript manuell ausführen');
        echo '</form>';
    }

    // Manuelle Ausführung des Skripts
    public function manual_run() {
        $this->fetch_and_create_posts();
        wp_redirect(admin_url('admin.php?page=script_management&run=success'));
        exit();
    }

    // Kategorie basierend auf dem Artikel finden
    private function find_matching_category($article) {
        $categories = get_categories(array('hide_empty' => 0));
        $article_keywords = explode(' ', $article->title . ' ' . $article->description);

        foreach ($categories as $category) {
            foreach ($article_keywords as $keyword) {
                if (stripos($category->name, $keyword) !== false) {
                    return $category->term_id;
                }
            }
        }

        // Standardkategorie, wenn keine gefunden wird
        return get_option('default_category');
    }
}

new NewsAPI();
