<?php
/**
 * Global design settings page (Settings API) + shortcode-builder helper.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings screen for the portfolio look & feel.
 */
class Ofnoa_Portfolio_Settings {

	const OPTION = 'ofnoa_portfolio_settings';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Seed default option on activation.
	 */
	public static function install_defaults() {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, ofnoa_portfolio_defaults() );
		}
	}

	/**
	 * Sub-menu under the CPT.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Ofnoa_Portfolio_CPT::POST_TYPE,
			'עיצוב תיק העבודות',
			'עיצוב ותצוגה',
			'manage_options',
			'ofnoa-portfolio-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register the setting.
	 */
	public static function register() {
		register_setting(
			'ofnoa_portfolio_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'ofnoa_portfolio_sanitize_settings',
				'default'           => ofnoa_portfolio_defaults(),
			)
		);
	}

	/**
	 * Assets for the settings screen.
	 *
	 * @param string $hook Admin hook.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'ofnoa-portfolio-settings' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'ofnoa-portfolio-admin', OFNOA_PORTFOLIO_URL . 'assets/css/ofnoa-admin.css', array(), OFNOA_PORTFOLIO_VERSION );
		wp_enqueue_script( 'ofnoa-portfolio-admin', OFNOA_PORTFOLIO_URL . 'assets/js/ofnoa-admin.js', array( 'jquery', 'wp-color-picker' ), OFNOA_PORTFOLIO_VERSION, true );
	}

	/**
	 * Radio-card group renderer.
	 *
	 * @param string $name    Field name.
	 * @param string $current Current value.
	 * @param array  $options value => label|desc pairs.
	 */
	private static function cards( $name, $current, $options ) {
		echo '<div class="ofnoa-cards">';
		foreach ( $options as $val => $meta ) {
			$id = 'ofnoa_' . $name . '_' . $val;
			printf(
				'<label class="ofnoa-card" for="%1$s"><input type="radio" id="%1$s" name="%2$s[%3$s]" value="%4$s" %5$s><span class="ofnoa-card-t">%6$s</span><span class="ofnoa-card-d">%7$s</span></label>',
				esc_attr( $id ),
				esc_attr( self::OPTION ),
				esc_attr( $name ),
				esc_attr( $val ),
				checked( $current, $val, false ),
				esc_html( $meta[0] ),
				esc_html( $meta[1] )
			);
		}
		echo '</div>';
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = ofnoa_portfolio_get_settings();
		?>
		<div class="wrap ofnoa-settings">
			<h1>עיצוב תיק העבודות — Ofnoa Portfolio</h1>
			<p class="ofnoa-lead">כל שינוי כאן חל על תצוגת התיק בכל האתר. שיבוץ בעמוד: הדבק את השורטקוד <code>[ofnoa_portfolio]</code> או השתמש בבלוק "Ofnoa Portfolio" בעורך Gutenberg.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'ofnoa_portfolio_group' ); ?>

				<div class="ofnoa-panel">
					<h2>כותרות הסקשן</h2>
					<label class="ofnoa-row"><span>כותרת ראשית</span>
						<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[heading]" value="<?php echo esc_attr( $s['heading'] ); ?>" class="regular-text" />
					</label>
					<label class="ofnoa-row"><span>כותרת משנה</span>
						<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[subheading]" value="<?php echo esc_attr( $s['subheading'] ); ?>" class="large-text" />
					</label>
				</div>

				<div class="ofnoa-panel">
					<h2>מצב התצוגה (ה-"וואו")</h2>
					<?php
					self::cards(
						'mode',
						$s['mode'],
						array(
							'device'    => array( 'מסגרות מכשירים', 'דפדפן ריאליסטי + נייד לידו — מראה שהאתר רספונסיבי' ),
							'hand'      => array( 'יד מחזיקה נייד', 'נייד תלת-ממדי ביד עם צילום המסך בפנים' ),
							'cinematic' => array( 'Cinematic פרלקסה', 'רוחב מלא, גרדיאנט, גלילה עם עומק' ),
							'tilt'      => array( 'כרטיסי 3D tilt', 'הטיה תלת-ממדית לפי העכבר, hover reveal' ),
						)
					);
					?>
				</div>

				<div class="ofnoa-panel">
					<h2>פריסה</h2>
					<?php
					self::cards(
						'layout',
						$s['layout'],
						array(
							'bento'    => array( 'Bento', 'רשת מודולרית — אריח דגל גדול + קטנים' ),
							'grid'     => array( 'Grid אחיד', 'כל הכרטיסים בגודל שווה' ),
							'masonry'  => array( 'Masonry', 'זרימה חופשית בגבהים משתנים' ),
							'carousel' => array( 'Carousel', 'גלילה אופקית עם snap' ),
						)
					);
					?>
					<div class="ofnoa-inline">
						<label>עמודות
							<select name="<?php echo esc_attr( self::OPTION ); ?>[columns]">
								<?php foreach ( array( '2', '3', '4' ) as $c ) : ?>
									<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $s['columns'], $c ); ?>><?php echo esc_html( $c ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>רוחב מקסימלי (px)
							<input type="number" min="600" max="1920" name="<?php echo esc_attr( self::OPTION ); ?>[max_width]" value="<?php echo esc_attr( $s['max_width'] ); ?>" />
						</label>
						<label>מרווח בין כרטיסים (px)
							<input type="number" min="0" max="80" name="<?php echo esc_attr( self::OPTION ); ?>[gap]" value="<?php echo esc_attr( $s['gap'] ); ?>" />
						</label>
						<label>עיגול פינות (px)
							<input type="number" min="0" max="48" name="<?php echo esc_attr( self::OPTION ); ?>[radius]" value="<?php echo esc_attr( $s['radius'] ); ?>" />
						</label>
					</div>
				</div>

				<div class="ofnoa-panel">
					<h2>צבעים ותֵמה</h2>
					<div class="ofnoa-inline">
						<label>מצב תֵמה
							<select name="<?php echo esc_attr( self::OPTION ); ?>[theme]">
								<option value="dark" <?php selected( $s['theme'], 'dark' ); ?>>כהה (מומלץ)</option>
								<option value="light" <?php selected( $s['theme'], 'light' ); ?>>בהיר</option>
								<option value="auto" <?php selected( $s['theme'], 'auto' ); ?>>אוטומטי (לפי המכשיר)</option>
							</select>
						</label>
						<label>צבע הדגשה
							<input type="text" class="ofnoa-color" name="<?php echo esc_attr( self::OPTION ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" />
						</label>
						<label>צבע הדגשה משני
							<input type="text" class="ofnoa-color" name="<?php echo esc_attr( self::OPTION ); ?>[accent2]" value="<?php echo esc_attr( $s['accent2'] ); ?>" />
						</label>
						<label>סגנון כרטיס
							<select name="<?php echo esc_attr( self::OPTION ); ?>[card_style]">
								<option value="glass" <?php selected( $s['card_style'], 'glass' ); ?>>זכוכית (Glass)</option>
								<option value="solid" <?php selected( $s['card_style'], 'solid' ); ?>>מלא</option>
								<option value="outline" <?php selected( $s['card_style'], 'outline' ); ?>>מתאר</option>
							</select>
						</label>
						<label>גופן
							<select name="<?php echo esc_attr( self::OPTION ); ?>[font]">
								<option value="heebo" <?php selected( $s['font'], 'heebo' ); ?>>Heebo</option>
								<option value="assistant" <?php selected( $s['font'], 'assistant' ); ?>>Assistant</option>
								<option value="rubik" <?php selected( $s['font'], 'rubik' ); ?>>Rubik</option>
								<option value="space" <?php selected( $s['font'], 'space' ); ?>>Space Grotesk + Heebo</option>
								<option value="system" <?php selected( $s['font'], 'system' ); ?>>גופן המערכת</option>
							</select>
						</label>
					</div>
				</div>

				<div class="ofnoa-panel">
					<h2>אפקטים ותנועה</h2>
					<div class="ofnoa-inline ofnoa-toggles">
						<label class="ofnoa-toggle"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[animations]" value="1" <?php checked( $s['animations'], '1' ); ?> /> <span>אנימציות כניסה בגלילה</span></label>
						<label class="ofnoa-toggle"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[tilt]" value="1" <?php checked( $s['tilt'], '1' ); ?> /> <span>הטיית 3D לפי עכבר</span></label>
						<label class="ofnoa-toggle"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_filters]" value="1" <?php checked( $s['show_filters'], '1' ); ?> /> <span>סרגל סינון לפי קטגוריה</span></label>
						<label class="ofnoa-toggle"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[sparkles]" value="1" <?php checked( $s['sparkles'], '1' ); ?> /> <span>שכבת נצנוצים / זיקוקים</span></label>
					</div>
					<label class="ofnoa-row"><span>סגנון הנצנוצים</span>
						<select name="<?php echo esc_attr( self::OPTION ); ?>[sparkles_style]">
							<option value="sparkles" <?php selected( $s['sparkles_style'], 'sparkles' ); ?>>נצנוצים עדינים (Sparkles)</option>
							<option value="fireworks" <?php selected( $s['sparkles_style'], 'fireworks' ); ?>>זיקוקים (Fireworks)</option>
							<option value="constellation" <?php selected( $s['sparkles_style'], 'constellation' ); ?>>קונסטלציה (רשת כוכבים)</option>
							<option value="aurora" <?php selected( $s['sparkles_style'], 'aurora' ); ?>>זוהר אורורה (Aurora glow)</option>
						</select>
					</label>
					<p class="description">כל האפקטים מכבדים אוטומטית את העדפת "צמצום תנועה" (prefers-reduced-motion) של המשתמש.</p>
				</div>

				<?php submit_button( 'שמור הגדרות' ); ?>
			</form>

			<div class="ofnoa-panel ofnoa-panel--ghost">
				<h2>שיבוץ מהיר</h2>
				<p>שורטקוד בסיסי: <code>[ofnoa_portfolio]</code></p>
				<p>דוגמאות עם עקיפת הגדרות:</p>
				<pre dir="ltr">[ofnoa_portfolio mode="hand" layout="grid" columns="2"]
[ofnoa_portfolio mode="cinematic" sparkles_style="fireworks" limit="3"]
[ofnoa_portfolio mode="tilt" theme="light" filters="1"]</pre>
			</div>
		</div>
		<?php
	}
}
Ofnoa_Portfolio_Settings::init();
