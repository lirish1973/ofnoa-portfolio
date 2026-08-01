<?php
/**
 * Project meta boxes: URL, screenshots, client, year, accent, tech, challenges, flags.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and persists the per-project fields.
 */
class Ofnoa_Portfolio_Metaboxes {

	/**
	 * Meta keys and their sanitizer.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'_ofnoa_url'          => 'esc_url_raw',
			'_ofnoa_target'       => 'target',
			'_ofnoa_shot_desktop' => 'esc_url_raw',
			'_ofnoa_shot_mobile'  => 'esc_url_raw',
			'_ofnoa_client'       => 'sanitize_text_field',
			'_ofnoa_year'         => 'sanitize_text_field',
			'_ofnoa_accent'       => 'color',
			'_ofnoa_tech'         => 'sanitize_text_field',
			'_ofnoa_challenge'    => 'wp_kses_post',
			'_ofnoa_featured'     => 'bool',
			'_ofnoa_mode'         => 'mode',
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post_' . Ofnoa_Portfolio_CPT::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Register the meta box.
	 */
	public static function add() {
		add_meta_box(
			'ofnoa_project_details',
			'פרטי הפרויקט — תיק עבודות',
			array( __CLASS__, 'render' ),
			Ofnoa_Portfolio_CPT::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue the media uploader on the editor screen.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || Ofnoa_Portfolio_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'ofnoa-portfolio-admin', OFNOA_PORTFOLIO_URL . 'assets/css/ofnoa-admin.css', array(), OFNOA_PORTFOLIO_VERSION );
		wp_enqueue_script( 'ofnoa-portfolio-admin', OFNOA_PORTFOLIO_URL . 'assets/js/ofnoa-admin.js', array( 'jquery' ), OFNOA_PORTFOLIO_VERSION, true );
	}

	/**
	 * Render the meta box UI.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'ofnoa_project_save', 'ofnoa_project_nonce' );
		$get = function ( $key ) use ( $post ) {
			return get_post_meta( $post->ID, $key, true );
		};
		$desktop = $get( '_ofnoa_shot_desktop' );
		$mobile  = $get( '_ofnoa_shot_mobile' );
		$target  = $get( '_ofnoa_target' ) ? $get( '_ofnoa_target' ) : '_blank';
		$accent  = $get( '_ofnoa_accent' ) ? $get( '_ofnoa_accent' ) : '';
		$mode    = $get( '_ofnoa_mode' );
		?>
		<div class="ofnoa-mb">
			<p class="ofnoa-mb-hint">כל שדה כאן מזין את תצוגת התיק. את התיאור הכללי (כמה מילים על העבודה) כתוב בעורך התוכן הראשי למעלה.</p>

			<div class="ofnoa-mb-grid">
				<label class="ofnoa-field ofnoa-field--order">
					<span>סדר תצוגה בתיק</span>
					<input type="number" name="ofnoa_menu_order" step="1" min="0" value="<?php echo esc_attr( (string) $post->menu_order ); ?>" placeholder="1" />
					<small class="ofnoa-field-note">מספר קטן = מוקדם יותר. 1 מוצג ראשון, 2 שני, וכן הלאה. אפשר גם לגרור פרויקטים ברשימת "כל הפרויקטים".</small>
				</label>

				<label class="ofnoa-field">
					<span>קישור לאתר החי</span>
					<input type="url" name="_ofnoa_url" value="<?php echo esc_attr( $get( '_ofnoa_url' ) ); ?>" placeholder="https://example.co.il" />
				</label>

				<label class="ofnoa-field">
					<span>פתיחת הקישור</span>
					<select name="_ofnoa_target">
						<option value="_blank" <?php selected( $target, '_blank' ); ?>>חלון / טאב חדש</option>
						<option value="_self" <?php selected( $target, '_self' ); ?>>באותו חלון</option>
					</select>
				</label>

				<label class="ofnoa-field">
					<span>לקוח / שם המותג</span>
					<input type="text" name="_ofnoa_client" value="<?php echo esc_attr( $get( '_ofnoa_client' ) ); ?>" placeholder="לדוגמה: HOCO Israel" />
				</label>

				<label class="ofnoa-field">
					<span>שנה</span>
					<input type="text" name="_ofnoa_year" value="<?php echo esc_attr( $get( '_ofnoa_year' ) ); ?>" placeholder="2026" />
				</label>

				<label class="ofnoa-field">
					<span>טכנולוגיות (מופרד בפסיקים)</span>
					<input type="text" name="_ofnoa_tech" value="<?php echo esc_attr( $get( '_ofnoa_tech' ) ); ?>" placeholder="WordPress, WooCommerce, Elementor" />
				</label>

				<label class="ofnoa-field">
					<span>צבע הדגשה לכרטיס (אופציונלי)</span>
					<input type="text" class="ofnoa-color" name="_ofnoa_accent" value="<?php echo esc_attr( $accent ); ?>" placeholder="#40E0FF" />
				</label>

				<label class="ofnoa-field">
					<span>מצב תצוגה לכרטיס זה (אופציונלי — עוקף את ברירת המחדל)</span>
					<select name="_ofnoa_mode">
						<option value="" <?php selected( $mode, '' ); ?>>— ברירת מחדל של הבלוק —</option>
						<option value="device" <?php selected( $mode, 'device' ); ?>>מסגרות מכשירים</option>
						<option value="hand" <?php selected( $mode, 'hand' ); ?>>יד מחזיקה נייד</option>
						<option value="cinematic" <?php selected( $mode, 'cinematic' ); ?>>Cinematic פרלקסה</option>
						<option value="tilt" <?php selected( $mode, 'tilt' ); ?>>כרטיס 3D tilt</option>
					</select>
				</label>

				<label class="ofnoa-field ofnoa-field--check">
					<input type="checkbox" name="_ofnoa_featured" value="1" <?php checked( $get( '_ofnoa_featured' ), '1' ); ?> />
					<span>פרויקט דגל (אריח גדול ב-Bento)</span>
				</label>
			</div>

			<div class="ofnoa-mb-media">
				<div class="ofnoa-media-field">
					<span class="ofnoa-media-label">צילום מסך — דסקטופ</span>
					<div class="ofnoa-media-preview<?php echo $desktop ? ' has-img' : ''; ?>">
						<?php if ( $desktop ) : ?><img src="<?php echo esc_url( $desktop ); ?>" alt="" /><?php endif; ?>
					</div>
					<input type="hidden" name="_ofnoa_shot_desktop" value="<?php echo esc_attr( $desktop ); ?>" />
					<button type="button" class="button ofnoa-media-pick" data-target="_ofnoa_shot_desktop">בחר תמונה</button>
					<button type="button" class="button-link ofnoa-media-clear" data-target="_ofnoa_shot_desktop">הסר</button>
				</div>

				<div class="ofnoa-media-field">
					<span class="ofnoa-media-label">צילום מסך — מובייל (אופציונלי)</span>
					<div class="ofnoa-media-preview ofnoa-media-preview--mobile<?php echo $mobile ? ' has-img' : ''; ?>">
						<?php if ( $mobile ) : ?><img src="<?php echo esc_url( $mobile ); ?>" alt="" /><?php endif; ?>
					</div>
					<input type="hidden" name="_ofnoa_shot_mobile" value="<?php echo esc_attr( $mobile ); ?>" />
					<button type="button" class="button ofnoa-media-pick" data-target="_ofnoa_shot_mobile">בחר תמונה</button>
					<button type="button" class="button-link ofnoa-media-clear" data-target="_ofnoa_shot_mobile">הסר</button>
				</div>
			</div>

			<label class="ofnoa-field ofnoa-field--full">
				<span>האתגרים בעבודה (מוצג בחלון הפרטים)</span>
				<textarea name="_ofnoa_challenge" rows="3" placeholder="מה היה מאתגר? איך פתרת? אילו תוצאות הושגו?"><?php echo esc_textarea( $get( '_ofnoa_challenge' ) ); ?></textarea>
			</label>
		</div>
		<?php
	}

	/**
	 * Persist the fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['ofnoa_project_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ofnoa_project_nonce'] ) ), 'ofnoa_project_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::fields() as $key => $type ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';

			switch ( $type ) {
				case 'esc_url_raw':
					$value = esc_url_raw( $raw );
					break;
				case 'color':
					$value = $raw ? ofnoa_portfolio_sanitize_color( $raw, '' ) : '';
					break;
				case 'target':
					$value = ( '_self' === $raw ) ? '_self' : '_blank';
					break;
				case 'mode':
					$value = ofnoa_portfolio_enum( $raw, array( 'device', 'hand', 'cinematic', 'tilt' ), '' );
					break;
				case 'bool':
					$value = $raw ? '1' : '0';
					break;
				case 'wp_kses_post':
					$value = wp_kses_post( $raw );
					break;
				case 'sanitize_text_field':
				default:
					$value = sanitize_text_field( $raw );
					break;
			}

			if ( '' === $value && 'bool' !== $type ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Explicit display order → the post's menu_order (front query sorts by it).
		// Written directly to avoid re-triggering save_post.
		if ( isset( $_POST['ofnoa_menu_order'] ) ) {
			global $wpdb;
			$order = max( 0, intval( wp_unslash( $_POST['ofnoa_menu_order'] ) ) );
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $post_id ) ); // phpcs:ignore WordPress.DB
			clean_post_cache( $post_id );
		}
	}
}
Ofnoa_Portfolio_Metaboxes::init();
