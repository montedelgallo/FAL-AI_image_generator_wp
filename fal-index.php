<?php
/**
 * Plugin Name: FAL AI Image Generator
 * Description: WordPress plugin to generate images using FAL AI API
 * Version: 0.1.0
 * Author: Giovanni Panasiti
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

class FAL_AI_Image_Generator
{
  // Singleton instance
  private static $instance = null;

  // Constructor
  private function __construct()
  {
    // Initialize hooks
    add_action('admin_menu', array($this, 'register_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

    // Check for pending requests on admin page load
    add_action('admin_init', array($this, 'check_pending_requests'));
  }

  // Get singleton instance
  public static function get_instance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  // This method has been moved outside the class as a global function

  // Register admin menu items
  public function register_admin_menu()
  {
    add_menu_page(
      'FAL AI Image Generator',
      'FAL AI Images',
      'manage_options',
      'fal-ai-images',
      array($this, 'render_images_page'),
      'dashicons-format-image',
      30
    );

    add_submenu_page(
      'fal-ai-images',
      'Generated Images',
      'Generated Images',
      'manage_options',
      'fal-ai-images',
      array($this, 'render_images_page')
    );

    add_submenu_page(
      'fal-ai-images',
      'Generate New Images',
      'Generate New',
      'manage_options',
      'fal-ai-new',
      array($this, 'render_generate_page')
    );

    add_submenu_page(
      'fal-ai-images',
      'Settings',
      'Settings',
      'manage_options',
      'fal-ai-settings',
      array($this, 'render_settings_page')
    );
  }

  // Register plugin settings
  public function register_settings()
  {
    register_setting('fal-ai-settings-group', 'fal_ai_api_key');

    add_settings_section(
      'fal_ai_settings_section',
      'API Settings',
      array($this, 'settings_section_callback'),
      'fal-ai-settings'
    );

    add_settings_field(
      'fal_ai_api_key',
      'FAL AI API Key',
      array($this, 'api_key_field_callback'),
      'fal-ai-settings',
      'fal_ai_settings_section'
    );
  }

  // Settings section description
  public function settings_section_callback()
  {
    echo '<p>Enter your FAL AI API key to start generating images.</p>';
  }

  // API key field
  public function api_key_field_callback()
  {
    $api_key = get_option('fal_ai_api_key');
    echo '<input type="text" id="fal_ai_api_key" name="fal_ai_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
  }

  // Enqueue admin scripts and styles
  public function enqueue_admin_scripts($hook)
  {
    if (strpos($hook, 'fal-ai') !== false) {
      wp_enqueue_style('fal-ai-admin-css', plugins_url('admin.css', __FILE__));
      wp_enqueue_script('fal-ai-admin-js', plugins_url('admin.js', __FILE__), array('jquery'), null, true);

      // Add inline CSS if external file not available
      wp_add_inline_style('fal-ai-admin-css', '
                .fal-ai-images-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                .fal-ai-images-table th, .fal-ai-images-table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    text-align: left;
                }
                .fal-ai-images-table th {
                    background-color: #f2f2f2;
                }
                .fal-ai-images-table img {
                    max-width: 150px;
                    height: auto;
                }
                .fal-ai-generate-form {
                    max-width: 600px;
                    margin-top: 20px;
                }
                .fal-ai-generate-form label {
                    display: block;
                    margin-top: 10px;
                }
                .fal-ai-generate-form input[type="text"],
                .fal-ai-generate-form textarea,
                .fal-ai-generate-form select {
                    width: 100%;
                    padding: 8px;
                }
                .fal-ai-generate-form input[type="number"] {
                    width: 100px;
                }
                .fal-ai-status-in-queue {
                    color: #856404;
                    background-color: #fff3cd;
                    padding: 3px 8px;
                    border-radius: 3px;
                }
                .fal-ai-status-in-progress {
                    color: #0c5460;
                    background-color: #d1ecf1;
                    padding: 3px 8px;
                    border-radius: 3px;
                }
                .fal-ai-status-completed {
                    color: #155724;
                    background-color: #d4edda;
                    padding: 3px 8px;
                    border-radius: 3px;
                }
                .fal-ai-status-failed {
                    color: #721c24;
                    background-color: #f8d7da;
                    padding: 3px 8px;
                    border-radius: 3px;
                }
            ');

      // Add inline JavaScript for AJAX functionality
      wp_add_inline_script('fal-ai-admin-js', '
                jQuery(document).ready(function($) {
                    // Form submission
                    $("#fal-ai-generate-form").on("submit", function(e) {
                        e.preventDefault();
                        
                        var prompt = $("#fal-ai-prompt").val();
                        var numImages = $("#fal-ai-num-images").val();
                        var model = $("#fal-ai-model").val();
                        var imageSize = $("#fal-ai-image-size").val();
                        
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "fal_ai_generate_images",
                                prompt: prompt,
                                num_images: numImages,
                                model: model,
                                image_size: imageSize,
                                nonce: $("#fal_ai_nonce").val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert("Image generation request submitted successfully!");
                                    window.location.href = "?page=fal-ai-images";
                                } else {
                                    alert("Error: " + response.data);
                                }
                            },
                            error: function() {
                                alert("An error occurred while submitting the request.");
                            }
                        });
                    });
                    
                    // Refresh status periodically on images page
                    if ($("#fal-ai-images-table").length > 0) {
                        setInterval(function() {
                            $.ajax({
                                url: ajaxurl,
                                type: "POST",
                                data: {
                                    action: "fal_ai_check_pending_requests",
                                    nonce: $("#fal_ai_nonce").val()
                                },
                                success: function(response) {
                                    if (response.success && response.data.refresh) {
                                        location.reload();
                                    }
                                }
                            });
                        }, 10000); // Check every 10 seconds
                    }
                });
            ');

      // Localize the script with the ajax_url
      wp_localize_script('fal-ai-admin-js', 'fal_ai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fal_ai_nonce')
      ));
    }
  }

  // Render the settings page
  public function render_settings_page()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <form action="options.php" method="post">
        <?php
        settings_fields('fal-ai-settings-group');
        do_settings_sections('fal-ai-settings');
        submit_button('Save Settings');
        ?>
      </form>
    </div>
    <?php
  }

  // Render the images listing page
  public function render_images_page()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'fal_ai_requests';
    $table_images = $wpdb->prefix . 'fal_ai_images';

    // Get all requests with their images
    $requests = $wpdb->get_results("
            SELECT r.*, COUNT(i.id) as image_count
            FROM $table_requests as r
            LEFT JOIN $table_images as i ON r.request_id = i.request_id
            GROUP BY r.id
            ORDER BY r.created_at DESC
        ");

    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

      <a href="?page=fal-ai-new" class="button button-primary">Generate New Images</a>

      <table class="wp-list-table widefat fixed striped fal-ai-images-table" id="fal-ai-images-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Prompt</th>
            <th>Model</th>
            <th>Image Size</th>
            <th>Images</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($requests)): ?>
            <tr>
              <td colspan="6">No image generation requests found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($requests as $request): ?>
              <tr>
                <td><?php echo esc_html($request->request_id); ?></td>
                <td><?php echo esc_html($request->prompt); ?></td>
                <td><?php echo esc_html($request->model); ?></td>
                <td><?php echo esc_html($request->image_size); ?></td>
                <td>
                  <?php
                  if ($request->status === 'COMPLETED') {
                    echo esc_html($request->image_count);
                  } else {
                    echo 'Pending';
                  }
                  ?>
                </td>
                <td>
                  <span class="fal-ai-status-<?php echo strtolower($request->status); ?>">
                    <?php echo esc_html($request->status); ?>
                  </span>
                </td>
                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($request->created_at))); ?></td>
                <td>
                  <a href="?page=fal-ai-images&action=view&request_id=<?php echo esc_attr($request->request_id); ?>"
                    class="button">View Images</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <?php
      // Display images for a specific request
      if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['request_id'])) {
        $request_id = sanitize_text_field($_GET['request_id']);

        $images = $wpdb->get_results($wpdb->prepare("
                    SELECT *
                    FROM $table_images
                    WHERE request_id = %s
                    ORDER BY id ASC
                ", $request_id));

        // Get request details
        $request = $wpdb->get_row($wpdb->prepare("
                    SELECT *
                    FROM $table_requests
                    WHERE request_id = %s
                ", $request_id));

        if ($request) {
          ?>
          <div class="fal-ai-image-details">
            <h2>Images for Request: <?php echo esc_html($request_id); ?></h2>
            <p><strong>Prompt:</strong> <?php echo esc_html($request->prompt); ?></p>
            <p><strong>Status:</strong>
              <span class="fal-ai-status-<?php echo strtolower($request->status); ?>">
                <?php echo esc_html($request->status); ?>
              </span>
            </p>

            <div class="fal-ai-image-grid">
              <?php if (empty($images)): ?>
                <p>No images found for this request. If the status is still in queue or in progress, the images will appear here
                  once they are generated.</p>
              <?php else: ?>
                <?php foreach ($images as $image): ?>
                  <div class="fal-ai-image-item">
                    <img src="<?php echo esc_url($image->image_url); ?>" alt="Generated image">
                    <div class="fal-ai-image-info">
                      <p><strong>Size:</strong> <?php echo esc_html($image->width); ?> x <?php echo esc_html($image->height); ?></p>
                      <p><strong>Type:</strong> <?php echo esc_html($image->content_type); ?></p>
                      <p><strong>Seed:</strong> <?php echo esc_html($image->seed); ?></p>
                      <p><strong>NSFW:</strong> <?php echo $image->has_nsfw ? 'Yes' : 'No'; ?></p>
                      <a href="<?php echo esc_url($image->image_url); ?>" class="button" target="_blank">Open Full Size</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <style>
            .fal-ai-image-grid {
              display: flex;
              flex-wrap: wrap;
              gap: 20px;
              margin-top: 20px;
            }

            .fal-ai-image-item {
              border: 1px solid #ddd;
              padding: 10px;
              border-radius: 4px;
              width: calc(33.33% - 20px);
              box-sizing: border-box;
            }

            .fal-ai-image-item img {
              max-width: 100%;
              height: auto;
              display: block;
              margin-bottom: 10px;
            }

            .fal-ai-image-info p {
              margin: 5px 0;
            }

            @media (max-width: 1200px) {
              .fal-ai-image-item {
                width: calc(50% - 20px);
              }
            }

            @media (max-width: 768px) {
              .fal-ai-image-item {
                width: 100%;
              }
            }
          </style>
          <?php
        } else {
          echo '<div class="notice notice-error"><p>Request not found.</p></div>';
        }
      }
      ?>

      <input type="hidden" id="fal_ai_nonce" value="<?php echo wp_create_nonce('fal_ai_nonce'); ?>">
    </div>
    <?php
  }

  // Render the generate new images page
  public function render_generate_page()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $api_key = get_option('fal_ai_api_key');
    if (empty($api_key)) {
      ?>
      <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="notice notice-error">
          <p>Please configure your FAL AI API key in the <a href="?page=fal-ai-settings">Settings</a> page before generating
            images.</p>
        </div>
      </div>
      <?php
      return;
    }

    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

      <form id="fal-ai-generate-form" class="fal-ai-generate-form">
        <div class="form-field">
          <label for="fal-ai-prompt">Prompt:</label>
          <textarea id="fal-ai-prompt" name="prompt" rows="5" required></textarea>
          <p class="description">Describe what you want to generate</p>
        </div>

        <div class="form-field">
          <label for="fal-ai-num-images">Number of Images:</label>
          <input type="number" id="fal-ai-num-images" name="num_images" min="1" max="10" value="1" required>
        </div>

        <div class="form-field">
          <label for="fal-ai-model">Model:</label>
          <select id="fal-ai-model" name="model">
            <option value="fast-sdxl">fast-sdxl</option>
            <option value="flux/dev">flux/dev</option>
            <!-- Add more models as needed -->
          </select>
        </div>

        <div class="form-field">
          <label for="fal-ai-image-size">Image Size:</label>
          <select id="fal-ai-image-size" name="image_size">
            <option value="square_hd">Square HD</option>
            <option value="square">Square</option>
            <option value="portrait_4_3">Portrait (4:3)</option>
            <option value="portrait_16_9">Portrait (16:9)</option>
            <option value="landscape_4_3">Landscape (4:3)</option>
            <option value="landscape_16_9">Landscape (16:9)</option>
          </select>
        </div>

        <div class="form-field">
          <input type="hidden" id="fal_ai_nonce" name="fal_ai_nonce" value="<?php echo wp_create_nonce('fal_ai_nonce'); ?>">
          <button type="submit" class="button button-primary">Generate Images</button>
        </div>
      </form>
    </div>
    <?php
  }

  // Check for pending requests and update their status
  public function check_pending_requests()
  {
    // Only run on the images page
    if (!isset($_GET['page']) || $_GET['page'] !== 'fal-ai-images') {
      return;
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'fal_ai_requests';

    // Get all pending requests
    $pending_requests = $wpdb->get_results("
            SELECT *
            FROM $table_requests
            WHERE status NOT IN ('COMPLETED', 'FAILED')
            ORDER BY created_at ASC
        ");

    if (empty($pending_requests)) {
      return;
    }

    $api_key = get_option('fal_ai_api_key');
    if (empty($api_key)) {
      return;
    }

    $updated = false;

    foreach ($pending_requests as $request) {
      $request_id = $request->request_id;
      $status_url = $request->status_url;

      $response = wp_remote_get($status_url, array(
        'headers' => array(
          'Authorization' => 'Key ' . $api_key,
        ),
      ));

      if (is_wp_error($response)) {
        continue;
      }

      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);

      if (!isset($data['status'])) {
        continue;
      }

      // Update status if changed
      if ($data['status'] !== $request->status) {
        $wpdb->update(
          $table_requests,
          array('status' => $data['status']),
          array('request_id' => $request_id),
          array('%s'),
          array('%s')
        );
        $updated = true;
      }

      // If status is COMPLETED, get the images
      if ($data['status'] === 'COMPLETED') {
        $this->save_completed_images($request);
      }
    }

    // If this is an AJAX request, return status
    if (defined('DOING_AJAX') && DOING_AJAX) {
      wp_send_json_success(array('refresh' => $updated));
    }
  }

  // Save images for completed requests
  private function save_completed_images($request)
  {
    global $wpdb;
    $table_images = $wpdb->prefix . 'fal_ai_images';

    $api_key = get_option('fal_ai_api_key');
    $request_id = $request->request_id;
    $response_url = $request->response_url;

    $response = wp_remote_get($response_url, array(
      'headers' => array(
        'Authorization' => 'Key ' . $api_key,
      ),
    ));

    if (is_wp_error($response)) {
      return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['images']) || !is_array($data['images'])) {
      return;
    }

    // Save each image
    foreach ($data['images'] as $index => $image) {
      // Check if image already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_images WHERE request_id = %s AND image_url = %s",
        $request_id,
        $image['url']
      ));

      if ($existing) {
        continue;
      }

      $wpdb->insert(
        $table_images,
        array(
          'request_id' => $request_id,
          'image_url' => $image['url'],
          'width' => isset($image['width']) ? $image['width'] : null,
          'height' => isset($image['height']) ? $image['height'] : null,
          'content_type' => isset($image['content_type']) ? $image['content_type'] : null,
          'seed' => isset($data['seed']) ? $data['seed'] : null,
          'has_nsfw' => isset($data['has_nsfw_concepts'][$index]) ? ($data['has_nsfw_concepts'][$index] ? 1 : 0) : 0,
        ),
        array(
          '%s',
          '%s',
          '%d',
          '%d',
          '%s',
          '%d',
          '%d',
        )
      );
    }
  }
}

// Initialize AJAX handlers
function fal_ai_ajax_handlers()
{
  // Handle image generation requests
  add_action('wp_ajax_fal_ai_generate_images', 'fal_ai_generate_images_handler');

  // Handle checking pending requests
  add_action('wp_ajax_fal_ai_check_pending_requests', 'fal_ai_check_pending_requests_handler');
}
add_action('init', 'fal_ai_ajax_handlers');

// AJAX handler for generating images
function fal_ai_generate_images_handler()
{
  check_ajax_referer('fal_ai_nonce', 'nonce');

  if (!current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
  }

  $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
  $num_images = isset($_POST['num_images']) ? intval($_POST['num_images']) : 1;
  $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'fast-sdxl';
  $image_size = isset($_POST['image_size']) ? sanitize_text_field($_POST['image_size']) : 'square';

  if (empty($prompt)) {
    wp_send_json_error('Prompt is required');
  }

  // Limit number of images
  if ($num_images < 1) {
    $num_images = 1;
  } elseif ($num_images > 10) {
    $num_images = 10;
  }

  $api_key = get_option('fal_ai_api_key');
  if (empty($api_key)) {
    wp_send_json_error('API key is not configured');
  }

  // Make API request
  $response = wp_remote_post("https://queue.fal.run/fal-ai/{$model}", array(
    'headers' => array(
      'Authorization' => 'Key ' . $api_key,
      'Content-Type' => 'application/json',
    ),
    'body' => json_encode(array(
      'prompt' => $prompt,
      'num_images' => $num_images,
      'image_size' => isset($_POST['image_size']) ? sanitize_text_field($_POST['image_size']) : 'square',
    )),
  ));

  if (is_wp_error($response)) {
    wp_send_json_error('API request failed: ' . $response->get_error_message());
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if (!isset($data['request_id'])) {
    wp_send_json_error('Invalid API response');
  }

  // Save request to database
  global $wpdb;
  $table_requests = $wpdb->prefix . 'fal_ai_requests';

  $wpdb->insert(
    $table_requests,
    array(
      'request_id' => $data['request_id'],
      'prompt' => $prompt,
      'num_images' => $num_images,
      'model' => $model,
      'image_size' => isset($_POST['image_size']) ? sanitize_text_field($_POST['image_size']) : 'square',
      'status' => 'IN_QUEUE',
      'status_url' => $data['status_url'],
      'response_url' => $data['response_url'],
    ),
    array(
      '%s',
      '%s',
      '%d',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
    )
  );

  if ($wpdb->last_error) {
    wp_send_json_error('Database error: ' . $wpdb->last_error);
  }

  wp_send_json_success($data);
}

// AJAX handler for checking pending requests
function fal_ai_check_pending_requests_handler()
{
  check_ajax_referer('fal_ai_nonce', 'nonce');

  if (!current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
  }

  // Call the check_pending_requests method
  $plugin = FAL_AI_Image_Generator::get_instance();
  $plugin->check_pending_requests();

  // This will be handled inside the method
}

// Register activation hook to create database tables
register_activation_hook(__FILE__, 'fal_ai_create_database_tables');

// Database table creation function
function fal_ai_create_database_tables()
{
  global $wpdb;

  $charset_collate = $wpdb->get_charset_collate();

  // Table for requests
  $table_requests = $wpdb->prefix . 'fal_ai_requests';
  $table_images = $wpdb->prefix . 'fal_ai_images';

  $sql_requests = "CREATE TABLE $table_requests (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        request_id varchar(255) NOT NULL,
        prompt text NOT NULL,
        num_images int NOT NULL,
        model varchar(255) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'IN_QUEUE',
        status_url varchar(255),
        response_url varchar(255),
        image_size varchar(50) NOT NULL DEFAULT 'square',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

  $sql_images = "CREATE TABLE $table_images (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        request_id varchar(255) NOT NULL,
        image_url varchar(255) NOT NULL,
        width int,
        height int,
        content_type varchar(50),
        seed bigint,
        has_nsfw tinyint(1),
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql_requests);
  dbDelta($sql_images);
}

// Check if tables exist and create them if they don't
function fal_ai_check_tables()
{
  global $wpdb;

  $table_requests = $wpdb->prefix . 'fal_ai_requests';
  $table_images = $wpdb->prefix . 'fal_ai_images';

  // Check if tables exist
  $requests_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_requests'") === $table_requests;
  $images_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_images'") === $table_images;

  // If either table doesn't exist, create both
  if (!$requests_exists || !$images_exists) {
    fal_ai_create_database_tables();
  }
}

// Initialize the plugin
function fal_ai_init()
{
  // Check and create tables if needed
  fal_ai_check_tables();

  // Initialize the plugin instance
  FAL_AI_Image_Generator::get_instance();
}
add_action('plugins_loaded', 'fal_ai_init');