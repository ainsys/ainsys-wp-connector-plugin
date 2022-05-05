<?php

add_action( 'wpcf7_init', 'wpcf7_ainsys_register_service', 15, 0 );

function wpcf7_ainsys_register_service() {
	$integration = WPCF7_Integration::get_instance();

	$integration->add_category( 'ainsys',
		__( 'AINSYS', 'contact-form-7' )
	);

	$integration->add_service( 'ainsys',
		WPCF7_Ainsys::get_instance()
	);
}

add_filter( 'wpcf7_form_hidden_fields',
	'wpcf7_ainsys_add_hidden_fields', 100, 1
);

function wpcf7_ainsys_add_hidden_fields( $fields ) {
	$service = WPCF7_Ainsys::get_instance();

	if ( ! $service->is_active() ) {
		return $fields;
	}

	return array_merge( $fields, array(
		'_wpcf7_ainsys_referrer'   => Ansys\connector\WooCommerce\utm_hendler::get_referer_url(),
		'_wpcf7_ainsys_user_agent' => $_SERVER['HTTP_USER_AGENT'],
		'_wpcf7_ainsys_ip'         => Ansys\connector\WooCommerce\utm_hendler::get_my_ip(),
		'_wpcf7_ainsys_roistat'    => Ansys\connector\WooCommerce\utm_hendler::get_roistat()
	) );
}

add_action( 'wpcf7_submit', 'on_wpcf7_submit', 10, 2 );

function on_wpcf7_submit( $wpcf7, $result ) {
	if ( 'mail_sent' !== $result['status'] ) {
		return $wpcf7;
	}

	$form_id = $wpcf7->id();

	$fields = $wpcf7->scan_form_tags();

	foreach ( $fields as $key => $field ) {
		if ( 'submit' === $field['basetype'] ) {
			unset( $fields[ $key ] );
		}
	}

	$fields = array_values( $fields );

	$request_action = 'UPDATE';

	$request_data = array(
		'entity'  => [
			'id'   => 0,
			'name' => 'wpcf7_' . $form_id
		],
		'action'  => $request_action,
		'payload' => $_POST
	);

	try {
		$server_responce = Ansys\connector\WooCommerce\ainsys_core::curl_exec_func( $request_data );
	} catch ( Exception $e ) {
		$server_responce = 'Error: ' . $e->getMessage();
	}

	Ansys\connector\WooCommerce\ainsys_core::save_log_information( $form_id, $request_action, serialize( $request_data ), serialize( $server_responce ), 0 );

	return $wpcf7;
}

if ( ! class_exists( 'WPCF7_Service' ) ) {
	return;
}

class WPCF7_Ainsys extends WPCF7_Service {

	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function get_title() {
		return 'Ainsys';
	}

	public function get_categories() {
		return array( 'ainsys' );
	}

	public function icon() {
	}

	public function display( $action = '' ) {
		echo '<p>' . sprintf(
				esc_html( __( 'Ainsys позволяет интегрировать данные, поступающие через формы Contact Form 7 в вашу экосистему', 'contact-form-7' ) ),
				wpcf7_link(
					__( 'https://contactform7.com/recaptcha/', 'contact-form-7' ),
					__( 'reCAPTCHA (v3)', 'contact-form-7' )
				)
			) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "Ainsys включен на этом сайте.", 'contact-form-7' ) )
			);
		}

//		if ( 'setup' == $action ) {
//			$this->display_setup();
//		} else {
//			echo sprintf(
//				'<p><a href="%1$s" class="button">%2$s</a></p>',
//				esc_url( $this->menu_page_url( 'action=setup' ) ),
//				esc_html( __( 'Setup Integration', 'contact-form-7' ) )
//			);
//		}
	}

	public function is_active() {
		return true;
	}

	private function display_setup() {
		$sitekey = $this->is_active() ? $this->get_sitekey() : '';
		$secret  = $this->is_active() ? $this->get_secret( $sitekey ) : '';

		?>
        <form method="post" action="<?php
		echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
			<?php
			wp_nonce_field( 'wpcf7-recaptcha-setup' ); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="sitekey"><?php
							echo esc_html( __( 'Site Key', 'contact-form-7' ) ); ?></label></th>
                    <td><?php
						if ( $this->is_active() ) {
							echo esc_html( $sitekey );
							echo sprintf(
								'<input type="hidden" value="%1$s" id="sitekey" name="sitekey" />',
								esc_attr( $sitekey )
							);
						} else {
							echo sprintf(
								'<input type="text" aria-required="true" value="%1$s" id="sitekey" name="sitekey" class="regular-text code" />',
								esc_attr( $sitekey )
							);
						}
						?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="secret"><?php
							echo esc_html( __( 'Secret Key', 'contact-form-7' ) ); ?></label></th>
                    <td><?php
						if ( $this->is_active() ) {
							echo esc_html( wpcf7_mask_password( $secret, 4, 4 ) );
							echo sprintf(
								'<input type="hidden" value="%1$s" id="secret" name="secret" />',
								esc_attr( $secret )
							);
						} else {
							echo sprintf(
								'<input type="text" aria-required="true" value="%1$s" id="secret" name="secret" class="regular-text code" />',
								esc_attr( $secret )
							);
						}
						?></td>
                </tr>
                </tbody>
            </table>
			<?php
			if ( $this->is_active() ) {
				if ( $this->get_global_sitekey() && $this->get_global_secret() ) {
					// nothing
				} else {
					submit_button(
						_x( 'Remove Keys', 'API keys', 'contact-form-7' ),
						'small', 'reset'
					);
				}
			} else {
				submit_button( __( 'Save Changes', 'contact-form-7' ) );
			}
			?>
        </form>
		<?php
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'recaptcha' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

}

