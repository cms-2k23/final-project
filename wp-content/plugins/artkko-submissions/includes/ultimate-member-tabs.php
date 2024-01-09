<?php
function um_mycustomtab_add_tab($tabs)
{

    /* MY COURSES */

    $tabs['ld_course_list'] = array(
        'name' => 'Submitted Commissions',
        'icon' => 'um-faicon-book',
        'custom' => true
    );


    if (!isset(UM()->options()->options['profile_tab_' . 'ld_course_list'])) {
        UM()->options()->update('profile_tab_' . 'ld_course_list', true);
    }

    /* MY CERTIFICATES */

    $tabs['uo_learndash_certificates'] = array(
        'name' => 'Order Commission',
        'icon' => 'um-faicon-certificate',
        'custom' => true
    );

    if (!isset(UM()->options()->options['profile_tab_' . 'uo_learndash_certificates'])) {
        UM()->options()->update('profile_tab_' . 'uo_learndash_certificates', true);
    }

    return $tabs;
}

add_filter('um_profile_tabs', 'um_mycustomtab_add_tab', 1000);

function prevent_cpt_delete($delete, $post, $force_delete)
{
    if ('submissions' === $post->post_type && !$force_delete) {
        return $delete;
    }
    return;
}

add_filter('pre_delete_post', 'prevent_cpt_delete', 10, 3);

/**
 * Render the tab 'MY COURSES'
 * @param array $args
 */
function um_profile_content_ld_course_list($args)
{

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $admin_email = get_bloginfo('admin_email');
    $admin_name = get_bloginfo('name');

    $headers[] = "From: {$admin_name} <{$admin_email}>";
    $headers[] = "Content-Type: text/html";

    $artist_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . "artkko_submissions";
    $retrieve_data = $wpdb->get_results("SELECT * FROM $table_name where artist_id = '$artist_id' and done = 0");

?>
    <style>
        input {
            padding: 10px 20px;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .reject-btn {
            background-color: #FF495C;
            color: #000;
            font-weight: 600;
        }

        .reject-btn:hover {
            background-color: #DB404F;
        }

        .submit-btn {
            background-color: var(--wp--preset--color--primary);
            color: #000;
            font-weight: 600;
        }

        .submit-btn:hover {
            background-color: #89DB1D;
        }

        table.fixed {
            table-layout: fixed;
        }

        input[type="file"]::file-selector-button {
            padding: 10px 20px;
            background-color: transparent;
            color: var(--wp--preset--color--primary);
            border: 1px solid var(--wp--preset--color--primary);
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .blog-card {
            display: flex;
            flex-direction: row;
            background: #1a1a1a !important;
            border: 1px solid #424242 !important;
            border-radius: 5px !important;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .post-description {
            color: #fff;
        }

        .post-image {
            transition: opacity 0.3s ease;
            width: 40%;
            height: 280px;
            object-fit: cover;
        }

        .article-details {
            padding: 24px;
        }

        .post-category {
            display: inline-block;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 3px;
            margin: 0 0 12px 0;
            padding: 0 0 4px 0;
            border-bottom: 2px solid #ebebeb;
        }

        @media (max-width: 700px) {
            #container {
                width: 330px;
            }

            .post-image {
                width: 100%;
            }

            .blog-card {
                flex-wrap: wrap;
            }
        }

        table.fixed {
            table-layout: fixed;
        }

        table.fixed td {
            overflow: hidden;
        }

        table.fixed th:nth-of-type(1) {
            width: 119px;
        }

        table.fixed th:nth-of-type(2) {
            width: 250px;
        }

        table.fixed th:nth-of-type(3) {
            width: 110px;
        }
    </style>
    <?php foreach ($retrieve_data as $row) : ?>
        <article class="blog-card">
            <div class="article-details">
                <h4 class="post-category">From: <?= $row->customer_name ?></h4><br>
                <h4 class="post-category">Due: <?= $row->due ?></h4>
                <p class="post-description">
                    “<?= $row->commission_content ?>”
                </p>
                <form method="post" enctype='multipart/form-data'>
                    <?php
                    $delRow = "delete_submission_{$row->id}";
                    $subRow = "submit_submission_{$row->id}";
                    $image_upload_by_id = "file_upload_{$row->id}";
                    ?>
                    <table class="fixed">
                        <tr>
                            <td><input class='reject-btn' type='submit' value="Reject" name=<?= $delRow ?>></td>
                            <td><input id=image_upload_button style="max-width: 300px;" type='file' accept="image/*" name='<?= $image_upload_by_id ?>' id='<?= $image_upload_by_id ?>'></td>
                            <td><input class='submit-btn' type='submit' value="Upload" name=<?= $subRow ?>></td>
                        </tr>
                    </table>
                </form>


                <?php
                if (isset($_POST[$delRow])) {
                    $headers[] = "Reply-to: {$row->customer_name} <{$row->customer_email}>";
                    $subject = "Commission rejected";
                    $message = "<p>Your request was rejected by artist. We are so sorry!</p>";
                    wp_mail($row->customer_email, $subject, $message, $headers,);

                    $wpdb->delete($table_name, array('id' => $row->id));
                    wp_delete_post($row->submission_id);
                    echo "<meta http-equiv='refresh' content='0'>";
                }

                if (isset($_POST[$subRow])) {

                    $attachment_id = media_handle_upload($image_upload_by_id, $_POST['post_id']);
                    $attachments = get_attached_file($attachment_id);

                    $headers[] = "Reply-to: {$row->customer_name} <{$row->customer_email}>";
                    $subject = "Commission completed";
                    $message = "<p>Your request was completed!</p>";
                    wp_mail($row->customer_email, $subject, $message, $headers, array($attachments));
                    wp_delete_attachment($attachment_id, true);

                    $wpdb->update($table_name, array('done' => 1), array('ID' => $row->id));
                    echo "<meta http-equiv='refresh' content='0'>";
                }
                ?>
            </div>
        </article>

    <?php endforeach; ?>

<?php
}

add_action('um_profile_content_ld_course_list', 'um_profile_content_ld_course_list');


/**
 * Render the tab 'MY CERTIFICATES'
 * @param array $args
 */
function um_profile_content_uo_learndash_certificates($args)
{
?>
    <div>
        <?php
        if (!um_is_myprofile()) {
            echo do_shortcode('[artkko_submission]');
        } else {
        ?>
            <div>Nothing to see here :)</div>
        <?php
        }
        ?>
    </div>

<?php
}

add_action('um_profile_content_uo_learndash_certificates', 'um_profile_content_uo_learndash_certificates');
