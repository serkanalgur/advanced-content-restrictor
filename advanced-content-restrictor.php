<?php
/*
Plugin Name: Advanced Content Restrictor
Description: Restrict content with granular controls and modern UI.
Version: 1.0
Author: kaisercrazy
*/

// Main plugin class
class Advanced_Content_Restrictor {
    private $settings;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('template_redirect', [$this, 'content_restriction']);
    }

    // Add admin menu
    public function add_admin_menu() {
        add_menu_page(
            'Content Restrictions',
            'Content Restrictor',
            'manage_options',
            'content-restrictor',
            [$this, 'settings_page'],
            'dashicons-shield-alt',
            80
        );
    }

    // Register settings
    public function register_settings() {
        register_setting('acr_settings_group', 'acr_settings', [$this, 'sanitize_settings']);

        add_settings_section('acr_main_section', 'Restriction Settings', null, 'content-restrictor');

        add_settings_field(
            'restricted_singular',
            'Restrict Singular Content',
            [$this, 'singular_field'],
            'content-restrictor',
            'acr_main_section'
        );

        add_settings_field(
            'restricted_archives',
            'Restrict Archives',
            [$this, 'archive_field'],
            'content-restrictor',
            'acr_main_section'
        );

        add_settings_field(
            'restricted_pages',
            'Restrict Specific Pages',
            [$this, 'pages_field'],
            'content-restrictor',
            'acr_main_section'
        );

        add_settings_field(
            'redirect_page',
            'Redirect Page',
            [$this, 'redirect_field'],
            'content-restrictor',
            'acr_main_section'
        );
    }

    // Sanitize settings
    public function sanitize_settings($input) {
        $output = [];

        $output['singular'] = isset($input['singular']) ? array_map('sanitize_text_field', $input['singular']) : [];
        $output['archives'] = isset($input['archives']) ? array_map('sanitize_text_field', $input['archives']) : [];
        $output['pages'] = isset($input['pages']) ? array_map('sanitize_text_field', $input['pages']) : [];
        $output['redirect'] = isset($input['redirect']) ? absint($input['redirect']) : 0;

        return $output;
    }

    // Singular content field
    public function singular_field() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected = $this->get_setting('singular', []);
        ?>
        <select class="acr-select2" name="acr_settings[singular][]" multiple="multiple" style="width: 100%">
            <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type->name) ?>" <?php selected(in_array($post_type->name, $selected)) ?>>
                    <?php echo esc_html($post_type->label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select post types to restrict their individual entries</p>
        <?php
    }

    // Archive field
    public function archive_field() {
        $post_types = get_post_types(['public' => true, 'has_archive' => true], 'objects');
        $selected = $this->get_setting('archives', []);
        ?>
        <select class="acr-select2" name="acr_settings[archives][]" multiple="multiple" style="width: 100%">
            <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type->name) ?>" <?php selected(in_array($post_type->name, $selected)) ?>>
                    <?php echo esc_html($post_type->label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select post types to restrict their archive pages</p>
        <?php
    }

    // Pages field
    public function pages_field() {
        $pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
        $selected = $this->get_setting('pages', []);
        ?>
        <select class="acr-select2" name="acr_settings[pages][]" multiple="multiple" style="width: 100%">
            <?php foreach ($pages as $page) : ?>
                <option value="<?php echo esc_attr($page->post_name) ?>" <?php selected(in_array($page->post_name, $selected)) ?>>
                    <?php echo esc_html($page->post_title) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    // Redirect field
    public function redirect_field() {
        $pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
        $selected = $this->get_setting('redirect', 0);
        ?>
        <select name="acr_settings[redirect]" style="width: 100%">
            <option value="0">Default Login Page</option>
            <?php foreach ($pages as $page) : ?>
                <option value="<?php echo esc_attr($page->ID) ?>" <?php selected($selected, $page->ID) ?>>
                    <?php echo esc_html($page->post_title) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    // Settings page
    public function settings_page() {
        ?>
        <div class="wrap acr-settings-wrap">
            <h1>Content Restriction Settings</h1>

            <div class="acr-settings-card">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('acr_settings_group');
                    do_settings_sections('content-restrictor');
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    // Admin assets
    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_content-restrictor') return;

        // Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery']);

        // Custom admin CSS
        wp_add_inline_style('wp-admin', '
            .acr-settings-card {
                background: #fff;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-top: 2rem;
                max-width: 800px;
            }

            .acr-select2 + .select2-container {
                margin-bottom: 1rem;
                width: 100% !important;
            }

            .acr-settings-wrap .form-table th {
                width: 200px;
                padding: 20px 10px 20px 0;
            }

            .acr-settings-wrap .form-table td {
                padding: 15px 0;
            }

            .acr-settings-wrap .description {
                color: #666;
                font-style: italic;
                margin-top: 8px;
            }
        ');

        // Select2 initialization
        wp_add_inline_script('select2', '
            jQuery(document).ready(function($) {
                $(".acr-select2").select2({
                    width: "resolve",
                    placeholder: "Select items",
                    allowClear: true
                });
            });
        ');
    }

    // Content restriction logic
    public function content_restriction() {
        if (is_user_logged_in()) return;

        $settings = $this->get_settings();
        $redirect_url = $settings['redirect'] ? get_permalink($settings['redirect']) : wp_login_url();

        // Check singular restrictions
        if (!empty($settings['singular']) && is_singular($settings['singular'])) {
            wp_redirect($redirect_url);
            exit;
        }

        // Check archive restrictions
        if (!empty($settings['archives']) && is_post_type_archive($settings['archives'])) {
            wp_redirect($redirect_url);
            exit;
        }

        // Check page restrictions
        if (!empty($settings['pages']) && is_page($settings['pages'])) {
            wp_redirect($redirect_url);
            exit;
        }
    }

    // Helper to get settings
    private function get_settings() {
        if (!$this->settings) {
            $this->settings = wp_parse_args(
                get_option('acr_settings'),
                [
                    'singular' => [],
                    'archives' => [],
                    'pages' => [],
                    'redirect' => 0
                ]
            );
        }
        return $this->settings;
    }

    private function get_setting($key, $default = '') {
        $settings = $this->get_settings();
        return $settings[$key] ?? $default;
    }
}

new Advanced_Content_Restrictor();