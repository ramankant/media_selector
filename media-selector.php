<?php
/**
 * Plugin Name: 	Media Selector plugin
 * Plugin URI:		http://www.evoxyz.com/
 * Description:		Add a media selector to your settings page.
 * Version: 		0.0.1
 * Author: 			Raman kant kamboj
 * Author URI: 		http://www.evoxyz.com/
 */
ob_start();
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'del') {
        $del_id = $_GET['id'];
        global $del_id;
        $table_name = $wpdb->prefix . "imagepath";
        $wpdb->delete($table_name, array('id' => $del_id), array('%d'));
        ?>
        <script>
            window.location = "<?php echo admin_url('admin.php?page=media-selector'); ?>";
        </script>
        <?php
    }
}
add_action('admin_menu', 'register_media_selector_settings_page');

function register_media_selector_settings_page() {
    add_menu_page('Media Selector', 'Media Selector', 'manage_options', 'media-selector', 'media_selector_settings_page_callback');
}

function mediaselector_options_install() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $table_name = $wpdb->prefix . "imagepath";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`image_id` varchar(255),
			`image_url` varchar(255),
			`active_status` ENUM('0', '1')NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
          ) $charset_collate; ";
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'mediaselector_options_install');

function media_selector_settings_page_callback() {
    if (isset($_POST['submit_image_selector']) && isset($_POST['image_attachment_id'])) {
        update_option('media_selector_attachment_id', absint($_POST['image_attachment_id']));
        $imgid = $_POST['image_attachment_id'];
        $imagepath = wp_get_attachment_url(get_option('media_selector_attachment_id'));
        $upload_dir = wp_upload_dir();
        $upload_url = strlen($upload_dir['baseurl'] . $upload_dir['subdir'] . '/');
        $image_name = substr($imagepath, $upload_url);
        global $wpdb;
        $table_name = $wpdb->prefix . "imagepath";
        $getdetials = $wpdb->get_row("SELECT * FROM  $table_name where image_id='$imgid'");
        $get_num_chk = count($getdetials);
        if ($get_num_chk == '0') {
            $userdata = array(
                'image_id' => $imgid,
                'image_url' => $image_name
            );
            $wpdb->insert($table_name, $userdata);
            echo '<span style="color:green; font-weight:bold; font-size:15px;">insert into successfully</span>';
        } else {
            echo '<span style="color:red; font-weight:bold; font-size:15px;">Already Selected</span>';
        }
    }
    wp_enqueue_media();
    ?>
    <form method='post' style="margin-top: 13px;">
        <div class='image-preview-wrapper'>
            <img id='image-preview' src="<?php echo wp_get_attachment_url(get_option('media_selector_attachment_id')); ?>" style="width: 150px;height: 150px;">
        </div>
        <input id="upload_image_button" type="button" class="button" value="<?php _e('Upload image'); ?>" />
        <input type='hidden' name='image_attachment_id' id='image_attachment_id' value="<?php echo get_option('media_selector_attachment_id'); ?>">
        <input type="submit" name="submit_image_selector" value="Save" class="button-primary">
    </form>
    <?php
    add_action('admin_footer', 'media_selector_print_scripts');

    function media_selector_print_scripts() {
        $my_saved_attachment_post_id = get_option('media_selector_attachment_id', 0);
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                var file_frame;
                var wp_media_post_id = wp.media.model.settings.post.id;
                var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>;
                jQuery('#upload_image_button').on('click', function (event) {
                    event.preventDefault();
                    if (file_frame) {
                        file_frame.uploader.uploader.param('post_id', set_to_post_id);
                        file_frame.open();
                        return;
                    } else {
                        wp.media.model.settings.post.id = set_to_post_id;
                    }
                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: 'Select a image to upload',
                        button: {
                            text: 'Use this image',
                        },
                        multiple: true
                    });
                    file_frame.on('select', function () {
                        attachment = file_frame.state().get('selection').first().toJSON();
                        $('#image-preview').attr('src', attachment.url).css('width', 'auto');
                        $('#image_attachment_id').val(attachment.id);
                        wp.media.model.settings.post.id = wp_media_post_id;
                    });
                    file_frame.open();
                });
                jQuery('a.add_media').on('click', function () {
                    wp.media.model.settings.post.id = wp_media_post_id;
                });
            });
        </script>
        <style>
            div.img {
                margin: 5px;
                border: 1px solid #ccc;
                float: left;
                width: 180px;
            }
            div.img:hover {
                border: 1px solid #777;
            }
            div.img img {
                width: 100%;
                height: auto;
            }
            div.desc {
                padding: 15px;
                text-align: center;
            }
        </style>
        <?php
    }

    global $wpdb;
    $table_imagepath = $wpdb->prefix . "imagepath";
    $table_post = $wpdb->prefix . "posts";
    $getalldetials = $wpdb->get_results("SELECT $table_imagepath.*,$table_post.guid FROM  $table_imagepath join $table_post on $table_post.ID = $table_imagepath.image_id");
    foreach ($getalldetials as $getimgdetails) {
        ?>
        <div class="img">
            <img src="<?php echo $getimgdetails->guid; ?>" alt="<?php echo $getimgdetails->image_url; ?>" style="width: 180px;height: 110px;">
            <div class="desc"><?php echo $getimgdetails->image_url; ?></div>
            <div><a href="<?php echo admin_url('admin.php?page=media-selector'); ?>&action=del&id=<?php echo $getimgdetails->id; ?>" onclick="return confirm('Do you want to delete this item')"><img src="<?php echo plugins_url('images/remove.png', __FILE__); ?>" style="width: 26px;height: 26px;"></a></div>
        </div>
        <?php
    }
}
?>