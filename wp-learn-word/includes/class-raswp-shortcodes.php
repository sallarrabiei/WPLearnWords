<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RASWP_Shortcodes {
	public static function init() {
		add_shortcode( 'raswp_leitner', array( __CLASS__, 'render_leitner' ) );
		add_shortcode( 'raswp_upgrade', array( __CLASS__, 'render_upgrade' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'raswp-leitner', RASWP_PLUGIN_URL . 'assets/css/leitner.css', array(), '1.0.0' );
		wp_register_script( 'raswp-leitner', RASWP_PLUGIN_URL . 'assets/js/leitner.js', array( 'jquery' ), '1.0.0', true );
	}

	private static function enqueue_runtime( $atts = array() ) {
		wp_enqueue_style( 'raswp-leitner' );
		wp_enqueue_script( 'raswp-leitner' );
		wp_localize_script( 'raswp-leitner', 'RASWP', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'raswp_ajax_nonce' ),
			'atts' => $atts,
			'i18n' => array(
				'show_answer' => __( 'Show Answer', 'wp-learn-word' ),
				'i_was_right' => __( 'I was Right', 'wp-learn-word' ),
				'i_was_wrong' => __( 'I was Wrong', 'wp-learn-word' ),
				'next' => __( 'Next', 'wp-learn-word' ),
				'upgrade_needed' => __( 'Free limit reached. Please upgrade to continue.', 'wp-learn-word' ),
				'login_needed' => __( 'Please log in to continue.', 'wp-learn-word' ),
			),
		) );
	}

	public static function render_leitner( $atts ) {
		$atts = shortcode_atts( array(
			'book' => '',
			'book_id' => 0,
		), $atts, 'raswp_leitner' );

		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<div class="raswp-message">' . esc_html__( 'Please log in to study.', 'wp-learn-word' ) . ' <a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login', 'wp-learn-word' ) . '</a></div>';
		}

		self::enqueue_runtime( $atts );

		ob_start();
		?>
		<div class="raswp-leitner" data-book="<?php echo esc_attr( $atts['book'] ); ?>" data-book-id="<?php echo esc_attr( (int) $atts['book_id'] ); ?>">
			<div class="raswp-card">
				<div class="raswp-word"></div>
				<div class="raswp-translation" style="display:none;"></div>
				<div class="raswp-example" style="display:none;"></div>
				<div class="raswp-actions">
					<button class="raswp-btn raswp-show"><?php echo esc_html__( 'Show Answer', 'wp-learn-word' ); ?></button>
					<button class="raswp-btn raswp-correct" style="display:none;"><?php echo esc_html__( 'I was Right', 'wp-learn-word' ); ?></button>
					<button class="raswp-btn raswp-wrong" style="display:none;"><?php echo esc_html__( 'I was Wrong', 'wp-learn-word' ); ?></button>
					<button class="raswp-btn raswp-next" style="display:none;"><?php echo esc_html__( 'Next', 'wp-learn-word' ); ?></button>
				</div>
				<div class="raswp-status"></div>
			</div>
			<div class="raswp-upgrade" style="display:none;">
				<?php echo do_shortcode( '[raswp_upgrade]' ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_upgrade( $atts ) {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<div class="raswp-upgrade-box">' . esc_html__( 'Please log in to upgrade.', 'wp-learn-word' ) . ' <a class="raswp-btn raswp-login" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login', 'wp-learn-word' ) . '</a></div>';
		}
		$user_id = get_current_user_id();
		if ( RASWP_Payment::user_has_premium( $user_id ) ) {
			return '<div class="raswp-upgrade-box">' . esc_html__( 'You have full access. Thank you!', 'wp-learn-word' ) . '</div>';
		}
		$amount = (int) get_option( 'raswp_zarinpal_amount', 200000 );
		$redirect = esc_url( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$nonce = wp_create_nonce( 'raswp_start_payment' );
		$action = esc_url( admin_url( 'admin-post.php' ) );
		$btn = '<form method="post" action="' . $action . '"><input type="hidden" name="action" value="raswp_start_payment" /><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" /><input type="hidden" name="_raswp_redirect" value="' . esc_attr( $redirect ) . '" /><button class="raswp-btn raswp-upgrade-btn" type="submit">' . sprintf( esc_html__( 'Upgrade Now (%s IRR)', 'wp-learn-word' ), number_format_i18n( $amount ) ) . '</button></form>';
		return '<div class="raswp-upgrade-box">' . $btn . '</div>';
	}
}