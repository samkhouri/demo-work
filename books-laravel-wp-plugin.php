<?php
/*
Plugin Name: Book Manager with Laravel Integration
Description: Manages a "Books" custom post type and integrates with a Laravel microservice.
Version: 1.0
Author: Sam Khouri
*/

// Register the "Books" Custom Post Type
function book_manager_register_post_type() {
    $labels = array(
        'name'               => 'Books',
        'singular_name'      => 'Book',
        'add_new'            => 'Add New Book',
        'add_new_item'       => 'Add New Book',
        'edit_item'          => 'Edit Book',
        'new_item'           => 'New Book',
        'view_item'          => 'View Book',
        'search_items'       => 'Search Books',
        'not_found'          => 'No books found',
        'not_found_in_trash' => 'No books found in Trash',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'show_in_menu'        => true,
        'supports'            => array('title', 'editor', 'custom-fields', 'thumbnail'),
        'capability_type'     => 'post',
    );

    register_post_type('book', $args);
}
add_action('init', 'book_manager_register_post_type');

// Add Meta Box for Book Attributes (e.g., Genre)
function book_manager_add_meta_boxes() {
    add_meta_box(
        'book_attributes_meta',
        'Book Attributes',
        'book_manager_attributes_meta_box_html',
        'book',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'book_manager_add_meta_boxes');

function book_manager_attributes_meta_box_html($post) {
    $genre = get_post_meta($post->ID, 'genre', true);
    echo '<label for="book_genre">Genre:</label>';
    echo '<input type="text" id="book_genre" name="book_genre" value="' . esc_attr($genre) . '" style="width: 100%;" />';
}

// Save Meta Box Data
function book_manager_save_meta_box_data($post_id) {
    if (array_key_exists('book_genre', $_POST)) {
        update_post_meta($post_id, 'genre', sanitize_text_field($_POST['book_genre']));
    }
}
add_action('save_post', 'book_manager_save_meta_box_data');

// Fetch Recommendations from Laravel Microservice
function book_manager_fetch_recommendations($post_id) {
    if (get_post_type($post_id) !== 'book') {
        return;
    }

    $api_url = 'https://your-laravel-app.com/api/recommendations'; // Replace with your Laravel API endpoint
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log('Failed to fetch recommendations: ' . $response->get_error_message());
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['recommendation'])) {
        update_post_meta($post_id, 'recommendation', sanitize_text_field($data['recommendation']));
    }
}
add_action('save_post', 'book_manager_fetch_recommendations');

// Display Books on the Front-End
function book_manager_display_books($atts) {
    $args = array(
        'post_type'      => 'book',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p>No books available.</p>';
    }

    $output = '<div class="book-list">';
    while ($query->have_posts()) {
        $query->the_post();
        $genre = get_post_meta(get_the_ID(), 'genre', true);
        $recommendation = get_post_meta(get_the_ID(), 'recommendation', true);

        $output .= '<div class="book-item">';
        $output .= '<h2>' . get_the_title() . '</h2>';
        $output .= '<p>' . get_the_content() . '</p>';
        $output .= '<p><strong>Genre:</strong> ' . esc_html($genre) . '</p>';
        $output .= '<p><strong>Recommendation:</strong> ' . esc_html($recommendation) . '</p>';
        $output .= '</div>';
    }
    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
add_shortcode('display_books', 'book_manager_display_books');