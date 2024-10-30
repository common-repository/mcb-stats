<?php
/**
 * Plugin Name: Mcb - Stats
 * Plugin URI: http://www.creativecode.es/
 * Description: Plugin that counts the statistics of the accesses to all wordpress' front, with the posibility of counting the total amount of time a user has been in the page.
 * Version: 1.0.0
 * Author: Mario Castellano
 * License: GPL2
 */
// Exit if accessed directly.
defined('ABSPATH') || exit;

date_default_timezone_set(get_option('timezone_string'));

function mcb_create_database_table() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'mcb_stats';

    $sql = "CREATE TABLE `" . $table_name . "` (
            `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NULL DEFAULT 0,
            `post_id` INT NULL DEFAULT 0,
            `post_title` TEXT NULL,
            `post_type` VARCHAR(45) NULL,
            `actual_guid` MEDIUMTEXT NULL,
            `acction` VARCHAR(45) NULL,
            `link_action_guid` MEDIUMTEXT NULL,
            `date_now` DATETIME NOT NULL,
            `time_in_page` TIME NULL,
            `ip` VARCHAR(45) NULL,
            `browser` VARCHAR(45) NULL,
            `platform` VARCHAR(45) NULL,
            `user_agent` TEXT NULL,
            PRIMARY KEY (`ID`, `date_now`),
            UNIQUE INDEX `ID_UNIQUE` (`ID` ASC));";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'mcb_create_database_table');

function mcb_getBrowser() {
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'Windows';
    }

    if (preg_match('/android/i', $u_agent)) {
        $bname = 'Android';
        $ub = 'Android';
    } elseif (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif (preg_match('/Edge/i', $u_agent)) {
        $bname = 'Microsoft Edge';
        $ub = "Edge";
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    } elseif (preg_match('/Trident/i', $u_agent)) {
        $bname = 'Internet Explorer 11';
        $ub = "Trident";
    }

    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    $i = count($matches['browser']);
    if ($i != 1) {
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }

    if ($version == null || $version == "") {
        $version = "?";
    }

    return array(
        'userAgent' => $u_agent,
        'name' => $bname,
        'version' => $version,
        'platform' => $platform,
        'pattern' => $pattern
    );
}

function mcb_getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters('wpb_get_ip', $ip);
}

function stats_mcb_head() {
    global $wpdb;
    $user_id = get_current_user_id();
    $ua = mcb_getBrowser();
    $browser = $ua['name'] . " " . $ua['version'];
    $platform = $ua['platform'];
    $userAgent = $ua['userAgent'];
    $ip = mcb_getIP();

    $data_post = NULL;
    $post_id = NULL;
    $post_title = NULL;
    $post_type = NULL;
    $post_guid = false;
    if (get_post()) {
        $data_post = get_post(get_post()->ID);
        $post_id = $data_post->ID;
        $post_title = get_the_title($post_id);
        $post_type = $data_post->post_type;
        $post_guid = get_permalink($post_id);
    }
    if ($post_guid == false) {
        $post_guid = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    if (strpos($post_guid, 'mcb_stats') === false) {
        $wpdb->insert(
                $wpdb->prefix . 'mcb_stats', array(
            'user_id' => (int) $user_id,
            'post_id' => (int) $post_id,
            'post_title' => html_entity_decode($post_title),
            'post_type' => $post_type,
            'actual_guid' => $post_guid,
            'acction' => 'Load page',
            'date_now' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'browser' => $browser,
            'platform' => $platform,
            'user_agent' => $userAgent
                )
        );
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('mcb_stats', plugin_dir_url(__FILE__) . "js/mcb_stats.js", array("jquery"));
    $active = false;
    if (get_option('mcb_stats_plugin_menu') !== false) {
        $save_options = json_decode(get_option('mcb_stats_plugin_menu'));
        $check_save_time_in_page = $save_options->check_save_time_in_page;
        $text_save_time_in_page = $save_options->text_save_time_in_page;
        if ($check_save_time_in_page == "true") {
            $active = true;
        }
    }
    if ($active == true) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){window.location.origin || (window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port?":" + window.location.port:"")); var c = "localhost" == window.location.hostname?window.location.protocol + "//" + window.location.hostname + "/" + window.location.pathname.split("/")[1]:window.location.origin, a, b = 0; (function(){"undefined" !== typeof Worker?("undefined" == typeof a && (a = new Worker("<?php echo plugin_dir_url(__FILE__); ?>js/mcb_stats_count.js")), a.onmessage = function(a){0 == b?jQuery.post(c +
                    "/wp-admin/admin-ajax.php", {action:"stats_ajax_mcb", post_id:<?php echo (int) $post_id; ?>, post_title:"<?php echo esc_html($post_title); ?>", post_type:"<?php echo esc_html($post_type); ?>", actual_guid:"<?php echo esc_url($post_guid); ?>", acction:"Time", link_action_guid:"Time in page", time_in_page:a.data}, function(a){b = a}):jQuery.post(c + "/wp-admin/admin-ajax.php", {action:"stats_ajax_mcb_update_time", ID:b, time_in_page:a.data})}, a.postMessage(<?php echo (int) $text_save_time_in_page; ?>)):console.log("Sorry! No Web Worker support.")})(); window.onbeforeunload = function(){a.terminate(); a = void 0}});</script>
        <?php
    }
}

add_action('wp_head', 'stats_mcb_head');

function stats_mcb_login($user_login, $user) {
    global $wpdb;
    $user_id = $user->ID;
    $ua = mcb_getBrowser();
    $browser = $ua['name'] . " " . $ua['version'];
    $platform = $ua['platform'];
    $userAgent = $ua['userAgent'];
    $ip = mcb_getIP();

    $post_id = 0;
    $post_title = 'login';
    $post_type = 'login';
    $post_guid = 'login';

    $wpdb->insert(
            $wpdb->prefix . 'mcb_stats', array(
        'user_id' => (int) $user_id,
        'post_id' => (int) $post_id,
        'post_title' => html_entity_decode($post_title),
        'post_type' => $post_type,
        'actual_guid' => $post_guid,
        'acction' => 'Login',
        'date_now' => date('Y-m-d H:i:s'),
        'ip' => $ip,
        'browser' => $browser,
        'platform' => $platform,
        'user_agent' => $userAgent
            )
    );
}

add_action('wp_login', 'stats_mcb_login', 10, 2);

function stats_mcb_logout() {
    global $wpdb;
    $user_id = get_current_user_id();
    $ua = mcb_getBrowser();
    $browser = $ua['name'] . " " . $ua['version'];
    $platform = $ua['platform'];
    $userAgent = $ua['userAgent'];
    $ip = mcb_getIP();

    $post_id = 0;
    $post_title = 'logout';
    $post_type = 'logout';
    $post_guid = 'logout';

    $wpdb->insert(
            $wpdb->prefix . 'mcb_stats', array(
        'user_id' => (int) $user_id,
        'post_id' => (int) $post_id,
        'post_title' => html_entity_decode($post_title),
        'post_type' => $post_type,
        'actual_guid' => $post_guid,
        'acction' => 'Logout',
        'date_now' => date('Y-m-d H:i:s'),
        'ip' => $ip,
        'browser' => $browser,
        'platform' => $platform,
        'user_agent' => $userAgent
            )
    );
}

add_action('wp_logout', 'stats_mcb_logout');

function mcb_stats_plugin_menu() {
    add_menu_page('Mcb - Stats', 'Mcb - Stats', 'administrator', 'mcb-stats-plugin-menu', 'mcb_stats_plugin_menu_page', 'dashicons-id-alt');
}

add_action('admin_menu', 'mcb_stats_plugin_menu');

function mcb_stats_donate_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66LXBEKX7DGK2" target="_blank">Donation or coffee</a>';
    }
    return $links;
}

add_filter('plugin_row_meta', 'mcb_stats_donate_link', 10, 2);

function mcb_stats_plugin_menu_page() {
    global $wpdb;
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core', array("jquery"));
    wp_enqueue_script('jquery-ui-datepicker', array("jquery"));
    wp_register_style('jquery-ui', plugin_dir_url(__FILE__) . "/css/jquery-ui.css");
    wp_enqueue_style('jquery-ui');
    wp_enqueue_script('mcb_stats_menu', plugin_dir_url(__FILE__) . "/js/mcb_stats_menu.js", array("jquery"));
    wp_enqueue_script('datatables', plugin_dir_url(__FILE__) . "/DataTables/datatables.min.js", array("jquery"));
    wp_enqueue_style('datatables', plugin_dir_url(__FILE__) . "/DataTables/datatables.min.css");
    wp_enqueue_script('chart', plugin_dir_url(__FILE__) . "/Chart/Chart.min.js", array("jquery"));

//top_more_time
    $top_more_time = $wpdb->get_results("SELECT DISTINCT post_id, SUM(time_in_page) AS numSUM, SEC_TO_TIME(SUM(TIME_TO_SEC(time_in_page))) AS timeSum, post_title FROM " . $wpdb->prefix . "mcb_stats WHERE acction = 'Time' GROUP BY post_id ORDER BY numSum DESC LIMIT 10;");
    $array_top_more_time = array();
    $total_top_more_time = 0;
    foreach ($top_more_time as $top) {
        $array_top_more_time[$top->post_title] = $top->numSUM;
        $total_top_more_time = $total_top_more_time + $top->numSUM;
    }
    foreach ($array_top_more_time as $key => $array) {
        $array_top_more_time[$key] = round(($array * 100) / $total_top_more_time, 2);
    }

    //top_users_login
    $top_users_login = $wpdb->get_results("SELECT st.user_id, COUNT(st.user_id) AS count, u.display_name
                                           FROM " . $wpdb->prefix . "mcb_stats AS st
                                           LEFT JOIN " . $wpdb->prefix . "users AS u ON u.ID = st.user_id
                                           WHERE st.acction = 'Login'
                                           GROUP BY st.user_id
                                           ORDER BY count DESC
                                           LIMIT 10");
    $array_top_users_logins = array();
    foreach ($top_users_login as $top) {
        if ($top->display_name == NULL) {
            $array_top_users_logins["Unnamed"] = $top->count;
        } else {
            $array_top_users_logins[$top->display_name] = $top->count;
        }
    }

    //top_more_views
    $top_more_views = $wpdb->get_results("SELECT post_title, post_id, COUNT(post_id) count FROM " . $wpdb->prefix . "mcb_stats  WHERE acction = 'Load page' GROUP BY post_id  ORDER BY count DESC LIMIT 10;");
    $array_top_more_views = array();
    foreach ($top_more_views as $top) {
        if ($top->post_title == NULL) {
            $array_top_more_views["Unnamed"] = $top->count;
        } else {
            $array_top_more_views[$top->post_title] = $top->count;
        }
    }

    //top_browsers
    $top_browsers = $wpdb->get_results("SELECT browser, COUNT(browser) count FROM " . $wpdb->prefix . "mcb_stats GROUP BY browser ORDER BY count DESC LIMIT 10;");
    $array_top_browsers = array();
    $total_top_browsers = 0;
    foreach ($top_browsers as $top) {
        $array_top_browsers[$top->browser] = $top->count;
        $total_top_browsers = $total_top_browsers + $top->count;
    }
    foreach ($array_top_browsers as $key => $array) {
        $array_top_browsers[$key] = round(($array * 100) / $total_top_browsers, 2);
    }

    if (get_option('mcb_stats_plugin_menu') !== false) {
        $save_options = json_decode(get_option('mcb_stats_plugin_menu'));
        $check_save_time_in_page = $save_options->check_save_time_in_page;
        $text_save_time_in_page = $save_options->text_save_time_in_page;
    } else {
        $check_save_time_in_page = false;
        $text_save_time_in_page = 0;
    }
    ?>
    <div class="wrap">
        <h1>Settings</h1>
        <div class="card" style="width: 100%; box-sizing: border-box; max-width: none;">
            <p>Time of refresh in seconds and insertion in the data base, this number must be adjusted depending the server's power and the recurrence of active users in the web. ATENTION: if the time of insertion in the data base is not correctly adjusted it may block de server</p>
            <h2>Activate the time in page registry</h2>                       
            <input id="check_save_time_in_page" type="checkbox" name="check_save_time_in_page" value="1" <?php echo ($check_save_time_in_page == "true") ? "checked" : ""; ?>>
            <h2>Seconds refresh in data base</h2>
            <input type="number" name="text_save_time_in_page" value="<?php echo ((int) $text_save_time_in_page != 0) ? (int) $text_save_time_in_page : 60; ?>" id="text_save_time_in_page" placeholder="time refresh in seconds"><br><br>
            <input type="button" id="save_settings" class="button button-primary" value="Save">
        </div>
        <h1>Data and statistics of users activity</h1>
        <div class="card" style="width: 100%; box-sizing: border-box; max-width: none;">
            <button class="button button-primary" id="reset-all" style="float: right;">Reset All</button>
            <h2>Generate activity table</h2>
            <div style="display:inline-block;">All data <input type="radio" name="data" value="1" checked="checked" /></div>
            <div style="display:inline-block;">Data from to <input type="radio" name="data" value="2"></div>
            <br>
            <br>
            <div id="div-from-to" style="display:none;">
                <label for="from">From</label>
                <input type="text" id="from" name="from" readonly="readonly">
                <label for="to">to</label>
                <input type="text" id="to" name="to" readonly="readonly">
                <br>
                <br>
            </div>
            <button class="button" id="generate-grid">Generate</button>
            <br>
            <br>
            <h2>Show / Hide columns</h2>
            <a class="toggle-vis button" data-column="0">ID</a>
            <a class="toggle-vis button" data-column="1">ID user</a>
            <a class="toggle-vis button" data-column="2">Name</a>
            <a class="toggle-vis button" data-column="3">Email</a>
            <a class="toggle-vis button" data-column="4">ID post</a>
            <a class="toggle-vis button" data-column="5">Title post</a>
            <a class="toggle-vis button" data-column="6">Type post</a>
            <a class="toggle-vis button" data-column="7">Actual url</a>
            <a class="toggle-vis button" data-column="8">Action</a>
            <a class="toggle-vis button" data-column="9">Link action url</a>
            <a class="toggle-vis button" data-column="10">Date</a>
            <a class="toggle-vis button" data-column="11">Time in page</a>
            <a class="toggle-vis button" data-column="12">IP</a>
            <a class="toggle-vis button" data-column="13">Browser</a>
            <a class="toggle-vis button" data-column="14">Platform</a>
            <a class="toggle-vis button" data-column="15">Usert Agent</a>
            <h2>Action buttons</h2>
            <table id="grid" class="display" style="width:100%;"></table>
        </div>

        <h1>Graphics and chards</h1>
        <div class="card" style="width: 100%; box-sizing: border-box; max-width: none;">
            <!--<div style="padding: 10px;">-->
            <div style="width: 24.5%; display: inline-table;">
                <label style="text-align: center;"><h2>Top 10 most accumulated time posts</h2></label>
                <?php
                if (empty($array_top_more_time)) {
                    echo '<h3 style="text-align: center;">No data</h3>';
                }
                ?>
                <canvas id="top_more_time"></canvas>
            </div>
            <div style="width: 24.5%; display: inline-table;">
                <label style="text-align: center;"><h2>Top 10 most logged in users</h2></label>
                <?php
                if (empty($array_top_users_logins)) {
                    echo '<h3 style="text-align: center;">No data</h3>';
                }
                ?>
                <canvas id="top_users_login"></canvas>
            </div>
            <div style="width: 24.5%; display: inline-table;">
                <label style="text-align: center;"><h2>Top 10 most viewed posts</h2></label>
                <?php
                if (empty($array_top_more_views)) {
                    echo '<h3 style="text-align: center;">No data</h3>';
                }
                ?>
                <canvas id="top_more_views"></canvas>
            </div>
            <div style="width: 24.5%; display: inline-table;">
                <label style="text-align: center;"><h2>Top 10 browsers</h2></label>
                <?php
                if (empty($array_top_browsers)) {
                    echo '<h3 style="text-align: center;">No data</h3>';
                }
                ?>
                <canvas id="top_browsers"></canvas>
            </div>
        </div>
        <script>
            jQuery(document).ready(function(a){a = {labels:<?php echo json_encode(array_keys($array_top_more_time)); ?>, datasets:[{data:<?php echo json_encode(array_values($array_top_more_time)); ?>, backgroundColor:"#E74C3C #9B59B6 #3498DB #1ABC9C #16A085 #27AE60 #F1C40F #F39C12 #E67E22 #D35400".split(" ")}]}; new Chart(jQuery("#top_more_time"), {type:"doughnut", options:{responsive:!0, legend:{position:"bottom"}, title:{display:!1}, animation:{animateScale:!0, animateRotate:!0}, tooltips:{callbacks:{label:function(a, b){return b.datasets[a.datasetIndex].data[a.index] + "%"}}}}, data:a}); a = {labels:<?php echo json_encode(array_keys($array_top_users_logins)); ?>, datasets:[{data:<?php echo json_encode(array_values($array_top_users_logins)); ?>, backgroundColor:"#E74C3C #9B59B6 #3498DB #1ABC9C #16A085 #27AE60 #F1C40F #F39C12 #E67E22 #D35400".split(" ")}]};
            new Chart(jQuery("#top_users_login"), {type:"doughnut", options:{responsive:!0, legend:{position:"bottom"}, title:{display:!1}, animation:{animateScale:!0, animateRotate:!0}}, data:a}); a = {labels:<?php echo json_encode(array_keys($array_top_more_views)); ?>, datasets:[{data:<?php echo json_encode(array_values($array_top_more_views)); ?>, backgroundColor:"#E74C3C #9B59B6 #3498DB #1ABC9C #16A085 #27AE60 #F1C40F #F39C12 #E67E22 #D35400".split(" ")}]}; new Chart(jQuery("#top_more_views"), {type:"doughnut", options:{responsive:!0, legend:{position:"bottom"}, title:{display:!1}, animation:{animateScale:!0, animateRotate:!0}}, data:a});
            a = {labels:<?php echo json_encode(array_keys($array_top_browsers)); ?>, datasets:[{data:<?php echo json_encode(array_values($array_top_browsers)); ?>, backgroundColor:"#E74C3C #9B59B6 #3498DB #1ABC9C #16A085 #27AE60 #F1C40F #F39C12 #E67E22 #D35400".split(" ")}]}; new Chart(jQuery("#top_browsers"), {type:"doughnut", options:{responsive:!0, legend:{position:"bottom"}, title:{display:!1}, animation:{animateScale:!0, animateRotate:!0}, tooltips:{callbacks:{label:function(a, b){return b.datasets[a.datasetIndex].data[a.index] + "%"}}}}, data:a})});
        </script>
    </div>

    <?php
}

function stats_ajax_mcb() {
    global $wpdb;
    if (get_current_user_id()) {
        $user_id = get_current_user_id();
    }

    $ua = mcb_getBrowser();
    $browser = $ua['name'] . " " . $ua['version'];
    $platform = $ua['platform'];
    $userAgent = $ua['userAgent'];
    $ip = mcb_getIP();

    $data_post = NULL;
    $post_id = NULL;
    $post_title = NULL;
    $post_type = NULL;
    $post_guid = false;
    $acction = NULL;
    $link_action_guid = NULL;
    $time_in_page = NULL;

    if (get_post()) {
        $data_post = get_post(get_post()->ID);
        $post_id = $data_post->ID;
        $post_title = get_the_title($post_id);
        $post_type = $data_post->post_type;
        $post_guid = get_permalink($post_id);
    }
    if ($post_guid == false) {
        $post_guid = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    if (sanitize_text_field($_POST["actual_guid"])) {
        $actual_guid = sanitize_text_field($_POST["actual_guid"]);
        if ($actual_guid) {
            $post_guid = $actual_guid;
        }
    }

    if (sanitize_text_field($_POST["post_id"])) {
        $p_post_id = sanitize_text_field($_POST["post_id"]);
        if ($p_post_id) {
            $post_id = $p_post_id;
        }
    }

    if (sanitize_text_field($_POST["post_title"])) {
        $p_post_title = sanitize_text_field($_POST["post_title"]);
        if ($p_post_title) {
            $post_title = $p_post_title;
        }
    }

    if (sanitize_text_field($_POST["post_type"])) {
        $p_post_type = sanitize_text_field($_POST["post_type"]);
        if ($p_post_type) {
            $post_type = $p_post_type;
        }
    }

    if (sanitize_text_field($_POST["acction"])) {
        $acction = sanitize_text_field($_POST["acction"]);
    }
    if (sanitize_text_field($_POST["link_action_guid"])) {
        $link_action_guid = sanitize_text_field($_POST["link_action_guid"]);
    }
    if (sanitize_text_field($_POST["time_in_page"])) {
        $time_in_page = sanitize_text_field($_POST["time_in_page"]);
    }

    $wpdb->insert(
            $wpdb->prefix . 'mcb_stats', array(
        'user_id' => (int) $user_id,
        'post_id' => (int) $post_id,
        'post_title' => html_entity_decode($post_title),
        'post_type' => $post_type,
        'actual_guid' => $post_guid,
        'acction' => $acction,
        'link_action_guid' => $link_action_guid,
        'date_now' => date('Y-m-d H:i:s'),
        'time_in_page' => $time_in_page,
        'ip' => $ip,
        'browser' => $browser,
        'platform' => $platform,
        'user_agent' => $userAgent
            )
    );

    echo $wpdb->insert_id;
    die();
}

add_action('wp_ajax_stats_ajax_mcb', 'stats_ajax_mcb');
add_action('wp_ajax_nopriv_stats_ajax_mcb', 'stats_ajax_mcb');

function stats_ajax_mcb_update_time() {
    global $wpdb;
    $user_id = get_current_user_id();
    $ID = sanitize_text_field($_POST["ID"]);
    $time_in_page = sanitize_text_field($_POST["time_in_page"]);
    $wpdb->update(
            $wpdb->prefix . 'mcb_stats', array(
        'time_in_page' => $time_in_page
            ), array('ID' => $ID, 'user_id' => $user_id)
    );
    die();
}

add_action('wp_ajax_stats_ajax_mcb_update_time', 'stats_ajax_mcb_update_time');
add_action('wp_ajax_nopriv_stats_ajax_mcb_update_time', 'stats_ajax_mcb_update_time');

function reset_data_mcb_stats() {
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . "mcb_stats;");
    echo 1;
    die();
}

add_action('wp_ajax_reset_data_mcb_stats', 'reset_data_mcb_stats');

function save_mcb_stats_sttings() {

    $option_name = 'mcb_stats_plugin_menu';
    $data = json_encode($_POST["data"]);
    if (get_option($option_name) !== false) {
        $return = update_option($option_name, $data);
        echo $return;
    } else {
        $deprecated = null;
        $autoload = 'yes';
        $return = add_option($option_name, $data, $deprecated, $autoload);
        echo $return;
    }
    die();
}

add_action('wp_ajax_save_mcb_stats_sttings', 'save_mcb_stats_sttings');

function get_data_mcb_stats() {
    global $wpdb;
    $from = sanitize_text_field($_POST["from"]);
    $to = sanitize_text_field($_POST["to"]);
    if ($from != "" && $to != "") {
        $from = str_replace('/', '-', $from);
        $from = date('Y-m-d', strtotime($from));
        $to = str_replace('/', '-', $to);
        $to = date('Y-m-d', strtotime($to . ' +1 day'));
        $wp_mcb_stats = $wpdb->get_results("SELECT u.user_email as email, u.display_name AS `name`, t.*
                                            FROM " . $wpdb->prefix . "mcb_stats AS t
                                            LEFT JOIN " . $wpdb->prefix . "users AS u ON u.ID = t.user_id
                                            WHERE t.date_now >= '" . $from . "'
                                            AND t.date_now <= '" . $to . "'
                                            ORDER BY u.ID DESC;");
    } else {
        $wp_mcb_stats = $wpdb->get_results("SELECT u.user_email as email, u.display_name AS `name`, t.*
                                            FROM " . $wpdb->prefix . "mcb_stats AS t
                                            LEFT JOIN " . $wpdb->prefix . "users AS u ON u.ID = t.user_id
                                            ORDER BY u.ID DESC;");
    }

    $new_data = array();
    foreach ($wp_mcb_stats as $data) {
        $new_data[] = array(
            $data->ID,
            $data->user_id,
            $data->name,
            $data->email,
            $data->post_id,
            $data->post_title,
            $data->post_type,
            $data->actual_guid,
            $data->acction,
            $data->link_action_guid,
            $data->date_now,
            $data->time_in_page,
            $data->ip,
            $data->browser,
            $data->platform,
            $data->user_agent
        );
    }
    echo json_encode($new_data);
    die();
}

add_action('wp_ajax_get_data_mcb_stats', 'get_data_mcb_stats');
