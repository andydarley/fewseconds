<?php
/**
 * Plugin Name: Daily Photo Projects
 * Description: Displays one-photo-per-day projects (v1: simple_days) under /dailypix/{year}-{name}/
 * Version: 0.1.1
 * Author: Andy Darley (scope, planning), ChatGPT (coding)
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

final class DailyPix_Plugin {
    const CPT = 'dailypix_entry';
    const TAX = 'dailypix_project';

    const META_DATE = 'dailypix_date';        // YYYY-MM-DD
    const META_SRC  = 'dailypix_source_url';  // Optional; generic (not Instagram-specific)

    // Term meta (v1)
    const TM_YEAR   = 'dailypix_year';
    const TM_SHORT  = 'dailypix_short_name';
    const TM_TITLE  = 'dailypix_display_title';
    const TM_DESC   = 'dailypix_description';
    const TM_VIDEO  = 'dailypix_project_video_url';

    public static function init(): void {
        add_action('init', [__CLASS__, 'register']);
        add_action('init', [__CLASS__, 'add_rewrites']);
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_filter('template_include', [__CLASS__, 'template_loader']);

        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_' . self::CPT, [__CLASS__, 'save_entry_meta'], 10, 2);

        add_action(self::TAX . '_add_form_fields', [__CLASS__, 'term_add_fields']);
        add_action(self::TAX . '_edit_form_fields', [__CLASS__, 'term_edit_fields']);
        add_action('created_' . self::TAX, [__CLASS__, 'term_save_fields']);
        add_action('edited_' . self::TAX, [__CLASS__, 'term_save_fields']);

        add_filter('post_type_link', [__CLASS__, 'entry_permalink'], 10, 2);
    }

    public static function activate(): void {
        self::register();
        self::add_rewrites();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function register(): void {
        register_post_type(self::CPT, [
            'label' => 'DailyPix Entries',
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => false, // we provide our own rewrites
            'supports' => ['title', 'thumbnail', 'editor'],
            'menu_icon' => 'dashicons-format-image',
        ]);

        register_taxonomy(self::TAX, [self::CPT], [
            'label' => 'DailyPix Projects',
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'rewrite' => false, // we provide our own rewrites
        ]);
    }

    public static function add_rewrites(): void {
        // Index
        add_rewrite_rule('^dailypix/?$', 'index.php?dailypix_view=index', 'top');

        // Project: /dailypix/{project-slug}/ where project slug is {year}-{name} (e.g. 2011-days)
        add_rewrite_rule('^dailypix/([a-z0-9\-]+)/?$', 'index.php?dailypix_view=project&dailypix_project=$matches[1]', 'top');

        // Entry: /dailypix/{project-slug}/{post-slug}/
        add_rewrite_rule('^dailypix/([a-z0-9\-]+)/([a-z0-9\-]+)/?$', 'index.php?dailypix_view=entry&dailypix_project=$matches[1]&dailypix_entry=$matches[2]', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'dailypix_view';
        $vars[] = 'dailypix_project';
        $vars[] = 'dailypix_entry';
        return $vars;
    }

    private static function plugin_template(string $file): string {
        return plugin_dir_path(__FILE__) . 'templates/' . $file;
    }

    public static function template_loader(string $template): string {
        $view = get_query_var('dailypix_view');
        if (!$view) return $template;

        if ($view === 'index')   return self::plugin_template('index.php');
        if ($view === 'project') return self::plugin_template('project.php');
        if ($view === 'entry')   return self::plugin_template('single.php');

        return $template;
    }

    /** Permalinks for entries are built from their project term slug + post_name. */
    public static function entry_permalink(string $permalink, WP_Post $post): string {
        if ($post->post_type !== self::CPT) return $permalink;

        $terms = wp_get_post_terms($post->ID, self::TAX);
        if (is_wp_error($terms) || empty($terms)) return $permalink;

        // v1 assumption: exactly one project per entry
        $project_slug = $terms[0]->slug;

        return home_url(user_trailingslashit("dailypix/{$project_slug}/{$post->post_name}"));
    }

    /* -------------------------
     * Entry meta box (date + source URL)
     * ------------------------- */

    public static function add_meta_boxes(): void {
        add_meta_box(
            'dailypix_entry_meta',
            'DailyPix Entry',
            [__CLASS__, 'render_entry_meta_box'],
            self::CPT,
            'side',
            'high'
        );
    }

    public static function render_entry_meta_box(WP_Post $post): void {
        $date = get_post_meta($post->ID, self::META_DATE, true);
        $src  = get_post_meta($post->ID, self::META_SRC, true);

        wp_nonce_field('dailypix_save_entry_meta', 'dailypix_entry_meta_nonce');

        echo '<p><label for="dailypix_date"><strong>Date</strong> (YYYY-MM-DD)</label><br>';
        echo '<input type="date" id="dailypix_date" name="dailypix_date" value="' . esc_attr($date) . '" style="width:100%"></p>';

        echo '<p><label for="dailypix_source_url"><strong>Source URL</strong> (optional)</label><br>';
        echo '<input type="url" id="dailypix_source_url" name="dailypix_source_url" value="' . esc_attr($src) . '" style="width:100%"></p>';
    }

    public static function save_entry_meta(int $post_id, WP_Post $post): void {
        if (!isset($_POST['dailypix_entry_meta_nonce']) || !wp_verify_nonce($_POST['dailypix_entry_meta_nonce'], 'dailypix_save_entry_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Date
        $date = isset($_POST['dailypix_date']) ? sanitize_text_field($_POST['dailypix_date']) : '';
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Invalid format: do not save
        } else {
            update_post_meta($post_id, self::META_DATE, $date);
        }

        // Source URL (optional)
        $src = isset($_POST['dailypix_source_url']) ? esc_url_raw($_POST['dailypix_source_url']) : '';
        update_post_meta($post_id, self::META_SRC, $src);
    }

    /* -------------------------
     * Term meta fields (project)
     * ------------------------- */

    public static function term_add_fields(): void {
        ?>
        <div class="form-field">
            <label for="dailypix_year">Year</label>
            <input type="number" name="dailypix_year" id="dailypix_year" min="1900" max="2100" />
            <p class="description">Numeric year, e.g. 2011.</p>
        </div>
        <div class="form-field">
            <label for="dailypix_short_name">Short name</label>
            <input type="text" name="dailypix_short_name" id="dailypix_short_name" />
            <p class="description">e.g. days, dice. URL uses the term slug (recommended: {year}-{name}).</p>
        </div>
        <div class="form-field">
            <label for="dailypix_display_title">Display title</label>
            <input type="text" name="dailypix_display_title" id="dailypix_display_title" />
        </div>
        <div class="form-field">
            <label for="dailypix_description">Tile description</label>
            <textarea name="dailypix_description" id="dailypix_description" rows="3"></textarea>
        </div>
        <div class="form-field">
            <label for="dailypix_project_video_url">Project video URL (optional)</label>
            <input type="url" name="dailypix_project_video_url" id="dailypix_project_video_url" />
            <p class="description">If set, shown on every day page for this project.</p>
        </div>
        <?php
    }

    public static function term_edit_fields(WP_Term $term): void {
        $year  = get_term_meta($term->term_id, self::TM_YEAR, true);
        $short = get_term_meta($term->term_id, self::TM_SHORT, true);
        $title = get_term_meta($term->term_id, self::TM_TITLE, true);
        $desc  = get_term_meta($term->term_id, self::TM_DESC, true);
        $video = get_term_meta($term->term_id, self::TM_VIDEO, true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="dailypix_year">Year</label></th>
            <td><input type="number" name="dailypix_year" id="dailypix_year" min="1900" max="2100" value="<?php echo esc_attr($year); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="dailypix_short_name">Short name</label></th>
            <td>
                <input type="text" name="dailypix_short_name" id="dailypix_short_name" value="<?php echo esc_attr($short); ?>" />
                <p class="description">URL uses the term slug (recommended: <?php echo esc_html($term->slug); ?>).</p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="dailypix_display_title">Display title</label></th>
            <td><input type="text" name="dailypix_display_title" id="dailypix_display_title" value="<?php echo esc_attr($title); ?>" /></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="dailypix_description">Tile description</label></th>
            <td><textarea name="dailypix_description" id="dailypix_description" rows="3"><?php echo esc_textarea($desc); ?></textarea></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="dailypix_project_video_url">Project video URL (optional)</label></th>
            <td>
                <input type="url" name="dailypix_project_video_url" id="dailypix_project_video_url" value="<?php echo esc_attr($video); ?>" />
                <p class="description">If set, shown on every day page for this project.</p>
            </td>
        </tr>
        <?php
    }

    public static function term_save_fields(int $term_id): void {
        if (isset($_POST['dailypix_year'])) {
            update_term_meta($term_id, self::TM_YEAR, (int) $_POST['dailypix_year']);
        }
        if (isset($_POST['dailypix_short_name'])) {
            update_term_meta($term_id, self::TM_SHORT, sanitize_title($_POST['dailypix_short_name']));
        }
        if (isset($_POST['dailypix_display_title'])) {
            update_term_meta($term_id, self::TM_TITLE, sanitize_text_field($_POST['dailypix_display_title']));
        }
        if (isset($_POST['dailypix_description'])) {
            update_term_meta($term_id, self::TM_DESC, sanitize_textarea_field($_POST['dailypix_description']));
        }
        if (isset($_POST['dailypix_project_video_url'])) {
            update_term_meta($term_id, self::TM_VIDEO, esc_url_raw($_POST['dailypix_project_video_url']));
        }
    }

    /* ---------------
     * Helper utilities
     * --------------- */

    public static function get_project_term_by_slug(string $slug): ?WP_Term {
        $term = get_term_by('slug', $slug, self::TAX);
        return ($term && !is_wp_error($term)) ? $term : null;
    }

    public static function get_project_display_title(WP_Term $term): string {
        $t = (string) get_term_meta($term->term_id, self::TM_TITLE, true);
        return $t !== '' ? $t : $term->name;
    }
}

DailyPix_Plugin::init();

register_activation_hook(__FILE__, ['DailyPix_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['DailyPix_Plugin', 'deactivate']);
