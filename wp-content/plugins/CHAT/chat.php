<?php
/**
 * Plugin Name: Chat Residence Plugin
 * Plugin URI: http://yourwebsite.com
 * Description: A chat plugin to search residences by city, budget, or name.
 * Version: 1.0
 * Author: Ayoub
 * Author URI: http://yourwebsite.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// Shortcode for chat interface
function chat_residence_shortcode() {
    ob_start();
    ?>
    <div id="chat-container">
        <div id="chat-box">
            <div id="chat-log"></div>
            <div id="chat-buttons">
                <button class="chat-button" data-question="Quelle est la ville que vous voulez visiter ?">Ville</button>
                <button class="chat-button" data-question="Votre budget ?">Budget</button>
                <button class="chat-button" data-question="Quelle est le nom de la résidence ?">Nom de Résidence</button>
            </div>
            <input type="text" id="chat-input" placeholder="Type your message..."/>
            <button id="chat-send-button">Send</button>
        </div>
    </div>

    <style>
        #chat-container {
            border: 1px solid #ccc;
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            border-radius: 10px;
            position: relative;
        }
        #chat-box {
            display: flex;
            flex-direction: column;
            height: 500px;
        }
        #chat-log {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 10px;
        }
        #chat-buttons {
            display: flex;
            justify-content: space-around;
            margin-bottom: 10px;
        }
        .chat-button {
            padding: 10px 20px;
            border: none;
            background-color: #0073aa;
            color: white;
            border-radius: 10px;
            cursor: pointer;
        }
        #chat-input {
            flex: 0;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        #chat-send-button {
            flex: 0;
            padding: 10px 20px;
            border: none;
            background-color: #0073aa;
            color: white;
            border-radius: 10px;
            cursor: pointer;
        }
        .bot-message, .user-message {
            margin-bottom: 10px;
        }
        .bot-message {
            text-align: left;
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 10px;
        }
        .user-message {
            text-align: right;
            background-color: #d1e7dd;
            padding: 10px;
            border-radius: 10px;
        }
        .residence-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 10px;
        }
        .residence-image {
            max-width: 100px;
            max-height: 100px;
            margin-right: 10px;
        }
        .residence-details {
            flex: 1;
        }
        .residence-title {
            font-weight: bold;
        }
        .residence-address {
            font-size: 0.9em;
            color: #555;
        }
        .residence-price {
            color: green;
            font-weight: bold;
        }
        .residence-button {
            background-color: #0073aa;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            var currentQuestionType = 'city';

            function addChatMessage(message, isBot) {
                const chatLog = $('#chat-log');
                const messageClass = isBot ? 'bot-message' : 'user-message';
                chatLog.append('<div class="' + messageClass + '">' + message + '</div>');
                chatLog.scrollTop(chatLog[0].scrollHeight);
            }

            function fetchResidences(query, type) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'fetch_residences_by_query',
                        query: query,
                        type: type
                    },
                    success: function(response) {
                        if (response.success) {
                            addChatMessage(response.data, true);
                        } else {
                            addChatMessage(response.data ? response.data : 'Erreur de recherche.', true);
                        }
                    }
                });
            }

            function openDetailsInNewWindow(id) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'GET',
                    data: {
                        action: 'fetch_residence_details',
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            var newWindow = window.open('', '_blank');
                            newWindow.document.write(response.data);
                            newWindow.document.close();
                        } else {
                            alert('Erreur de chargement des détails.');
                        }
                    }
                });
            }

            $('.chat-button').on('click', function() {
                const question = $(this).data('question');
                currentQuestionType = $(this).text().toLowerCase();
                addChatMessage(question, true);
            });

            $('#chat-send-button').on('click', function() {
                const userMessage = $('#chat-input').val();
                if (userMessage) {
                    addChatMessage(userMessage, false);

                    if (currentQuestionType === 'budget') {
                        const budget = parseFloat(userMessage.replace(',', '.'));
                        if (!isNaN(budget) && budget > 0) {
                            fetchResidences(budget, 'budget');
                        } else {
                            addChatMessage('Vérifiez votre budget !!!', true);
                        }
                    } else if (currentQuestionType === 'nom de résidence') {
                        const residenceName = userMessage.trim();
                        fetchResidences(residenceName, 'name');
                    } else {
                        fetchResidences(userMessage, 'city');
                    }
                    $('#chat-input').val('');
                }
            });

            $('#chat-input').on('keypress', function(e) {
                if (e.which == 13) {
                    $('#chat-send-button').click();
                }
            });

            $(document).on('click', '.residence-button', function() {
                const residenceId = $(this).data('id');
                openDetailsInNewWindow(residenceId);
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('chat_residence', 'chat_residence_shortcode');

// Handle AJAX request to fetch residences
function fetch_residences_by_query() {
    if (!isset($_POST['query']) || !isset($_POST['type'])) {
        wp_send_json_error('Invalid request.');
    }

    $query = sanitize_text_field($_POST['query']);
    $type = sanitize_text_field($_POST['type']);
    $api_url = "https://admin.arpej.fr/api/wordpress/residences/";
    $auth_key = "wordpress";
    $auth_secret = "f4ae4d1a35cf653bed2e78623cc1cfd0";

    $response = wp_remote_get($api_url, [
        'headers' => [
            'X-Auth-Key' => $auth_key,
            'X-Auth-Secret' => $auth_secret
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching residences.');
    }

    $residences = json_decode(wp_remote_retrieve_body($response), true);

    $filtered_residences = array_filter($residences, function($residence) use ($query, $type) {
        switch ($type) {
            case 'budget':
                return $residence['preview']['rent_amount_from'] <= $query;
            case 'name':
                return stripos($residence['title'], $query) !== false;
            default:
                return stripos($residence['city'], $query) !== false;
        }
    });

    if (empty($filtered_residences)) {
        wp_send_json_error('Vérifiez la ville que vous voulez visiter !');
    }

    $result_html = '';
    foreach ($filtered_residences as $residence) {
        $result_html .= '<div class="residence-item">';
        $result_html .= '<img src="' . $residence['pictures'][0]['url'] . '" class="residence-image"/>';
        $result_html .= '<div class="residence-details">';
        $result_html .= '<div class="residence-title">' . $residence['title'] . '</div>';
        $result_html .= '<div class="residence-address">' . $residence['address'] . ', ' . $residence['city'] . '</div>';
        $result_html .= '<div class="residence-price">' . $residence['preview']['rent_amount_from'] . ' €</div>';
        $result_html .= '</div>';
        $result_html .= '<button class="residence-button" data-id="' . $residence['id'] . '">Voir Plus</button>';
        $result_html .= '</div>';
    }

    wp_send_json_success($result_html);
}
add_action('wp_ajax_fetch_residences_by_query', 'fetch_residences_by_query');
add_action('wp_ajax_nopriv_fetch_residences_by_query', 'fetch_residences_by_query');

// Handle AJAX request to fetch residence details
function fetch_residence_details() {
    if (!isset($_GET['id'])) {
        wp_send_json_error('Invalid request.');
    }

    $id = sanitize_text_field($_GET['id']);
    $api_url = "https://admin.arpej.fr/api/wordpress/residences/" . $id;
    $auth_key = "wordpress";
    $auth_secret = "f4ae4d1a35cf653bed2e78623cc1cfd0";

    $response = wp_remote_get($api_url, [
        'headers' => [
            'X-Auth-Key' => $auth_key,
            'X-Auth-Secret' => $auth_secret
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching residence details.');
    }

    $residence = json_decode(wp_remote_retrieve_body($response), true);

    if (!$residence) {
        wp_send_json_error('Residence not found.');
    }

    $details_html = '<html><head><title>Residence Details</title>';
    $details_html .= '<style>
        .details-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .details-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
        }
        .details-title {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
        }
        .details-info {
            margin-bottom: 10px;
        }
        .details-info strong {
            display: inline-block;
            width: 150px;
            color: #555;
        }
        .details-description {
            margin-top: 20px;
        }
        .details-description p {
            line-height: 1.6;
            color: #333;
        }
    </style></head><body>';
    $details_html .= '<div class="details-container">';
    $details_html .= '<img src="' . $residence['pictures'][0]['url'] . '" class="details-image"/>';
    $details_html .= '<div class="details-title">' . $residence['title'] . '</div>';
    $details_html .= '<div class="details-info"><strong>Address:</strong> ' . $residence['address'] . ', ' . $residence['city'] . '</div>';
    $details_html .= '<div class="details-info"><strong>Price:</strong> ' . $residence['preview']['rent_amount_from'] . ' €</div>';
    $details_html .= '<div class="details-info"><strong>Description:</strong> ' . $residence['description'] . '</div>';
    $details_html .= '</div></body></html>';

    wp_send_json_success($details_html);
}
add_action('wp_ajax_fetch_residence_details', 'fetch_residence_details');
add_action('wp_ajax_nopriv_fetch_residence_details', 'fetch_residence_details');
?>
