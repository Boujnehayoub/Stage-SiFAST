<?php
/* Template Name: Single Residence */

get_header(); 

if (isset($_GET['residence_id'])) {
    $residence_id = sanitize_text_field($_GET['residence_id']);

    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/' . $residence_id, [
        'headers' => [
            'X-Auth-Key' => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0'
        ]
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $residence = json_decode($body, true);

        if (!empty($residence)) {
            ?>
            <div class="residence-details">
                <h1><?php echo esc_html($residence['title']); ?></h1>
                <img src="<?php echo esc_url($residence['pictures'][0]['url']); ?>" alt="<?php echo esc_attr($residence['title']); ?>">
                <table>
                    <tr>
                        <th>Price</th>
                        <td><?php echo esc_html($residence['details']['price']); ?> €</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo esc_html($residence['address']); ?></td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td><?php echo esc_html($residence['city']); ?></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><?php echo esc_html($residence['description']); ?></td>
                    </tr>
                </table>
            </div>
            <?php
        } else {
            echo '<p>No details found for this residence.</p>';
        }
    } else {
        echo '<p>Error fetching residence details.</p>';
    }
} else {
    echo '<p>No residence ID provided.</p>';
}

get_footer();
?>
