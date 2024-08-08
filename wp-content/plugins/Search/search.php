<?php
/*
Plugin Name: Mon Plugin de Recherche
Description: Un plugin simple pour effectuer une recherche par nom.
Version: 1.0
Author: Votre Nom
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add the plugin to the WordPress settings menu
function mp_add_settings_page() {
    add_menu_page(
        'Mon Plugin de Recherche', // Title of the page
        'Plugin de Recherche',     // Menu text
        'manage_options',          // Capability required to view this page
        'mon-plugin-de-recherche', // Slug name to refer to this menu
        'mp_render_settings_page', // Function to display the content of the page
        'dashicons-search',        // Icon for the menu
        20                         // Position in the menu
    );
}
add_action('admin_menu', 'mp_add_settings_page');

// Render the settings page content
function mp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Mon Plugin de Recherche</h1>
        <form id="mp-search-form" action="<?php echo esc_url(admin_url('admin.php')); ?>" method="GET">
            <input type="hidden" name="page" value="mon-plugin-de-recherche">
            <label for="mp-search">Rechercher par nom:</label>
            <input type="text" id="mp-search" name="s" placeholder="Tapez votre recherche" required>
            <button type="submit">Rechercher</button>
        </form>
        
        <div id="mp-search-results">
            <!-- Results will be displayed here -->
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mp-search').on('input', function() {
            var searchQuery = $(this).val();

            // Make AJAX request
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mp_search_action',
                    search_query: searchQuery
                },
                success: function(response) {
                    $('#mp-search-results').html(response);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX Handler for search
add_action('wp_ajax_mp_search_action', 'mp_search_action_callback');
add_action('wp_ajax_nopriv_mp_search_action', 'mp_search_action_callback');

function mp_search_action_callback() {
    if (isset($_POST['search_query'])) {
        global $wpdb;
        $search_query = sanitize_text_field($_POST['search_query']);
        $table_name = $wpdb->prefix . 'mp_data';

        // Query to fetch names starting with the search query
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE nom LIKE %s",
                $wpdb->esc_like($search_query) . '%'
            )
        );

        if ($results) {
            echo '<h2>Résultats de la recherche pour "' . esc_html($search_query) . '":</h2>';
            echo '<ul>';
            foreach ($results as $result) {
                echo '<li>' . esc_html($result->nom) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aucun résultat trouvé pour "' . esc_html($search_query) . '".</p>';
        }
    }
    wp_die();
}
?>
