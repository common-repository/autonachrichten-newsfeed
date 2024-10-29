<?php
/*
  Plugin Name: Autonachrichten Newsfeed
  Plugin URI: http://www.autonachrichten.de/plugins/wordpress
  Description: Dieses Plugin zeigt aktuelle Nachrichten rund um das Auto. Die gezeigten Themen, können beliebig konfiguriert werden.
  Author: Matthias Tosch
  Version: 0.2.2
  Author URI: http://www.mtosch.de/
  Donate link: http://www.mtosch.de/
  Tags: News, Autos, Cars, Nachrichten, vw, volkswagen, opel, audi, mercedes, ford, sidebar, rss, xml, news flash, scroller
 */


//####################################################################################################
//###### Project   : Autonachrichten Newsfeed                                                   ######
//###### File Name : autonachrichten_newsfeed.php                                               ######
//###### Purpose   : This is the main page for this plugin.                                     ######
//###### Created   : Dez 7th 2011                                                               ######
//###### Modified  : Sep 27th 2017                                                              ######
//###### Author    : Matthias Tosch (http://www.mtosch.de)                                      ######
//###### Link      : http://www.autonachrichten.de/plugins/auto-nachrichten-fur-wordpress-blogs ######
//####################################################################################################


global $wpdb, $wp_version;

class AnNewsFeed {

    protected $cache_dir = "";
    protected $cache_file = "rss_cache.txt";

    public function __construct() {
        $this->cache_dir = dirname(__FILE__);
    }

    public function getCachefile() {
        return $this->cache_dir . "/" . $this->cache_file;
    }

    public function init() {
        if (function_exists('register_sidebar_widget')) {
            wp_register_sidebar_widget('an_1', 'Autonachrichten Feed', array($this, 'anWidget'), array('description' => 'Immer die akutuellen Auto Nachrichten in deiner Sidebar'));
        }

        if (function_exists('register_widget_control')) {
            register_widget_control(array('Autonachrichten Feed', 'widgets'), array($this, 'anControl'));
        }
    }

    public function anWidget($args) {
        extract($args);


        if (isset($before_widget) && isset($before_title))
            echo $before_widget . $before_title;


        echo get_option('an_title');

        if (isset($after_title))
            echo $after_title;

        echo $this->showWidget();

        if (isset($afer_widget))
            echo $after_widget;
    }

    public function anControl() {
        // Widget Options
        ?>
        <p>
            Das Autonachrichten-Widget zeigt aktuelle Nachrichten aus dem Automobilbereich. 
            Falls nur News zu ausgewählten Automarken oder Themen angzeigt werden sollen, kann das in den
            <a href="options-general.php?page=autonachrichten/autonachrichten_newsfeed.php">Einstellungen</a> angepasst werden.
        </p>
        <?php
    }

    function an_add_javascript_files() {
        if (!is_admin()) {
            wp_enqueue_script('javascript', get_option('siteurl') . '/wp-content/plugins/autonachrichten/autonachrichten_newsfeed.js');
        }
    }

    private function checkCache() {
        $cacheTime = 900;
        $file = $this->getCachefile();

        if (file_exists($file)) {
            $filetime = filemtime($file);

            if (time() - $filetime > $cacheTime) {
                return false;
            }
            // load from Cache
            $handle = fopen($file, "r");
            $contents = "";

            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);

            if (strlen($contents) == 0 || is_null($content))
                return false;

            return $contents;
        } else {
            return false;
        }
    }

    private function writeCache($content) {
        $file = $this->getCachefile();
        if (!$handle = fopen($file, "w")) {
            print "Kann die Cache Datei nicht öffnen";
            exit;
        }
        if (!fwrite($handle, $content)) {
            print "Kann in die Cache Datei nicht schreiben";
            exit;
        }
        fclose($handle);
    }

    public function getAnData() {

        $url = "https://www.autonachrichten.de/newsInterface";
        $an_categories = get_option('an_categories');
        if ($an_categories) {
            $url .= "?cat=" . $an_categories;
        }
        $result = file_get_contents($url);

        $result = str_replace(array("\n", "\t"), array(" ", ""), $result);
        preg_match_all("#<item>(.*?)</item\>#sim", $result, $itemblocks);


        return $itemblocks;
    }

    public function showWidget() {
        if (($output = $this->checkCache()) !== false) {
            return($output);
        }

        $an_display_width = get_option('an_display_width');
        $an_display_count = get_option('an_display_count');
        $an_record_height = get_option('an_record_height');
        $an_show_preview = get_option('an_show_preview');
        $an_show_image = get_option('an_show_image');

        if (!is_numeric($an_display_width))
            $an_display_width = 200;

        if (!is_numeric($an_record_height))
            $an_record_height = 50;

        if (!is_numeric($an_display_count))
            $an_display_count = 5;

        $cnt = 0;
        $an_html = $an_html_all = $an_x = "";

        // Grab data from AN Newsfeed
        $itemblocks = $this->getAnData();



        if (!empty($itemblocks)) {
            $an_count = 0;
            foreach ($itemblocks[1] as $block) {
                preg_match_all("/\<title\>(.*?)\<\/title\>/", $block, $title);
                preg_match_all("/\<link\>(.*?)\<\/link\>/", $block, $link);
                if ($an_show_image == "1")
                    preg_match("#<enclosure>(.*?)</enclosure>#sim", $block, $img);



                $an_post_title = $title[1][0];
                $an_post_title = trim($an_post_title);
                $get_permalink = $link[1][0];
                $get_permalink =trim($get_permalink);

                $an_post_title = substr($an_post_title, 0, $an_display_width);

                $dis_height = $an_record_height . "px";
                $an_html = "<div class=\"an_div\" style=\"margin:4px 0px;\">";
                if ($an_show_image == "1")
                    $an_html .= "<img src=\"" . $img[1] . "\" style=\"float:left; margin-right:5px\" />";
                //echo "<a href=\"galery.php?galeryid=". $row['id'] ."\">". $row['gallerienamen'] ."</a>";

                $an_html .= "<a target=\"_blank\" href=\"" . $get_permalink . "\">" . $an_post_title . "</a>";

                if ($an_show_image == "1")
                    $an_html .= "<div style=\"clear:both;\"></div>";

                $an_html .= "</div>";

                $an_html_all .= $an_html;

                $an_post_title = trim($an_post_title);
                $get_permalink = $get_permalink;

                $an_x = $an_x . "an_array[$an_count] = '" . $an_html . "'; ";
                $an_count++;
            }
            $an_record_height = $an_record_height + 4;
            if ($an_count >= $an_display_count) {
                $an_count = $an_display_count;
                $an_height = ($an_record_height * $an_display_count);
            } else {
                $an_count = $an_count;
                $an_height = ($an_count * $an_record_height);
            }
            $an_height1 = $an_record_height . "px";

            ob_start();
            ?>
            <div style="padding-top:8px;padding-bottom:8px;">
                <div style="text-align:left;vertical-align:middle;text-decoration: none;overflow: hidden; position: relative; margin:0 0 3px 1px; height: <?php echo $an_height1; ?>;" id="an_Holder">
                    <?php echo $an_html_all; ?>
                </div>
                <span style="color:#ccc; font-size: 10px">&copy; autonachrichten.de</span>
            </div>
            <script type="text/javascript">
                var an_array = new Array();
                var an_obj = '';
                var an_scrollPos = '';
                var an_numScrolls = '';
                var an_heightOfElm = '<?php echo $an_record_height; ?>'; // Height of each element (px)
                var an_numberOfElm = '<?php echo $an_count; ?>';
                var an_scrollOn = 'true';
                function an_createscroll()
                {
            <?php echo $an_x; ?>
                    an_obj = document.getElementById('an_Holder');
                    an_obj.style.height = (an_numberOfElm * an_heightOfElm) + 'px'; // Set height of DIV
                    an_content();
                }
            </script>
            <script type="text/javascript">
                an_createscroll();
            </script>
            <?php
            $output = ob_get_contents();
            ob_end_clean();
        } else {
            $output = "<div style='padding-bottom:5px;padding-top:5px;'>No data available!</div>";
        }
        $this->writeCache($output);

        return $output;
    }

    public function an_install() {

        add_option('an_title', "Auto Nachrichten");
        add_option('an_display_width', "200");
        add_option('an_display_count', "5");
        add_option('an_record_height', "40");
        add_option('an_show_image', "1");

        // build cache dir
        if (!fopen($this->cache_dir . "/../" . $this->cache_file, "w")) {
            echo "<b>F&uuml;r das Caching ben&ouml;tigt Das Plugin schreibrechte. Bitte setze f&uuml;r den Plugin-Ordner und Unterordner die Rechte \"0777\"</b>";
            exit;
        }
    }

}

$anWidget = new AnNewsFeed();

function getImportFile() {

    $filename = "import.txt";
    $path = plugin_dir_path(__FILE__);

    $uri = $path . $filename;

    if (!is_file($uri)) {
        // falls Datei noch nicht vorhanden
        if (!is_writable($path)) {
            // Datei nicht schreibbar
            echo "nicht schreibbar";
        } else {
            // Datei schreiben
            #mkdir($path, 0777);
            file_put_contents($uri, "");
        }

        return false;
    } else {

        return file_get_contents($uri);
    }
}

function an_admin_options() {
    global $wpdb, $anWidget;


    $xml = $anWidget->getAnData();


    $anImportID = (int) $_GET['anImportID'];


    $import_datei = getImportFile();
    $import_ids = explode(",", $import_datei);


    if (isset($anImportID) && !empty($anImportID)) {

        // daten holen
        // Beispielabfrage: http://autonachrichten.local/wp-admin/options-general.php?page=autonachrichten/autonachrichten_newsfeed.php&anImportID=84901
        // $xml
        foreach ($xml[1] as $item) {

            //var_dump($item);
            //$xml_input = simplexml_load_string ($item);
            preg_match("/\<an_id\>(.*?)\<\/an_id\>/", $item, $an_id);
            //var_dump($an_id);

            if ($anImportID == $an_id[1]) {

                // post_title = title
                // echo ("<br>Titel<br/>");
                preg_match("/\<title\>(.*?)\<\/title\>/", $item, $title);
                //var_dump($title);
                // $content = description
                // echo ("<br>Beschreibung<br/>");
                preg_match("#<description>(.*?)</description>#", $item, $content);

                $content = str_replace("<![CDATA[", "", $content[1]);
                $content = str_replace("]]>", "", $content);
                $content = str_replace("<p>", "", $content);
                $content = str_replace("</p>", "\n\n", $content);
                //var_dump($content);
                $autor = get_current_user_id(); //get_the_author_meta('ID');//  get_author_user_ids();   
                //echo ("<br>Autor<br/>");
                //var_dump($autor);

                $date = date("Y-m-d H:i:s");

                // enclosure
                //echo ("<br>BildUrl<br/>");
                preg_match("/\<enclosure\>(.*?)\<\/enclosure\>/", $item, $anImageUrl);

                // größeres Bild runterladen
                $imgURL = str_replace("75x50", "290x210", $anImageUrl[1]);
                $smallURL = str_replace("75x50", "150x150", $anImageUrl[1]);

                $image = file_get_contents($imgURL);
                $loadDirection = wp_upload_dir();

                //echo ("<br>Bildname<br/>");

                $info = pathinfo('.jpg');

                //Name des Bildes, entstanden aus den Zeilen vor .jpg
                $image_name = basename($imgURL, '.' . $info['extension']);
                file_put_contents($loadDirection['path'] . "/$image_name.jpg", $image);

                // put thumbnail Image from AN cache in upload dir
                $thumbName = $image_name . "-150x150.jpg";
                $tumbImageData = file_get_contents($smallURL);
                file_put_contents($loadDirection['path'] . "/{$thumbName}", $tumbImageData);


                $blogUrl = $loadDirection['url'] . "/" . $image_name . ".jpg";

                // Datei wird erstellt wenn noch nicht vorhanden und es kann gelesen und geschrieben werden
                // DB Post
                $sql = "INSERT INTO wp_posts
                                (post_author,post_date, post_date_gmt,post_content, post_title, post_status, post_type)
                                 VALUES
                                ('$autor','{$date}','{$date}','{$content}', '{$title[1]}', 'draft','post')";
                $wpdb->query($sql);
                $lastPostId = $wpdb->insert_id;

                // DB Bild
                $sql = "INSERT INTO wp_posts
                            (post_content, post_title, post_excerpt,post_status,post_name,post_parent,guid,post_type,post_mime_type)
                            VALUES
                            ('','{$title[1]}-Bild', '','inherit','{$title[1]}-Bild',{$lastPostId}, '{$blogUrl}' , 'attachment', 'image/jpeg')";
                $wpdb->query($sql);
                $lastImageId = $wpdb->insert_id;

                $imagePath = str_replace(site_url() . "/wp-content/uploads/", "", $blogUrl);
                $thumbPath = str_replace(".jpg", "150x150.jpg", str_replace(site_url() . "/wp-content/uploads/", "", $blogUrl));

                $imageData = array(
                    "width" => 290,
                    "height" => 210,
                    "file" => $imagePath,
                    "sizes" => array(
                        "thumbnail" => array(
                            "file" => "{$thumbPath}",
                            "width" => 150,
                            "height" => 150,
                            "mime-type" => "image/jpeg"
                        )
                    ),
                    "image_meta" => array(
                        "aperture" => 0,
                        "credit" => "",
                        "camera" => "",
                        "caption" => "",
                        "created_timestamp" => 0,
                        "copyright" => "",
                        "focal_length" => 0,
                        "iso" => 0,
                        "shutter_speed" => 0,
                        "title" => ""
                    )
                );


                $sql = "INSERT INTO wp_postmeta
                    (post_id,meta_key, meta_value)
                    VALUES
                    ({$lastImageId}, '_wp_attachment_metadata', '" . serialize($imageData) . "'),
                    ({$lastImageId}, '_wp_attached_file' , '{$imagePath}')";
                $wpdb->query($sql);


                $sql = "INSERT INTO wp_postmeta
                    (post_id,meta_key, meta_value)
                    VALUES
                    ({$lastPostId},'_thumbnail_id', '{$lastImageId}')";
                $wpdb->query($sql);

                // Für jeden Datenbankeintrag wird die ID des Artikels in einer Textdatei gespeichert, die IDs sind per , separiert

                $import_ids[] = $anImportID;
                //var_dump($import_ids);

                $new_input = implode($import_ids, ",");

                file_put_contents(plugin_dir_path(__FILE__) . "import.txt", $new_input);

                // Meldung => Eintrag wurde erfolgreich importiert
                // Box soll überhalb der Überschrift sein
                $message = "Eintrag wurde erfolgreich importiert! Zum Bearbeiten des Artikels klicken Sie bitte folgenden Link:";

                // Es wird ein direkter Link zum Bearbeiten des zuletzt importieren Artikels angeboten
                $bearbeiten_link = get_edit_post_link($lastPostId);
                $link_link = "<td><a href=\"{$bearbeiten_link}\">" . $title[1] . "</a><td>";

                //
                // gelbe Messagebox, keine Fehlermeldung nur notice
                echo '<h2 id="message" class="updated fade">';
                //Link zum Bearbeiten


                echo "<p><strong>$message$link_link</strong></p></h2>";

                #break;
            }
        }
    }
    ?>
    <div class="wrap">
        <h2><?php echo wp_specialchars("Nachrichten Importieren"); ?></h2>
    </div>
    <p>
        <b>Wichtig für den Import:</b> Wir stellen euch die Inhalte zur Weiterverwendung zur Verfügung. Es darf aber nicht der komplette Artikel von Autonachrichten verwendet werden. Da unsere Redaktion zum Teil auch an Inhalte von Dritten (Automarken, Verbände, oder Fotografen) nutzt, musst du in bei der Veröffentlichung in deinem Blog sicherstellen, dass diese Copywrites auch angezeigt werden. Die Nennung aller Copywrites sind unter autonachrichten.de auf den Bildern und unter den Texten zu sehen. Wir empfehlen diese Nennungen zu überprüfen, da einige Rechteinhaber penibel mit der Einhaltung sind.<br/>

    </p>
    <?php
    $an_title = get_option('an_title');
    $an_display_width = get_option('an_display_width');
    $an_display_count = get_option('an_display_count');
    $an_record_height = get_option('an_record_height');
    $an_rss_url = get_option('an_rss_url');
    $an_show_image = 1;
    $an_categories = explode(",", get_option('an_categories'));


    $count = 0;


    echo "<table>";


    foreach ($xml[1] as $item) {
        echo "<tr>";
        $count++;
        //Wenn id schon vorhanden (cache), dann gib nur den Titel aus und nicht den Link

        preg_match("/\<title\>(.*?)\<\/title\>/", $item, $title);
        preg_match("/\<an_id\>(.*?)\<\/an_id\>/", $item, $an_artikel_id);

        $gefunden = array_search($an_artikel_id[1], $import_ids);

        if ($gefunden == false) {

            //Stellt Titel als Links dar
            echo "<td><a href=\"?page=autonachrichten/autonachrichten_newsfeed.php&anImportID=" . $an_artikel_id[1] . "\">" . $title[1] . "</a><td>";
            //dahinter Link auf Autonachrichten.de
            //var_dump($an_artikel_id);
        } else {
            //Stellt ausschließlich Titel dar, dieser soll nachdem er einmal importiert wurde nicht mehr anklickbar/importierbar sein
            echo "<td>" . $title[1] . "<td>";
        }



        echo "</tr>";
        if ($count > 10){
            break;
        }
    }
    echo "</table>";

    if ($_POST['an_submit']) {
        // delete Cachefile
        $file = $anWidget->getCachefile();

        if (file_exists($file)) {
            unlink($file);
            file_put_contents($file, "");
        } else {
            file_put_contents($file, "");
        }

        $an_title = stripslashes($_POST['an_title']);
        $an_display_width = stripslashes($_POST['an_display_width']);
        $an_display_count = stripslashes($_POST['an_display_count']);
        $an_record_height = stripslashes($_POST['an_record_height']);
        $an_rss_url = stripslashes($_POST['an_rss_url']);

        if (empty($_POST['an_show_preview']) || !isset($_POST['an_show_preview'])) {
            $an_show_preview = 0;
        } else {
            $an_show_preview = stripslashes($_POST['an_show_preview']);
        }

        if (isset($_POST['an_cat'])) {
            if (is_array($_POST['an_cat'])) {
                $save = array();
                foreach ($_POST['an_cat'] as $cat => $val) {
                    $save[] = $cat;
                }
                $an_categories = $save;
                $categories_str = implode(",", $save);
            }
        } else {
            $categories_str = "";
            $an_categories = array();
        }
        update_option('an_title', $an_title);
        update_option('an_display_width', $an_display_width);
        update_option('an_display_count', $an_display_count);
        update_option('an_record_height', $an_record_height);
        update_option('an_rss_url', $an_rss_url);
        update_option('an_show_image', $an_show_image);
        update_option('an_categories', $categories_str);
    }
    ?>

    <div class="wrap">
        <h2><?php echo wp_specialchars("Plugin Konfigurieren"); ?></h2>
    </div>

    <form name="an_form" method="post" action="">
    <?php
    echo '<p>Titel:<br><input  style="width: 200px;" type="text" value="';
    echo $an_title . '" name="an_title" id="an_title" /></p>';

    echo '<p>Anzahl gleichzeitiger Einträge:<br><input  style="width: 100px;" type="text" value="';
    echo $an_display_count . '" name="an_display_count" id="an_display_count" /></p>';

    echo '<p>Länge der Überschrift Maximal:<br><input  style="width: 100px;" type="text" value="';
    echo $an_display_width . '" name="an_display_width" id="an_display_width" /></p>';

    echo '<p><input type="checkbox" ' . ($an_show_image == "1" ? "checked=\"checked\"" : "") . '" name="an_show_image" value="1" id="an_show_image" /> Artikelbild anzeigen</p>';

    echo '<div class="wrap">
        <h2>Kategorien für den Newsticker</h2>
    </div>Wenn keine Kategorie ausgewählt ist, werden alle Nachrichten eingeblendet';

    $categories = array(
        3 => "Top News",
        4 => "Featured",
        159 => "Alfa-Romeo",
        1198 => "Aston Martin",
        19 => "Audi",
        1754 => "Bentley",
        60 => "BMW",
        1484 => "Bugatti",
        2932 => "Cadillac",
        2302 => "Chevrolet",
        2748 => "Chrysler",
        149 => "Citroen",
        4673 => "Dacia",
        1209 => "Daewoo",
        1447 => "Daihatsu",
        8395 => "Dodge",
        325 => "Fiat",
        581 => "Ford",
        363 => "Honda",
        5509 => "Infiniti",
        257 => "Jeep",
        14 => "Kia",
        491 => "Lamborghini",
        651 => "Lancia",
        7800 => "Land Rover",
        21 => "Lexus",
        8398 => "Lotus",
        3108 => "Maybach",
        430 => "Mazda",
        1610 => "McLaren",
        218 => "Mercedes",
        286 => "Mini",
        604 => "Mini",
        303 => "Nissan",
        92 => "Opel",
        993 => "Peugeot",
        473 => "Porsche",
        224 => "Renault",
        1558 => "Rolls-Royce",
        1684 => "Rover",
        566 => "Saab",
        320 => "Seat",
        261 => "Skoda",
        545 => "Smart",
        885 => "Subaru",
        452 => "Suzuki",
        8394 => "Tesla",
        313 => "Toyota",
        1035 => "Volkswagen",
        600 => "Volvo",
        8393 => "Wiesmann"
    );
    echo "<table><tr>";
    $i = 0;
    foreach ($categories as $id => $cat) {
        $i++;
        echo '<td style="width:120px; height:25px">';
        echo '<input type="checkbox" ' . (in_array($id, $an_categories) ? "checked=\"checked\"" : "") . '" name="an_cat[' . $id . ']" value="1" id="brand_' . $id . '" /> <label for="brand_' . $id . '">' . $cat . '</label>';
        echo "</td>";

        if ($i % 4 == 0) {
            echo "</tr><tr>";
        }
    }

    echo "</tr></table><br/><br/>";
    echo '<input name="an_submit" id="an_submit" lang="publish" class="button-primary" value="Einstellungen Speichern" type="Submit" />';
    ?>
    </form>
        <?php include_once("help.php"); ?>
        <?php
    }

function an_add_to_menu() {
    add_options_page('Autonachrichten Widget', 'Autonachrichten Widget', 'manage_options', __FILE__, 'an_admin_options');
}

if (is_admin()) {
    add_action('admin_menu', 'an_add_to_menu');
}

add_action('plugin_action_links_' . plugin_basename(__FILE__), 'an_newsfeed_plugin_actions');

// Add settings option
function an_newsfeed_plugin_actions($links) {
    $new_links = array();
    $new_links[] = '<a href="options-general.php?page=autonachrichten/autonachrichten_newsfeed.php">Einstellungen</a>';
    return array_merge($new_links, $links);
}

function an_deactivation() {
    delete_option('an_title');
    delete_option('an_display_count');
    delete_option('an_display_width');
    delete_option('an_record_height');
}

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action('init', array($anWidget, 'an_add_javascript_files'));
add_action("plugins_loaded", array($anWidget, "init"));
register_activation_hook(__FILE__, array($anWidget, 'an_install'));
register_deactivation_hook(__FILE__, 'an_deactivation');
add_action('admin_menu', 'an_add_to_menu');

