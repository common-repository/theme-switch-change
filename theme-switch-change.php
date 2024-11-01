<?php
/**
 * Theme Switch Change
 * @author    Lenin Zapata <leninzapatap@gmail.com>
 * @link      https://leninzapata.com
 * @copyright 2019 Theme Switch Change
 * @package   Theme Switch Change
 *
 * @wordpress-plugin
 * Plugin Name: Theme Switch Change
 * Plugin URI: https://wordpress.org/plugins/theme-switch-change/
 * Author: Lenin Zapata
 * Author URI: https://leninzapata.com
 * Version: 1.2.3
 * Description: Quickly and easily change the themes you have installed for quick views and test.
 * Text Domain: theme-switch-change
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
final class theme_switch_change{

    private static
    /**
     * Current theme information
     * @since   1.0     2019-08-13      Release
     * @access  private
     * @var     array
     */
    $current_theme = null,
    /**
     * List of all installed themes (only names)
     * @since   1.0     2019-08-13      Release
     * @access  private
     * @var     array
     */
    $themes_list = [],
    /**
     * List of all installed themes (only names)
     * @since   1.0     2019-08-13      Release
     * @access  private
     * @var     array
     */
    $themes_array = [];

    /**
     * get all parent and children themes
     * with their respective parameters.
     *
     * @since   1.0     2019-08-19      Release
     * @return  void
     */
    private static function get_themes() {

        // ─── Get current theme information ────────
        self::$current_theme = wp_get_theme();
        $current_theme_id = self::$current_theme->get_stylesheet();

        // ─── Get all the themes ────────
        $themes = wp_get_themes();
        if ( ! isset( $themes ) || ! is_array( $themes ) ) { return; }

        $count = 1;
        foreach ($themes as $value) {

            $count++;
            $template   = $value->get_template();
            $stylesheet = $value->get_stylesheet();
            $screenshot = $value->get_screenshot();

            $data = array(
				'id'         => urlencode( str_replace( '/', '-', strtolower( $stylesheet ) ) ),
				'name'       => $value['Name'],
                //'link'       => admin_url( wp_nonce_url( "themes.php?action=activate&amp;template=" . urlencode( $template ) . "&amp;stylesheet=" . urlencode( $stylesheet ) . '&amp;return=/', 'switch-theme_' . $stylesheet) ),
                'link'        => '#',
                'template'    => $template,
                'meta'        => array( 'class' => ( $stylesheet == $current_theme_id ? 'theme-sc-current_theme' : '' ), 'onclick' => 'themeSC_change_theme("'. $stylesheet .'","'. $template .'"); return false;', ),
                'stylesheet'  => $stylesheet,
                'img'         => $screenshot,
                'author'      => $value->get('Author'),
                'author_link' => $value->get('AuthorURI'),
                'theme_url'   => $value->get('ThemeURI'),
                'version'     => $value->get('Version'),
				// 'type' is set later
				// 'active' is set later
            );

            // Verify parent or child
			if ($template == $stylesheet) {
				$data['type'] = 'parent';
			} else {
				$data['type'] = 'child';
            }

            // add the theme to the lists
			self::$themes_array[] = $data;
			self::$themes_list [] = $data['name'];

        }

    }

    /**
     * Create the main menu and submenus of the theme list
     *
     * @since   1.0     2019-08-19      Release
     * @return  void
     */
    public static function create_admin_menu(){

        global $wp_admin_bar, $pagenow;

        // ─── Verify if the user is allowed ────────
        if ( ! current_user_can( 'switch_themes' ) || $pagenow == 'themes.php' ) { return; }

        // ─── Execute the query of installed themes ────────
        self::get_themes();

        // ─── Check if there are themes to display ────────
        if ( empty(self::$themes_array ) ) { return; }

        // ─── Initialize variables ────────
        $child_themes  = [];
        $parent_themes = [];

        // ─── Current theme name ────────
        $menu_label =self::$current_theme->display( 'Name' );
        $menu_label_ex = '<span class="ab-icon dashicons-admin-appearance"></span><span class="ab-label">' .
                        sprintf( __( 'Theme: %s','theme-switch-change' ) , '<strong>' . $menu_label . '</strong>' ) . '</span>';

        // ─── Put the menu ID ────────
        $menu_id = 'theme-sc-themeswitch';

        // ─── Classify themes by type ────────
        foreach (self::$themes_array as $k => $v ) {
			if ( $v['template'] != $v['stylesheet'] ) {
				$child_themes[] = $v;
			} else {
				$parent_themes[] = $v;
			}
        }

        // ─── If there are Themes children then we add a class in the parent menu ────────
        $class_main_menu = '';
        if( ! empty($child_themes) && count($child_themes)>0 ){
            $class_main_menu = 'theme-sc-parent-children';
        }

        // ─── Add menu in main bar ────────
		$wp_admin_bar->add_node( array(
			'id'    => $menu_id,
			'title' => $menu_label_ex,
            'href'  => admin_url('themes.php'),
            'meta'   => [ 'class' => $class_main_menu ]
        ));

        // ─── If there are Theme children then we put the header that these are the parents ────────
        if( ! empty($child_themes) && count($child_themes)>0 ){
            $wp_admin_bar->add_node( array(
                'id'     => 'theme-sc-parent',
                'title'  => 'Parents themes ('.count($parent_themes).')',
                'href'   => '',
                'parent' => $menu_id,
                'meta'   => [ 'class' => 'theme-sc-header' ]
            ));
        }
        // ─── Show the Theme parents ────────
        foreach ($parent_themes ?: [] as $value) {
            $wp_admin_bar->add_node( array(
                'id'     => 'theme-sc-' . $value['id'],
                'title'  => $value['name'] . self::get_info_theme( $value ),
                'href'   => $value['link'],
                'parent' => $menu_id,
                'meta'   => $value['meta'],
            ));
        }
        // ───────────────────────────

        // ─── Theme children header ────────
        if( ! empty($child_themes) && count($child_themes)>0 ){
            $wp_admin_bar->add_node( array(
                'id'     => 'theme-sc-child',
                'title'  => 'Children themes ('.count($child_themes).')',
                'href'   => '',
                'parent' => $menu_id,
                'meta'   => [ 'class' => 'theme-sc-header' ]
            ));
        }
        // ─── Show the children themes ────────
        foreach ($child_themes ?: [] as $value) {
            $wp_admin_bar->add_node( array(
                'id'     => 'theme-sc-child-' . $value['id'],
                'title'  => $value['name'] . self::get_info_theme( $value ),
                'href'   => $value['link'],
                'parent' => $menu_id,
                'meta'   => $value['meta'],
            ));
        }

    }

    /**
     * Get extra information from the Theme
     *
     * @since   1.0         2019-08-20      Release
     * @since   1.2.1       2019-09-06      Get the theme or author link
     *
     * @param   array       $data           Partial Theme Information
     * @return  string|html
     */
    private static function get_info_theme( $data ){

        $html = '';

        if( ! empty( $data ) ){

            // ─── Set image theme ────────
            $html .= '<div class="theme-sc-screenshot">
                <img src="'. $data['img'] .'" />';

            // ─── Set author theme with link ────────
            $author = '';
            $author = $data['author'];
            $link = ! empty( $data['theme_url'] ) ? $data['theme_url'] : ( ! empty( $data['author_link'] ) ? $data['author_link'] : '' );
            $html .= '<div class="theme-sc-screenshot-info">';
                $html .= '<div><span>Author:</span> ' . $author . '  <span class="theme-link dashicons dashicons-external" data-url="'.$link.'"></span> </div>';
                $html .= '<div><span>Version:</span> ' . $data['version'] . '</div>';
            $html .= '</div>';

            $html .= '</div>';
        }

        return $html;
    }

    /**
	 * Saves the current browsed location before switch
     *
     * @since   1.0     2019-08-19      Release
     * @return  void
	 */
	public static function before_theme_switch() {
		$url = esc_url_raw($_SERVER['HTTP_REFERER']);
        $url = parse_url($url);
		if (!empty($url['path'])) set_transient( 'theme-sc_themeswitch_lasturl', $url['path'] . ( !empty($url['query']) ? '?' . $url['query'] : '' ), 60 );
    }

    /**
	 * After changing the theme the information is stored in cookie
     * to have the current theme updated.
     *
     * @since   1.0     2019-08-19      Release
     * @since   1.1     2019-09-01      Update code for correction update cookie
     * @return  void
	 */
	public static function after_theme_switch( $theme = null ) {
        self::$current_theme = wp_get_theme();
        $current_theme_stylesheet_id = self::$current_theme->get_stylesheet();
        $current_theme_template_id = self::$current_theme->get_template();
        self::update_cookie( $current_theme_stylesheet_id, 'stylesheet' );
        self::update_cookie( $current_theme_template_id );
    }

    /**
     * Change the style of the Theme.
     * Through Cookie and only works for the administrator role for testing.
     *
     * @since   1.0     2019-08-19      Release
     * @since   1.1     2019-09-01      Corrections and validations for the themes.php page
     * @since   1.2.3   2019-10-27      Corrected in case of one of the cookies that controls the change of theme does not exist
     *                                  Then this will show the active theme.
     *
     * @param   string  $current_theme  Current active theme from the database
     * @return  string
     */
    public static function themesc_swicth_stylesheet( $current_theme ) {
        global $pagenow;

        // ─── If you are inside the theme change page then the same activated theme returns ────────
        if( $pagenow == 'themes.php' ){
            if( !empty( $_GET['action'] ) && !empty( $_GET['stylesheet'] ) )
                self::update_cookie( $current_theme, 'stylesheet' );

            return $current_theme;

        }elseif ( ! empty( $_COOKIE['themesc_template_change'] ) ){ // if you change the theme through the plugin
            // Use your preview theme instead
            self::delete_cookie();
            $current_theme = $_COOKIE['themesc_stylesheet'];
            self::update_cookie( $current_theme, 'stylesheet' );

        }elseif( ! empty( $_COOKIE['themesc_stylesheet'] ) && ! empty( $_COOKIE['themesc_template'] ) ){ // If there is an active theme change cookie, then it returns
            $current_theme = $_COOKIE['themesc_stylesheet'];

        }

        return $current_theme;
    }

    /**
     * Change the style of the Theme (children in case you have it).
     * Through Cookie and only works for the administrator role for testing.
     *
     * @since   1.0     2019-08-19      Release
     * @since   1.1     2019-09-01      Corrections and validations for the themes.php page
     * @since   1.2.3   2019-10-27      Corrected in case of one of the cookies that controls the change of theme does not exist
     *                                  Then this will show the active theme.
     *
     * @param   string  $current_theme  Current active template from the database
     * @return  string
     */
    public static function themesc_swicth_template( $current_theme ) {
        global $pagenow;

        // ─── If you are inside the theme change page then the same activated theme returns ────────
        if( $pagenow == 'themes.php' ){
            if( !empty( $_GET['action'] ) && !empty( $_GET['stylesheet'] ) )
                self::update_cookie( $current_theme );
            return $current_theme;

        }elseif (  ! empty( $_COOKIE['themesc_template_change'] ) && ! empty( $_COOKIE['themesc_template'] ) ){ // if you change the theme through the plugin
            // Use your preview theme instead
            self::delete_cookie();
            $current_theme = $_COOKIE['themesc_template'];
            self::update_cookie( $current_theme );

        }elseif( ! empty( $_COOKIE['themesc_template'] ) && ! empty( $_COOKIE['themesc_stylesheet'] ) ){ // If there is an active theme change cookie, then it returns
            $current_theme = $_COOKIE['themesc_template'];
        }

        return $current_theme;
    }

    /**
     * Update the theme to be displayed through a cookia for the temporary preview view
     *
     * @since   1.1     2019-09-01      Release
     *
     * @param   string  $name           Cookie name
     * @param   string  $prefix         Name Prefix
     * @return  void
     */
    private static function update_cookie( $name = '', $prefix = 'template' ){
        setcookie( 'themesc_' . $prefix, $name, time()+84000, '/');
    }

    /**
     * Delete a cookie
     *
     * @since   1.1     2019-09-01      Release
     * @param   string  $name           Name of the cookie to delete
     * @return  void
     */
    private static function delete_cookie( $name = 'themesc_template_change' ){
        unset($_COOKIE[$name]);
        setcookie( $name, null, -1, '/');
    }

    /**
     * Function that creates the variable cookie to know what theme to display
     * this will be printed on the footer and only printed for administrator role
     *
     * @since   1.0     2019-08-19      Release
     * @since   1.2.1   2019-09-06      - The preview of the link was separated so that it is not clickable
     *                                  - Theme author link added
     *
     * @return  void
     */
    public static function footer(){
        if( ! current_user_can( 'administrator' ) ) return; ?>
<script>
function themesc_createCookie(name,value,days){var expires;if(days){var date=new Date();date.setTime(date.getTime()+(days*24*60*60*1000));expires="; expires="+date.toGMTString()}else{expires=""} document.cookie=encodeURIComponent(name)+"="+encodeURIComponent(value)+expires+"; path=/"}
function themeSC_change_theme( stylesheet, template ){
    themesc_createCookie( 'themesc_stylesheet', stylesheet, 1 );
    themesc_createCookie( 'themesc_template', template, 1 );
    themesc_createCookie( 'themesc_template_change', 1, 1 );
    location.reload();
}
jQuery(document).ready(function( $ ){
    // ─── move the element a higher level to correct that it is not a clickable element ────────
    $('#wp-admin-bar-theme-sc-themeswitch .theme-sc-screenshot').each(function(){
        var final_paste = $(this).parent().parent();
        $($(this).detach()).appendTo(final_paste);
    });

    // ─── Redirect to the Theme or Author page ────────
    $('#wp-admin-bar-theme-sc-themeswitch-default .theme-link').on('click', function(){
        var link = $(this).attr('data-url');
        window.open(link, '_blank');
    })
});
</script>
    <?php
    }

    /**
     * Set the necessary CSS for the themes menu to look correctly
     * this will be printed on the footer and only printed for administrator role
     *
     * @since   1.0     2019-08-19      Release
     * @since   1.1     2019-09-01      Validation to show this css code only for the admin user
     * @since   1.2.1   2019-09-06      Styles for the author link
     *
     * @return  void
     */
    public static function header(){
        // ─── Verify if the user is allowed ────────
        if( ! current_user_can( 'administrator' ) ) return;
?>
<style>
#wp-admin-bar-theme-sc-themeswitch-default.ab-submenu{
    padding: 0;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-header{
    background: #2b2b2b;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-header .ab-empty-item{
    height: 20px!important;
    line-height: 20px!important;
    font-size: 11px;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot{
    display: none;
    position: absolute;
    right: -250px;
    top: 0;
    width: 250px;
    height: auto;
    overflow: hidden;
    box-shadow: 1px 1px 1px #1b1b1bf5;
    background: #1b1b1bf5;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot img{
    display:block;
    max-width: 100%;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info{
    padding: 5px;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info > div{
    height: 18px;
    line-height: 18px;
    color:#bbbbbb;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info > div span{
    height: 18px;
    line-height: 18px;
    color:gray;
    font-style: italic;
    margin-right: 2px;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu li:hover .theme-sc-screenshot{
    display:block;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu li:last-child{
    padding-bottom: 5px;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu > li:not(.theme-sc-header) a{
    margin-left: 15px!important;
}
#wp-admin-bar-theme-sc-themeswitch-default li.theme-sc-current_theme a:before{
    content: '✔';
    position: absolute;
    left: 5px;
    top: 5px;
    font-size: 9px;
    border: 1px solid;
    padding: 2px 3px 4px 4px;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-link{
    font-family: dashicons;
    z-index: 999;
    cursor: pointer;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-link:hover:before{
    color:#fff;
}
</style>
    <?php
    }
}
/*
|--------------------------------------------------------------------------
| Hooks and Filter that run only in a timely manner
|--------------------------------------------------------------------------
*/
add_action( 'wp_footer', ['theme_switch_change','footer'], 10 );
add_action( 'admin_footer', ['theme_switch_change','footer'], 10 );
add_action( 'wp_head', ['theme_switch_change','header'], 10 );
add_action( 'admin_head', ['theme_switch_change','header'], 10 );
add_action( 'admin_bar_menu',  [ 'theme_switch_change', 'create_admin_menu' ], 100  );
add_action( 'switch_theme', ['theme_switch_change','after_theme_switch']);
add_filter( 'template',  ['theme_switch_change','themesc_swicth_template'], 10 );
add_filter( 'stylesheet', ['theme_switch_change','themesc_swicth_stylesheet'], 10 );

// Lenin Zapata - 😄