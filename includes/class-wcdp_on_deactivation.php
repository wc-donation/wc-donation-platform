<?php
/**
 * Deactivation Action.
 *
 * @since v1.2.7
 */

defined('ABSPATH') || exit;

class WCDP_On_Deactivation {
    private $survey_options;

	/**
	 * WCDP_On_Deactivation constructor
	 *
	 * @since v1.2.7
	 */
    public function __construct() {
        //add css and html to plugin.php page
        add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );

        //send survey response to WCDP server
        add_action( 'wp_ajax_wcdp_on_deactivation', array( $this, 'send_survey_data' ) );

        //options of the
        $this->survey_options = array(
            array(
                'id'          	=> 'no-need',
                'input' 		=> false,
                'text'        	=> __( "I no longer need the plugin.", "wc-donation-platform" ),
            ),
            array(
                'id'          	=> 'better-plugin',
                'input' 		=> true,
                'text'        	=> __( "I found a better plugin.", "wc-donation-platform" ),
                'placeholder' 	=> __( "Please share which plugin.", "wc-donation-platform" ),
            ),
            array(
                'id'          	=> 'stop-working',
                'input' 		=> true,
                'text'        	=> __( "The plugin suddenly stopped working.", "wc-donation-platform" ),
                'placeholder' 	=> __( "Please share more details.", "wc-donation-platform" ),
            ),
            array(
                'id'          	=> 'not-working',
                'input' 		=> true,
                'text'        	=> __( "I could not get the plugin to work.", "wc-donation-platform" ),
                'placeholder' 	=> __( "Please share more details.", "wc-donation-platform" ),
            ),
            array(
                'id'          	=> 'temporary-deactivation',
                'input' 		=> false,
                'text'        	=> __( "It's a temporary deactivation.", "wc-donation-platform" ),
            ),
            array(
                'id'          	=> 'other',
                'input' 		=> true,
                'text'        	=> __( "Other", "wc-donation-platform" ),
                'placeholder' 	=> __( "Please share the reason.", "wc-donation-platform" ),
            ),
        );
	}

    /**
     * Send survey result to wcdp server
     *
     * @return void
     * @since v1.2.7
     */
	public function send_survey_data() {
		$data = $this->get_data();

		if ( current_user_can( 'administrator' ) ) {
			wp_remote_post( 'https://wcdp.jonh.eu/wp-admin/admin-ajax.php', array(
				'method'        => 'POST',
				'timeout'       => 30,
				'redirection'   => 5,
				'headers'     => array(
                    'user-agent' => 'PHP/WCDP',
                    'Accept'     => 'application/json',
                ),
				'blocking'      => false,
				'httpversion'   => '1.0',
				'body'          => $data,
			) );
        }
	}

	/**
	 * ADD Deactivation Survey HTML, JS & CSS
	 *
	 * @since v1.2.7
	 * @return void
	 */
    public function get_source_data_callback() {
		global $pagenow;
        if ( $pagenow == 'plugins.php' ) {
            $this->on_deactivation_html();
            $this->on_deactivation_css();
            $this->on_deactivation_js();
		}
	}

    /**
     * echo html of survey modal
     *
     * @return void
     */
	public function on_deactivation_html() { ?>
    	<div class="wcdp-modal" id="wcdp-on-deactivation-modal">
            <div class="wcdp-modal-wrap">

                <div class="wcdp-modal-header">
                    <h2><?php esc_html_e( "Can you please help me to improve the plugin?", "wc-donation-platform" ); ?></h2>
                    <button class="wcdp-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
                </div>

                <div class="wcdp-modal-body">
                    <h3><?php esc_html_e( "If you have a moment, please let me know why you are deactivating Donation Platform for WooCommerce.", "wc-donation-platform" ); ?></h3>
                    <ul class="wcdp-modal-input">
						<?php foreach ($this->survey_options as $key => $option) { ?>
							<li>
								<label>
									<input type="radio" id="<?php echo esc_attr($option['id']); ?>" name="wcdp-survey" value="<?php echo esc_attr($option['text']); ?>">
									<div class="wcdp-reason-text"><?php echo esc_html($option['text']); ?></div>
									<?php if( isset($option['input']) && $option['input'] ) { ?>
										<textarea placeholder="<?php echo esc_attr($option['placeholder']); ?>" class="wcdp-reason-input <?php echo $key == 0 ? 'wcdp-active' : ''; ?> <?php echo esc_html($option['id']); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
                    </ul>

                    <div class="wcdp-modal-footer">
                        <a class="wcdp-modal-submit" href="#"><?php esc_html_e( "Submit & Deactivate", "wc-donation-platform" ); ?><span class="dashicons dashicons-update rotate"></span></a>
                        <a class="wcdp-modal-on-deactivation" href="#"><?php esc_html_e( "Skip & Deactivate", "wc-donation-platform" ); ?></a>
                    </div>

                    <p class="wcdp-greyed"><?php esc_html_e( "By submitting the survey, you agree that some non-sensitive technical diagnostic data will be send.", "wc-donation-platform" ); ?></p>

                </div>
            </div>
        </div>
	<?php }


	/**
	 * Deactivation Forms CSS File
	 *
	 * @since v1.2.7
	 * @return void
	 */
	public function on_deactivation_css() { ?>
		<style>
			.wcdp-modal {
                position: fixed;
                z-index: 99999;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: #0000007F;
                display: none;
                box-sizing: border-box;
                overflow: scroll;
            }
            .wcdp-modal * {
                box-sizing: border-box;
            }
            .wcdp-modal.modal-active {
                display: block;
            }
			.wcdp-modal-wrap {
                max-width: 870px;
                width: 100%;
                position: relative;
                margin: 10% auto;
                background: #fff;
            }
            .wcdp-modal-submit {
                color: white;
                background-color: #0c4a0d;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 3px;
            }
            .wcdp-modal-submit:hover {
                color: white;
                background-color: #0e8512;
            }
			.wcdp-reason-input{
				display: none;
			}
			.wcdp-reason-input.wcdp-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite;
			}
			@keyframes rotate {
				to{ transform: rotate(360deg); }
			}
			@keyframes popupRotate {
				to{ transform: rotate(360deg); }
			}
			#wcdp-on-deactivation-modal {
				background: #000000E2;
				overflow: hidden;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-wrap {
				max-width: 570px;
				border-radius: 3px;
				margin: 5% auto;
				overflow: hidden
			}
			#wcdp-on-deactivation-modal .wcdp-modal-header {
				padding: 17px 10px 17px 30px;
				border-bottom: 1px solid #eaeaea;
				display: flex;
				align-items: center;
				background: #f8f8f8;
                font-size: 1.3em;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-header .wcdp-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #0c4a0d;
				background: none;
				color: #0c4a0d;
				cursor: pointer;
				transition: 400ms;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-header .wcdp-modal-cancel:focus, #wcdp-on-deactivation-modal .wcdp-modal-header .wcdp-modal-cancel:hover {
				color: #0e8512;
				border: 1px solid #0e8512;
				outline: 0;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body {
				padding: 25px 30px;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul {
				margin: 25px 0 10px;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li label textarea {
				margin-top: 8px;
				width: 350px;
			}
			#wcdp-on-deactivation-modal .wcdp-modal-body ul li label .wcdp-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
            #wcdp-on-deactivation-modal .wcdp-modal-footer {
                padding-top: 15px;
                display: flex;
                align-items: center;
            }
            #wcdp-on-deactivation-modal .wcdp-modal-footer .wcdp-modal-submit {
                display: flex;
                align-items: center;
            }
            #wcdp-on-deactivation-modal .wcdp-modal-footer .wcdp-modal-submit span {
                margin-left: 4px;
                display: none;
            }
            #wcdp-on-deactivation-modal .wcdp-modal-footer .wcdp-modal-submit.loading span {
                display: block;
            }
            #wcdp-on-deactivation-modal .wcdp-modal-footer .wcdp-modal-on-deactivation {
                margin-left: auto;
                color: #939393;
                text-decoration: none;
            }
            .wcdp-greyed {
                color: #939393;
                font-size: 0.9em;
            }
		</style>
    <?php }


	/**
	 * Deactivation Forms JS script
	 *
	 * @since v1.2.7
	 * @return void
	 */
	public function on_deactivation_js() { ?>
        <script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				'use strict';

				// Modal Radio Input Click Action
				$('.wcdp-modal-input input[type=radio]').on( 'change', function(e) {
					$('.wcdp-reason-input').removeClass('wcdp-active');
					$('.wcdp-modal-input').find( '.'+$(this).attr('id') ).addClass('wcdp-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.wcdp-modal-cancel', function(e) {
					$( '#wcdp-on-deactivation-modal' ).removeClass( 'modal-active' );
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-wc-donation-platform', function(e) {
					e.preventDefault();
					$( '#wcdp-on-deactivation-modal' ).addClass( 'modal-active' );
					$( '.wcdp-modal-on-deactivation' ).attr( 'href', $(this).attr('href') );
					$( '.wcdp-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				// Submit to Server
				$( document ).on( 'click', '.wcdp-modal-submit', function(e) {
					e.preventDefault();

					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						type: 'POST',
						data: {
							action: 'wcdp_on_deactivation',
							cause_id: $('input[type=radio]:checked').attr('id'),
							cause_details: $('.wcdp-reason-input.wcdp-active').val()
						},
						success: function () {
							$( '#wcdp-on-deactivation-modal' ).removeClass( 'modal-active' );
							window.location.href = url;
						},
						error: function(xhr) {
							console.log( 'WCDP: Error occured. Please try again' + xhr.statusText + xhr.responseText );
						},
					});
				});
			});
		</script>
    <?php }


	/**
	 * Get All the Installed Plugin Data
	 *
	 * @since v1.2.7
	 * @return ARRAY Plugin Information
	 */
	public function get_plugins(): array
    {
		if ( ! function_exists( 'get_plugins' ) ) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }

		$active = array();
        $inactive = array();
        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', array() );
		if (is_multisite()) {
			$active_plugins = array_merge($active_plugins, array_keys(get_site_option( 'active_sitewide_plugins', array() )));
		}

        foreach ( $all_plugins as $key => $plugin ) {
            $e = $key . '/' . $plugin['Version'];

			if ( in_array( $key, $active_plugins ) ){
				$active[] = $e;
			} else {
				$inactive[] = $e;
			}
		}

		return array( 'active' => $active, 'inactive' => $inactive );
	}

	/**
	 * Get non-sensitive diagnostic Data
	 *
	 * @since v1.2.7
	 * @return ARRAY Data to send
	 */
	public function get_data(): array
    {
		global $wpdb;
		$plugins_data = $this->get_plugins();

        return array(
            'action'            => 'wcdp_deactivation_survey',
            'name'              => get_bloginfo( 'name' ),
            'home'              => esc_url( home_url() ),
            'wp_version'        => get_bloginfo( 'version' ),
            'memory_limit'      => WP_MEMORY_LIMIT,
            'debug_mode'        => defined('WP_DEBUG') && WP_DEBUG,
            'locale'            => get_locale(),
            'multisite'         => is_multisite(),

            'active_theme'      => get_stylesheet(),
            'active_plugins'    => $plugins_data['active'],
            'inactive_plugins'  => $plugins_data['inactive'],
            'server'            => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_key($_SERVER['SERVER_SOFTWARE']) : '',

            'timezone'          => date_default_timezone_get(),
            'php_curl'          => function_exists( 'curl_init' ),
            'php_version'       => function_exists('phpversion') ? phpversion() : '',
            'upload_size'       => size_format( wp_max_upload_size() ),
            'mysql_version'     => $wpdb->db_version(),
            'php_fsockopen'     => function_exists( 'fsockopen' ),

            'cause_id'          => isset($_POST['cause_id']) ? esc_attr($_POST['cause_id']) : '',
            'cause_details'     => isset($_POST['cause_id']) ? sanitize_text_field($_POST['cause_details']) : '',
        );
	}
}
