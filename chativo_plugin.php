<?php
/*
Plugin Name: Chativo
Description: Enable Chativo Chat Widget
Version: 1.1.0
Author: Chativo.io
*/

//Catch anyone trying to directly acess the plugin - which isn't allowed
if (!function_exists('add_action')) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

class ChativoChatWidget extends WP_Widget {

    static $added = 0;
    /**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
	    parent::__construct(
			'chativo_chat_widget', // Base ID
			__( 'Chativo Chat', 'text_domain' ), // Name
			array( 'description' => __( 'Enable Chativo Chat Widget', 'text_domain' ), ) // Args
        );
	}

/*
 * Outputs the options form on admin
 *
 * @param array $instance The widget options
 */
public function form($instance) {

}

/**
 * Saves changes
 * @param array $new_instance The widget options
 * @param array $old_instance The widget options
 */

public function update( $new_instance, $old_instance ) {

}
    /**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
public function widget( $args, $instance ) {
    add_action( 'wp_footer', function() use ( $args, $instance ) {
    global $lang, $api;
    if ( static::$added !== 0 ) return; // run only once;
    static::$added++;

    //Check if user already exists
    function user_id_exists($user){

        global $wpdb;
    
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));
        if($count == 1){ return true; }else{ return false; }
    }

    $a = get_option('chativo_settings'); // Initialize
    $api = $a['APIKey_field'];
    $lang = $a['Lang_field'];

    if (strpos(get_locale(), 'en_') > -1) {
        $lang = 'en-US';
    }
    else if (strpos(get_locale(), 'zh_') > -1) {
        $lang = 'zh-CN';
    }

    // outputs the content of the widget
    echo <<<EOL
        <script type="text/javascript">
        var infObj = {
        apiKey: "$api",
        lang: "$lang",
        src: 'https://supwid.chativo.io',
        };

        const elem = document.createElement('div');
        elem.id = 'chativo-web-widget';
        document.body.appendChild(elem);

        const imported = document.createElement('script');
        imported.src = 'https://supwid.chativo.io/widget.js';
        document.head.appendChild(imported);
        </script>
EOL;
});
}
}

//Config Page
//===================================================================
add_action( 'admin_menu', 'chativo_add_admin_menu' );
add_action( 'admin_init', 'chativo_settings_init' );

function chativo_add_admin_menu(  ) {
    add_menu_page( 'Chativo', 'Chativo', 'manage_options', 'settings-api-page', 'chativo_options_page' );
}

function chativo_settings_init(  ) {
    register_setting( 'idPlugin', 'chativo_settings' );

    add_settings_section(
        'chativo_idPlugin_section',
        __( '', 'wordpress' ),
        'chativo_settings_section_callback',
        'idPlugin'
    );

    add_settings_field(
        'APIKey_field',
        __( 'API-Key', 'wordpress' ),
        'APIKey_field_render',
        'idPlugin',
        'chativo_idPlugin_section'
    );

    add_settings_field(
        'Lang_field',
        __( 'Language', 'wordpress' ),
        'Lang_field_render',
        'idPlugin',
        'chativo_idPlugin_section'
    );
}

function modify_admin_bar_css() { ?>
    <style type="text/css">
        #wpadminbar {
            z-index: 10000; /*Ensure widget is stacked in front of admin bar*/
        }
    </style>
<?php }

add_action( 'wp_head', 'modify_admin_bar_css' );

function APIKey_field_render(  ) {
    $options = get_option( 'chativo_settings' );
    ?>
        <input id="ak" type='text' style="width:90%;background-color:#DCDCDC;" autocomplete="off" name='chativo_settings[APIKey_field]' required value=''>
    <?php
}

function Lang_field_render(  ) {
    $options = get_option( 'chativo_settings' );    
    ?>
    <select name='chativo_settings[Lang_field]'>
        <option value='en-US' <?php selected( $options['Lang_field'], 1 ); ?>>English</option>
        <option value='zh-CN' <?php selected( $options['Lang_field'], 2 ); ?>>Chinese</option>
    </select>
<?php
}

function chativo_settings_section_callback(  ) {
    echo __( '', 'wordpress' );
}  

function chativo_options_page(  ) {
        ?>
            <script>
                function togg(){
                    var x = document.getElementById("domainPanel");
                    var y = document.getElementById("settingsPanel");
                    if (x.style.display === "none") {
                        x.style.display = "";
                        y.style.display = "none";
                    } else {
                        y.style.display = "none";
                    }
                }
    
                function domainTogg(){
                    var x = document.getElementById("settingsPanel");
                    var y = document.getElementById("domainPanel");
                    if (x.style.display === "none") {
                        x.style.display = "";
                        y.style.display = "none";
                    }
                }

                //ajax for channel name
                function ajx($ak){
                    var dataObj = {
                        "key":  $ak
                    };

                    var dataJSON = JSON.stringify(dataObj);
                    jQuery(document).ready(function($) {
                        $.ajax({
                            url: 'https://engine.chativo.io/api/v2/auth/client/key',
                            type: 'post',
                            data: dataJSON,
                            headers: {
                                "X-API-KEY": 'CHATIVO_WORDPRESS',
                                "Content-Type": 'application/json'
                            },
                            dataType: 'json',
                            success: function (data) {
                                var x = document.getElementById("optName");
                                x.innerHTML = data["name"];
                                x.value = data["name"];
                            }
                        });
                    });
                }

                function updateAlert(){
                    var x = document.getElementById("updateAlert");
                    x.style.display = "block";

                    var y = document.getElementById("ak").value;
                    ajx(y);
                }

                //Check for channel name on load
                window.onload = function getName() { 
                    var x = document.getElementById("optName");
                    ajx('<?php $cs = get_option('chativo_settings'); echo $cs["APIKey_field"]; ?>');
                }

            </script>
        <?php
    ?>
    <div style="display: none; background-color:#C3F1BB;border:1px solid #D3D3D3; width:95%; padding-left: 10px; margin-top:10px;" id="updateAlert">
        <h3>Settings updated.</h3>
    </div>
    <form action='options.php' method='post' style="margin-top:18px;" onsubmit="updateAlert();">

        <table style="background-color:white;border:1px solid #D3D3D3;width:95%;border-collapse:collapse;">
            <tr style="padding:10px 0px 10px 0px;">
                <td style="border:1px solid #D3D3D3;padding-top:10px;">
                <div style="text-align: center;padding: 4px 10px 0px 30px;display:inline-block;">
                    <img src="https://ps.w.org/chativo/assets/chativo1.png" />
                </div>
                <div style="vertical-align: top; display:inline-block;">
                    <h3 style="color: black;font-size:2em;">Plugin Settings</h3>
                </div>
                </td>
            </tr>
            <tr>
                <td>
                    <table style="background-color:white;border:1px solid #D3D3D3;border-collapse:collapse;width:100%;">
                        <tr>
                            <td style="border:1px solid #D3D3D3;">
                                <table style="border-collapse:collapse;">
                                <tbody>
                                    <tr>
                                        <td>
                                            <button type="button" onclick="togg()" style="text-align:center;width:15em;background-color:#6C6EFF;color:#ffffff;padding:13px 0px 13px 0px;margin:0px;"><h4>Account Settings</h4></button>    
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="text-align:center;width:10em;padding:15px 0px 15px 0px;margin:0px;"><h4></h4></div> 
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="text-align:center;width:10em;padding:15px 0px 15px 0px;margin:0px;"><h4></h4></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="text-align:center;width:10em;padding:15px 0px 15px 0px;margin:0px;"><h4></h4></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="text-align:center;width:10em;padding:15px 0px 15px 0px;margin:0px;"><h4></h4></div>
                                        </td>
                                    </tr>
                                </tbody>
                                </table>
                            </td>
                            <td style="border:1px solid #D3D3D3;width:100%;">
                                <div id="domainPanel" style="border:1px solid #D3D3D3;padding:0px 20px 0px 20px;margin:0px;height:22em;">
                                    <table>
                                        <tr>
                                            <td>
                                                <div class="update-nag notice" style="background-color:#ffefda;padding:5px 50px 3px 10px;margin:0px;margin-top:5px;color:black"><p>At Appearance -> Widgets page, Drag and Drop the widget into the site's footer.</p></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <h3>Edit the Channel Name in</h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <h3 style="display:inline;margin-right:15px;">Channel Name </h3>
                                                <select style="background-color:#DCDCDC;border-radius:5px;">
                                                    <option id="optName"></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                            <br>
                                            <button type="button" style="font-weight:bold;border:1px solid #6C6EFF;background-color:#ffffff; color:#6C6EFF;width:wrap-content;padding:10px 20px 10px 20px;border-radius:5px;text-align:center;" onclick="domainTogg()">Edit</button>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div id="settingsPanel" style="border:1px solid #D3D3D3;padding:0px 20px 0px 20px;margin:4px;display:none;">
                                    <img src="https://ps.w.org/chativo/assets/wordpress_setting_banner.png" />
                                    <h3 style="display: block;">Instruction: Click <a href="https://account.chativo.io/" style="color: #ef5088;">here</a> to login Chativo and copy paste support channel API key to enable live chat for your website</h3>
                                    <?php
                                        settings_fields( 'idPlugin' );
                                        do_settings_sections( 'idPlugin' );
                                    ?>
                                    <h3 style="display: block;">Don't have a Chativo.io account? Go to <a href="https://account.chativo.io/signup" style="color: #ef5088;">Chativo.io</a> and create one!</h3>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left:12px;">
                            <?php
                                submit_button();
                            ?>
                            </td>
                            <td>
                                <p style="float:right;margin-right:8px;">Having trouble and need some help? Check out our <a href="https://infinacle.com/desk/knowledgebase/#1561537651100-5032d0a9-f6a9">Knowledge Base</a>.</p>
                            </td>
                        </tr>
                    </table>            
                </td>
            </tr>
        </table>
    </form>
    
    <?php
}
//===================================================================
    add_action( 'widgets_init', function(){
        register_widget( 'ChativoChatWidget' );
    });

    function chativo_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=settings-api-page">' . __( 'Settings' ) . '</a>';
        array_unshift( $links, $settings_link );
    
          return $links;
    }
    $plugin = plugin_basename( __FILE__ );
    add_filter( "plugin_action_links_$plugin", 'chativo_settings_link' );
?>
