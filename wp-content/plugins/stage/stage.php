<?php
/*
Plugin Name: Mon Plugin
Description: Un plugin qui gère les administrateurs et enregistre les données dans la base de données.
Version: 1.0
Author: Votre Nom
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enqueue custom CSS for the plugin
function mp_enqueue_styles() {
    wp_enqueue_style('mp-custom-styles', plugin_dir_url(__FILE__) . 'css/stage.css');
}
add_action('wp_enqueue_scripts', 'mp_enqueue_styles');

// Créer le shortcode pour afficher le formulaire d'ajout d'administrateur
function mp_form_shortcode() {
    ob_start();
    ?>
    <form id="mp-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
        <input type="hidden" name="action" value="mp_save_form">
        <label for="nom">Nom:</label>
        <input type="text" id="nom" name="nom" required><br>
        <label for="prenom">Prénom:</label>
        <input type="text" id="prenom" name="prenom" required><br>
        <label for="motdepasse">Mot de passe:</label>
        <input type="password" id="motdepasse" name="motdepasse" required><br>
        <input type="submit" value="Envoyer">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('mp_form', 'mp_form_shortcode');

// Créer le shortcode pour afficher la liste des administrateurs
function mp_display_data_shortcode() {
    ob_start();
    mp_display_data();
    return ob_get_clean();
}
add_shortcode('mp_display_data', 'mp_display_data_shortcode');

// Fonction pour afficher la liste des administrateurs
function mp_display_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="mp-data-table">
        <h2>Administrateurs Enregistrés</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column column-columnname" scope="col">ID</th>
                    <th class="manage-column column-columnname" scope="col">Nom</th>
                    <th class="manage-column column-columnname" scope="col">Prénom</th>
                    <th class="manage-column column-columnname" scope="col">Mot de passe</th>
                    <th class="manage-column column-columnname" scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($results) {
                    foreach ($results as $row) {
                        echo "<tr>";
                        echo "<td>{$row->id}</td>";
                        echo "<td>{$row->nom}</td>";
                        echo "<td>{$row->prenom}</td>";
                        echo "<td>{$row->motdepasse}</td>";
                        echo "<td>
                                <a href='" . admin_url('admin.php?page=mp-edit-admin&id=' . $row->id) . "' class='button button-primary'>Modifier</a>
                                <a href='" . admin_url('admin.php?page=mon-plugin&action=delete&id=' . $row->id) . "' class='button button-secondary'>Supprimer</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Aucun administrateur trouvé.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Fonction pour gérer l'envoi du formulaire d'ajout d'administrateur
function mp_save_form() {
    if (isset($_POST['nom'], $_POST['prenom'], $_POST['motdepasse'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $motdepasse = sanitize_text_field($_POST['motdepasse']);

        $wpdb->insert(
            $table_name,
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'motdepasse' => $motdepasse,
            ]
        );

        wp_redirect(admin_url('admin.php?page=mon-plugin'));
        exit;
    }
}
add_action('admin_post_nopriv_mp_save_form', 'mp_save_form');
add_action('admin_post_mp_save_form', 'mp_save_form');

// Créer la table pour stocker les données des administrateurs à l'activation du plugin
function mp_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nom tinytext NOT NULL,
        prenom tinytext NOT NULL,
        motdepasse tinytext NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mp_create_table');

// Ajouter un élément de menu dans l'administration pour gérer les administrateurs
function mp_add_admin_menu() {
    add_menu_page(
        'Mon Plugin',
        'Mon Plugin',
        'manage_options',
        'mon-plugin',
        'mp_admin_page',
        'dashicons-admin-generic',
        6
    );

    // Ajouter une sous-page pour ajouter un nouvel administrateur
    add_submenu_page(
        'mon-plugin',
        'Ajouter un Administrateur',
        'Ajouter un Administrateur',
        'manage_options',
        'mp-add-admin',
        'mp_add_admin_page'
    );

    // Ajouter une sous-page pour modifier un administrateur existant
    add_submenu_page(
        'mon-plugin',
        'Modifier Administrateur',
        'Modifier Administrateur',
        'manage_options',
        'mp-edit-admin',
        'mp_edit_admin_page'
    );
}
add_action('admin_menu', 'mp_add_admin_menu');

// Afficher la page principale d'administration avec la liste des administrateurs
function mp_admin_page() {
    ?>
    <div class="wrap">
        <h1>Administrateurs Enregistrés</h1>
        <?php echo do_shortcode('[mp_display_data]'); ?>
        <button id="mp-add-admin-btn" class="button button-primary">Ajouter un Administrateur</button>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var addAdminBtn = document.getElementById('mp-add-admin-btn');

        addAdminBtn.addEventListener('click', function() {
            window.location.href = '<?php echo admin_url('admin.php?page=mp-add-admin'); ?>';
        });
    });
    </script>
    <?php
}

// Afficher la page d'ajout d'administrateur
function mp_add_admin_page() {
    ?>
    <div class="wrap">
        <h1>Ajouter un Nouvel Administrateur</h1>
        <?php echo do_shortcode('[mp_form]'); ?>
    </div>
    <?php
}

// Afficher la page de modification d'un administrateur
function mp_edit_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';

    if (isset($_GET['id'])) {
        $admin_id = intval($_GET['id']);
        $admin = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $admin_id");

        if ($admin) {
            ?>
            <div class="wrap">
                <h1>Modifier Administrateur</h1>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
                    <input type="hidden" name="action" value="mp_update_admin">
                    <input type="hidden" name="id" value="<?php echo $admin->id; ?>">
                    <label for="nom">Nom:</label>
                    <input type="text" id="nom" name="nom" value="<?php echo $admin->nom; ?>" required><br>
                    <label for="prenom">Prénom:</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo $admin->prenom; ?>" required><br>
                    <label for="motdepasse">Mot de passe:</label>
                    <input type="password" id="motdepasse" name="motdepasse" value="<?php echo $admin->motdepasse; ?>" required><br>
                    <input type="submit" value="Mettre à Jour" class="button button-primary">
                </form>
            </div>
            <?php
        } else {
            echo "<p>Administrateur non trouvé.</p>";
        }
    }
}

// Action pour mettre à jour un administrateur
function mp_update_admin() {
    if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['motdepasse'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $admin_id = intval($_POST['id']);
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $motdepasse = sanitize_text_field($_POST['motdepasse']);

        $wpdb->update(
            $table_name,
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'motdepasse' => $motdepasse,
            ],
            ['id' => $admin_id]
        );

        wp_redirect(admin_url('admin.php?page=mon-plugin'));
        exit;
    }
}
add_action('admin_post_mp_update_admin', 'mp_update_admin');

// Action pour supprimer un administrateur
function mp_delete_admin() {
    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $admin_id = intval($_GET['id']);
        $wpdb->delete($table_name, ['id' => $admin_id]);

        wp_redirect(admin_url('admin.php?page=mon-plugin'));
        exit;
    }
}
add_action('admin_init', 'mp_delete_admin');
?>
