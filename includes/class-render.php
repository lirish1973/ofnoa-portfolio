<?php
/**
 * The rendering engine: turns settings + a query into the portfolio markup.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds portfolio HTML and enqueues its assets.
 */
class Ofnoa_Portfolio_Render {

	/**
	 * Per-request counter for unique instance ids.
	 *
	 * @var int
	 */
	private static $counter = 0;

	/**
	 * Whether front-end assets were registered.
	 *
	 * @var bool
	 */
	private static $registered = false;

	/**
	 * Register (not enqueue) the front-end assets.
	 */
	public static function register_assets() {
		if ( self::$registered ) {
			return;
		}
		wp_register_style( 'ofnoa-portfolio', OFNOA_PORTFOLIO_URL . 'assets/css/ofnoa-portfolio.css', array(), OFNOA_PORTFOLIO_VERSION );
		wp_register_script( 'ofnoa-portfolio', OFNOA_PORTFOLIO_URL . 'assets/js/ofnoa-portfolio.js', array(), OFNOA_PORTFOLIO_VERSION, true );
		self::$registered = true;
	}

	/**
	 * Merge caller args over saved settings and normalise types.
	 *
	 * @param array $args Raw overrides.
	 * @return array
	 */
	public static function prepare_args( $args ) {
		$s   = ofnoa_portfolio_get_settings();
		$a   = wp_parse_args( is_array( $args ) ? $args : array(), $s );
		$out = array();

		$out['heading']        = sanitize_text_field( $a['heading'] );
		$out['subheading']     = sanitize_text_field( $a['subheading'] );
		$out['theme']          = ofnoa_portfolio_enum( $a['theme'], array( 'dark', 'light', 'auto' ), $s['theme'] );
		$out['accent']         = ofnoa_portfolio_sanitize_color( $a['accent'], $s['accent'] );
		$out['accent2']        = ofnoa_portfolio_sanitize_color( $a['accent2'], $s['accent2'] );
		$out['layout']         = ofnoa_portfolio_enum( $a['layout'], array( 'bento', 'grid', 'masonry', 'carousel' ), $s['layout'] );
		$out['mode']           = ofnoa_portfolio_enum( $a['mode'], array( 'device', 'hand', 'cinematic', 'tilt' ), $s['mode'] );
		$out['columns']        = ofnoa_portfolio_enum( (string) $a['columns'], array( '2', '3', '4' ), $s['columns'] );
		$out['gap']            = (string) max( 0, min( 80, intval( $a['gap'] ) ) );
		$out['radius']         = (string) max( 0, min( 48, intval( $a['radius'] ) ) );
		$out['max_width']      = (string) max( 600, min( 1920, intval( $a['max_width'] ) ) );
		$out['card_style']     = ofnoa_portfolio_enum( $a['card_style'], array( 'glass', 'solid', 'outline' ), $s['card_style'] );
		$out['font']           = ofnoa_portfolio_enum( $a['font'], array( 'heebo', 'assistant', 'rubik', 'system', 'space' ), $s['font'] );
		$out['sparkles_style'] = ofnoa_portfolio_enum( $a['sparkles_style'], array( 'sparkles', 'fireworks', 'constellation', 'aurora' ), $s['sparkles_style'] );
		$out['section_bg']     = isset( $a['section_bg'] ) ? ofnoa_portfolio_sanitize_color( $a['section_bg'], '' ) : '';

		// Booleans may arrive as "1"/"0"/"true"/"yes".
		$bool = static function ( $v, $fallback ) {
			if ( null === $v ) {
				return $fallback;
			}
			$v = strtolower( (string) $v );
			if ( in_array( $v, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return '1';
			}
			if ( in_array( $v, array( '0', 'false', 'no', 'off', '' ), true ) ) {
				return '0';
			}
			return $fallback;
		};
		$out['sparkles']     = $bool( $a['sparkles'] ?? null, $s['sparkles'] );
		$out['animations']   = $bool( $a['animations'] ?? null, $s['animations'] );
		$out['tilt']         = $bool( $a['tilt'] ?? null, $s['tilt'] );
		$out['show_filters'] = $bool( isset( $a['filters'] ) ? $a['filters'] : ( $a['show_filters'] ?? null ), $s['show_filters'] );

		// Query controls.
		$out['limit']    = isset( $a['limit'] ) ? intval( $a['limit'] ) : -1;
		$out['category'] = isset( $a['category'] ) ? sanitize_title( $a['category'] ) : '';
		$out['ids']      = isset( $a['ids'] ) ? array_filter( array_map( 'absint', explode( ',', (string) $a['ids'] ) ) ) : array();
		$out['orderby']  = ofnoa_portfolio_enum( $a['orderby'] ?? '', array( 'menu_order', 'date', 'title', 'rand' ), 'menu_order' );

		return $out;
	}

	/**
	 * Build a WP_Query for the projects.
	 *
	 * @param array $a Prepared args.
	 * @return WP_Query
	 */
	private static function query( $a ) {
		$args = array(
			'post_type'           => Ofnoa_Portfolio_CPT::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $a['limit'] > 0 ? $a['limit'] : -1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( 'menu_order' === $a['orderby'] ) {
			$args['orderby'] = array( 'menu_order' => 'ASC', 'date' => 'DESC' );
		} else {
			$args['orderby'] = $a['orderby'];
			$args['order']   = 'title' === $a['orderby'] ? 'ASC' : 'DESC';
		}

		if ( $a['ids'] ) {
			$args['post__in'] = $a['ids'];
			$args['orderby']  = 'post__in';
		}
		if ( $a['category'] ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => Ofnoa_Portfolio_CPT::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $a['category'],
				),
			);
		}
		return new WP_Query( $args );
	}

	/**
	 * Main entry: return the section HTML.
	 *
	 * @param array $args Overrides.
	 * @return string
	 */
	public static function render( $args = array() ) {
		self::register_assets();
		wp_enqueue_style( 'ofnoa-portfolio' );
		wp_enqueue_script( 'ofnoa-portfolio' );

		$a = self::prepare_args( $args );

		// Load fonts if needed.
		$font = ofnoa_portfolio_font_map( $a['font'] );
		if ( $font['google'] ) {
			$handle = 'ofnoa-font-' . $a['font'];
			if ( ! wp_style_is( $handle, 'enqueued' ) ) {
				wp_enqueue_style( $handle, 'https://fonts.googleapis.com/css2?family=' . $font['google'] . '&display=swap', array(), null ); // phpcs:ignore
			}
		}

		$query = self::query( $a );
		self::$counter++;
		$uid = 'ofnoa-pf-' . self::$counter;

		if ( ! $query->have_posts() ) {
			return '<div class="ofnoa-empty">עדיין לא נוספו פרויקטים לתיק העבודות.</div>';
		}

		$style_vars = sprintf(
			'--ofnoa-accent:%s;--ofnoa-accent2:%s;--ofnoa-gap:%dpx;--ofnoa-radius:%dpx;--ofnoa-maxw:%dpx;--ofnoa-cols:%d;%s',
			esc_attr( $a['accent'] ),
			esc_attr( $a['accent2'] ),
			intval( $a['gap'] ),
			intval( $a['radius'] ),
			intval( $a['max_width'] ),
			intval( $a['columns'] ),
			$a['section_bg'] ? '--ofnoa-bg:' . esc_attr( $a['section_bg'] ) . ';' : ''
		);

		$classes = array(
			'ofnoa-portfolio',
			'ofnoa-theme-' . $a['theme'],
			'ofnoa-layout-' . $a['layout'],
			'ofnoa-mode-' . $a['mode'],
			'ofnoa-card-' . $a['card_style'],
			'ofnoa-font-' . $a['font'],
		);
		if ( '1' === $a['animations'] ) {
			$classes[] = 'ofnoa-anim';
		}
		if ( '1' === $a['tilt'] ) {
			$classes[] = 'ofnoa-has-tilt';
		}

		$config = array(
			'tilt'      => '1' === $a['tilt'],
			'animate'   => '1' === $a['animations'],
			'sparkles'  => '1' === $a['sparkles'],
			'sparkStyle'=> $a['sparkles_style'],
			'accent'    => $a['accent'],
			'accent2'   => $a['accent2'],
		);

		ob_start();
		?>
		<section id="<?php echo esc_attr( $uid ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			style="<?php echo esc_attr( $style_vars ); ?>"
			data-ofnoa-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
			dir="rtl">

			<?php if ( '1' === $a['sparkles'] ) : ?>
				<canvas class="ofnoa-fx" data-style="<?php echo esc_attr( $a['sparkles_style'] ); ?>" aria-hidden="true"></canvas>
			<?php endif; ?>

			<div class="ofnoa-inner">
				<?php if ( $a['heading'] || $a['subheading'] ) : ?>
					<header class="ofnoa-head">
						<?php if ( $a['heading'] ) : ?>
							<h2 class="ofnoa-title"><?php echo esc_html( $a['heading'] ); ?></h2>
						<?php endif; ?>
						<?php if ( $a['subheading'] ) : ?>
							<p class="ofnoa-sub"><?php echo esc_html( $a['subheading'] ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<?php
				if ( '1' === $a['show_filters'] ) {
					echo self::render_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaping inside.
				}
				?>

				<div class="ofnoa-grid" role="list">
					<?php
					$i = 0;
					while ( $query->have_posts() ) {
						$query->the_post();
						echo self::render_card( get_post(), $a, $i, $uid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
						$i++;
					}
					wp_reset_postdata();
					?>
				</div>
			</div>

			<div class="ofnoa-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
				<div class="ofnoa-modal-backdrop" data-close></div>
				<div class="ofnoa-modal-panel" role="document">
					<button type="button" class="ofnoa-modal-close" data-close aria-label="סגור"><?php echo ofnoa_portfolio_icon( 'close' ); // phpcs:ignore ?></button>
					<div class="ofnoa-modal-body"></div>
				</div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Category filter bar.
	 *
	 * @return string
	 */
	private static function render_filters() {
		$terms = get_terms(
			array(
				'taxonomy'   => Ofnoa_Portfolio_CPT::TAXONOMY,
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $terms ) || count( $terms ) < 1 ) {
			return '';
		}
		ob_start();
		?>
		<div class="ofnoa-filters" role="tablist" aria-label="סינון פרויקטים">
			<button type="button" class="ofnoa-filter is-active" data-filter="*" role="tab" aria-selected="true">הכל</button>
			<?php foreach ( $terms as $term ) : ?>
				<button type="button" class="ofnoa-filter" data-filter="<?php echo esc_attr( $term->slug ); ?>" role="tab" aria-selected="false"><?php echo esc_html( $term->name ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render one project card.
	 *
	 * @param WP_Post $post  Project.
	 * @param array   $a     Prepared args.
	 * @param int     $index Position.
	 * @param string  $uid   Per-instance unique id prefix.
	 * @return string
	 */
	private static function render_card( $post, $a, $index, $uid = 'ofnoa' ) {
		$id       = $post->ID;
		$url      = get_post_meta( $id, '_ofnoa_url', true );
		$target   = get_post_meta( $id, '_ofnoa_target', true ) ? get_post_meta( $id, '_ofnoa_target', true ) : '_blank';
		$client   = get_post_meta( $id, '_ofnoa_client', true );
		$year     = get_post_meta( $id, '_ofnoa_year', true );
		$accent   = get_post_meta( $id, '_ofnoa_accent', true );
		$tech     = get_post_meta( $id, '_ofnoa_tech', true );
		$featured = '1' === get_post_meta( $id, '_ofnoa_featured', true );
		$card_mode= get_post_meta( $id, '_ofnoa_mode', true );
		$mode     = $card_mode ? $card_mode : $a['mode'];

		$title    = get_the_title( $post );
		$excerpt  = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );

		$terms      = get_the_terms( $id, Ofnoa_Portfolio_CPT::TAXONOMY );
		$term_slugs = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'slug' ) : array();

		$rel        = ( '_blank' === $target ) ? ' rel="noopener noreferrer"' : '';
		$card_style = $accent ? ' style="--ofnoa-card-accent:' . esc_attr( $accent ) . '"' : '';
		$card_class = 'ofnoa-card ofnoa-card--' . esc_attr( $mode );
		if ( $featured ) {
			$card_class .= ' is-featured';
		}

		$delay = min( $index, 8 ) * 70;

		ob_start();
		?>
		<article class="<?php echo esc_attr( $card_class ); ?>" role="listitem"
			data-stack="<?php echo esc_attr( implode( ' ', $term_slugs ) ); ?>"
			data-delay="<?php echo esc_attr( $delay ); ?>"<?php echo $card_style; // phpcs:ignore ?>>

			<div class="ofnoa-card-visual">
				<?php echo self::render_media( $mode, $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
			</div>

			<div class="ofnoa-card-body">
				<?php if ( $client || $year ) : ?>
					<div class="ofnoa-card-meta">
						<?php if ( $client ) : ?><span class="ofnoa-card-client"><?php echo esc_html( $client ); ?></span><?php endif; ?>
						<?php if ( $year ) : ?><span class="ofnoa-card-year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
					</div>
				<?php endif; ?>

				<h3 class="ofnoa-card-title"><?php echo esc_html( $title ); ?></h3>

				<?php if ( $excerpt ) : ?>
					<p class="ofnoa-card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>

				<?php if ( $tech ) : ?>
					<ul class="ofnoa-chips">
						<?php foreach ( array_slice( array_filter( array_map( 'trim', explode( ',', $tech ) ) ), 0, 6 ) as $chip ) : ?>
							<li class="ofnoa-chip"><?php echo esc_html( $chip ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="ofnoa-card-actions">
					<?php if ( $url ) : ?>
						<a class="ofnoa-btn ofnoa-btn--primary" href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo $rel; // phpcs:ignore ?>>
							<span>צפייה באתר</span><?php echo ofnoa_portfolio_icon( 'external' ); // phpcs:ignore ?>
						</a>
					<?php endif; ?>
					<button type="button" class="ofnoa-btn ofnoa-btn--ghost ofnoa-open-detail" data-detail="<?php echo esc_attr( $uid . '-detail-' . $id ); ?>">
						<span>פרטים</span><?php echo ofnoa_portfolio_icon( 'arrow' ); // phpcs:ignore ?>
					</button>
				</div>
			</div>

			<?php echo self::render_detail_template( $post, $mode, $url, $target, $client, $year, $tech, $uid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The device/hand/cinematic/tilt visual for a project.
	 *
	 * @param string  $mode Display mode.
	 * @param WP_Post $post Project.
	 * @return string
	 */
	private static function render_media( $mode, $post ) {
		$id      = $post->ID;
		$desktop = get_post_meta( $id, '_ofnoa_shot_desktop', true );
		$mobile  = get_post_meta( $id, '_ofnoa_shot_mobile', true );
		$url     = get_post_meta( $id, '_ofnoa_url', true );
		$client  = get_post_meta( $id, '_ofnoa_client', true );
		$alt     = $client ? $client : get_the_title( $post );

		// Fallback: featured image if no explicit desktop screenshot.
		if ( ! $desktop && has_post_thumbnail( $id ) ) {
			$desktop = get_the_post_thumbnail_url( $id, 'large' );
		}

		$host = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
		$host = $host ? preg_replace( '/^www\./', '', $host ) : 'preview';

		$img = static function ( $src, $alt, $class ) {
			if ( ! $src ) {
				return '';
			}
			return sprintf(
				'<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async" />',
				esc_attr( $class ),
				esc_url( $src ),
				esc_attr( $alt )
			);
		};

		ob_start();

		if ( 'cinematic' === $mode ) {
			?>
			<div class="ofnoa-cine">
				<div class="ofnoa-cine-media" data-parallax="0.12">
					<?php echo $img( $desktop, $alt, 'ofnoa-cine-img' ); // phpcs:ignore ?>
				</div>
				<div class="ofnoa-cine-glow" aria-hidden="true"></div>
			</div>
			<?php
		} elseif ( 'tilt' === $mode ) {
			?>
			<div class="ofnoa-tiltcard" data-tilt>
				<div class="ofnoa-tilt-shine" aria-hidden="true"></div>
				<div class="ofnoa-tilt-layer ofnoa-tilt-back" data-depth="6">
					<?php echo $img( $desktop, $alt, 'ofnoa-tilt-img' ); // phpcs:ignore ?>
				</div>
				<?php if ( $mobile ) : ?>
					<div class="ofnoa-tilt-layer ofnoa-tilt-front" data-depth="22">
						<span class="ofnoa-mini-phone"><?php echo $img( $mobile, $alt, 'ofnoa-mini-phone-img' ); // phpcs:ignore ?></span>
					</div>
				<?php endif; ?>
			</div>
			<?php
		} elseif ( 'hand' === $mode ) {
			?>
			<div class="ofnoa-hand" data-tilt>
				<?php echo self::hand_svg(); // phpcs:ignore ?>
				<div class="ofnoa-hand-phone">
					<span class="ofnoa-hand-notch" aria-hidden="true"></span>
					<?php echo $img( $mobile ? $mobile : $desktop, $alt, 'ofnoa-hand-img' ); // phpcs:ignore ?>
					<span class="ofnoa-hand-glare" aria-hidden="true"></span>
				</div>
			</div>
			<?php
		} else { // device (default).
			?>
			<div class="ofnoa-devices" data-tilt>
				<div class="ofnoa-browser">
					<div class="ofnoa-browser-bar" aria-hidden="true">
						<span class="ofnoa-dot ofnoa-dot--r"></span>
						<span class="ofnoa-dot ofnoa-dot--y"></span>
						<span class="ofnoa-dot ofnoa-dot--g"></span>
						<span class="ofnoa-browser-url"><?php echo ofnoa_portfolio_icon( 'globe' ); // phpcs:ignore ?><em><?php echo esc_html( $host ); ?></em></span>
					</div>
					<div class="ofnoa-browser-screen">
						<?php echo $img( $desktop, $alt, 'ofnoa-browser-img' ); // phpcs:ignore ?>
					</div>
				</div>
				<?php if ( $mobile ) : ?>
					<div class="ofnoa-phone" aria-hidden="false">
						<span class="ofnoa-phone-notch"></span>
						<div class="ofnoa-phone-screen">
							<?php echo $img( $mobile, $alt, 'ofnoa-phone-img' ); // phpcs:ignore ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		return (string) ob_get_clean();
	}

	/**
	 * Hidden rich content used by the modal.
	 *
	 * @param WP_Post $post   Project.
	 * @param string  $mode   Mode.
	 * @param string  $url    Site URL.
	 * @param string  $target Link target.
	 * @param string  $client Client.
	 * @param string  $year   Year.
	 * @param string  $tech   Tech list.
	 * @param string  $uid    Per-instance unique id prefix.
	 * @return string
	 */
	private static function render_detail_template( $post, $mode, $url, $target, $client, $year, $tech, $uid = 'ofnoa' ) {
		$id        = $post->ID;
		$challenge = get_post_meta( $id, '_ofnoa_challenge', true );
		$content   = apply_filters( 'the_content', $post->post_content );
		$rel       = ( '_blank' === $target ) ? ' rel="noopener noreferrer"' : '';

		ob_start();
		?>
		<template id="<?php echo esc_attr( $uid . '-detail-' . $id ); ?>">
			<div class="ofnoa-detail">
				<div class="ofnoa-detail-visual"><?php echo self::render_media( 'device', $post ); // phpcs:ignore ?></div>
				<div class="ofnoa-detail-text">
					<?php if ( $client ) : ?><span class="ofnoa-detail-client"><?php echo esc_html( $client ); ?><?php echo $year ? ' · ' . esc_html( $year ) : ''; ?></span><?php endif; ?>
					<h3 class="ofnoa-detail-title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
					<?php if ( $content ) : ?><div class="ofnoa-detail-desc"><?php echo wp_kses_post( $content ); ?></div><?php endif; ?>
					<?php if ( $challenge ) : ?>
						<div class="ofnoa-detail-challenge">
							<h4><?php echo ofnoa_portfolio_icon( 'bolt' ); // phpcs:ignore ?> אתגרים ופתרונות</h4>
							<div><?php echo wp_kses_post( wpautop( $challenge ) ); ?></div>
						</div>
					<?php endif; ?>
					<?php if ( $tech ) : ?>
						<ul class="ofnoa-chips ofnoa-chips--lg">
							<?php foreach ( array_filter( array_map( 'trim', explode( ',', $tech ) ) ) as $chip ) : ?>
								<li class="ofnoa-chip"><?php echo esc_html( $chip ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $url ) : ?>
						<a class="ofnoa-btn ofnoa-btn--primary ofnoa-btn--lg" href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo $rel; // phpcs:ignore ?>>
							<span>כניסה לאתר החי</span><?php echo ofnoa_portfolio_icon( 'external' ); // phpcs:ignore ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</template>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A tasteful vector hand gripping a phone (behind the CSS phone).
	 *
	 * @return string
	 */
	private static function hand_svg() {
		return '<svg class="ofnoa-hand-svg" viewBox="0 0 260 360" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
			<defs>
				<linearGradient id="ofnoaSkin" x1="0" y1="0" x2="1" y2="1">
					<stop offset="0" stop-color="#F4C7A8"/><stop offset="0.55" stop-color="#E7A981"/><stop offset="1" stop-color="#C97F58"/>
				</linearGradient>
			</defs>
			<g>
				<path fill="url(#ofnoaSkin)" d="M40 360c-6-58-10-104 4-150 8-27 26-44 52-49 6-32 12-58 20-86 5-16 30-13 30 5-1 22-4 42-7 63 10-3 20-6 31-6 7-24 14-45 24-66 8-15 31-6 27 11-5 24-11 45-18 67 9 2 17 6 24 12 9-18 18-33 30-47 11-13 32 1 24 16-11 20-22 37-33 55 12 12 18 28 18 49v126H40Z"/>
				<path fill="#00000022" d="M96 161c24-6 47-6 74 3 20 7 33 20 39 41-16-16-36-26-60-30-20-3-38-3-53-2 0-4 0-8 0-12Z"/>
			</g>
		</svg>';
	}
}
