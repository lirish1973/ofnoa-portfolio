<?php
/**
 * One-time seeding of Liraz's real projects on activation.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates demo projects using the bundled real screenshots.
 */
class Ofnoa_Portfolio_Seeder {

	const FLAG = 'ofnoa_portfolio_seeded';

	/**
	 * Seed once.
	 */
	public static function maybe_seed() {
		if ( get_option( self::FLAG ) ) {
			return;
		}

		// Don't seed if the user already created projects.
		$existing = get_posts(
			array(
				'post_type'      => Ofnoa_Portfolio_CPT::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $existing ) ) {
			update_option( self::FLAG, 1 );
			return;
		}

		$img = OFNOA_PORTFOLIO_URL . 'assets/img/';

		$projects = array(
			array(
				'title'     => 'HOCO Israel — יבואן רשמי',
				'content'   => 'חנות איקומרס מלאה ליבואן הרשמי של HOCO בישראל: 148+ מוצרים, מנוע חיפוש חכם, סנכרון מלאי אוטומטי בין מערכות (HMAC-SHA256) וצ׳אטבוט AI מבוסס RAG שעונה ללקוחות בזמן אמת. עומס גבוה מטופל דרך Cloudflare.',
				'url'       => 'https://hoco-israel.co.il/',
				'client'    => 'HOCO Israel',
				'year'      => '2026',
				'accent'    => '#2F6FED',
				'tech'      => 'WordPress, WooCommerce, Elementor, Woodmart, AI Chatbot, Cloudflare',
				'challenge' => 'האתגר: קטלוג של מאות מוצרים עם סנכרון מלאי חי ותמיכה אוטומטית. הפתרון: מנגנון סנכרון מאובטח ב-HMAC, צ׳אטבוט RAG (GPT-4o-mini + embeddings) וקאשינג אגרסיבי דרך Cloudflare לשמירה על מהירות טעינה.',
				'featured'  => '1',
				'stack'     => 'חנות איקומרס',
				'desktop'   => $img . 'hoco-desktop.webp',
				'mobile'    => $img . 'hoco-mobile.webp',
				'order'     => 1,
			),
			array(
				'title'     => 'TryIt — טיולים וחוויות בעולם',
				'content'   => 'אתר תדמית והמרות לחברת טיולים ותיקה: עיצוב קולנועי מלא-רוחב, טפסי לידים מותאמים אישית, הטמעת מפות מסלול (Google Maps) ודפי יעד עשירים. חוויית גלישה חלקה גם בעומסי קמפיין.',
				'url'       => 'https://tryit.co.il/',
				'client'    => 'TryIt Travel',
				'year'      => '2026',
				'accent'    => '#F39200',
				'tech'      => 'WordPress, WooCommerce, טפסים מותאמים, Google Maps, Cloudflare',
				'challenge' => 'האתגר: להפוך גולשים לפניות איכותיות עבור עשרות יעדים. הפתרון: טפסי לידים חכמים לכל מסלול, אינטגרציית CRM, ומבנה דפי יעד אחיד שקל לתחזק ולשכפל.',
				'featured'  => '0',
				'stack'     => 'תיירות',
				'desktop'   => $img . 'tryit-desktop.webp',
				'mobile'    => $img . 'tryit-mobile.webp',
				'order'     => 2,
			),
			array(
				'title'     => 'Sunrise by Marina — תכשיטים בעבודת יד',
				'content'   => 'חנות מותג בינלאומית לתכשיטים בעבודת יד: אסתטיקה נקייה ורגישה, טיפוגרפיה גדולה ומזמינה, ומסע קנייה חלק שמעמיד את המוצר במרכז. מותאם לקהל דובר אנגלית.',
				'url'       => 'https://sunrisebymarina.com/',
				'client'    => 'Sunrise by Marina',
				'year'      => '2026',
				'accent'    => '#E6A08A',
				'tech'      => 'WordPress, WooCommerce',
				'challenge' => 'האתגר: לשדר יוקרה ורגש סביב מוצר קטן. הפתרון: היררכיה ויזואלית מוקפדת, תמונות גיבור גדולות ומיקרו-אנימציות עדינות שמובילות את העין אל הכפתור.',
				'featured'  => '0',
				'stack'     => 'חנות איקומרס',
				'desktop'   => $img . 'sunrise-desktop.webp',
				'mobile'    => '',
				'order'     => 3,
			),
			array(
				'title'     => 'D-Atelier — מותג בהתאמה אישית',
				'content'   => 'חוויית מותג יוקרתית עם פתיח מונפש (rose-gold), זהות ויזואלית עשירה וחנות איקומרס מאחורי הקלעים. הכל מוגש מהיר דרך Cloudflare.',
				'url'       => 'https://d-atelier.co.il/',
				'client'    => 'D-Atelier',
				'year'      => '2026',
				'accent'    => '#C08457',
				'tech'      => 'WordPress, WooCommerce, Cloudflare',
				'challenge' => 'האתגר: לפתוח בחוויית מותג קולנועית מבלי לפגוע במהירות. הפתרון: פתיח וידאו קליל, טעינה עצלה של נכסים וקאשינג ברשת Cloudflare.',
				'featured'  => '0',
				'stack'     => 'חנות איקומרס',
				'desktop'   => $img . 'datelier-desktop.webp',
				'mobile'    => $img . 'datelier-mobile.webp',
				'order'     => 4,
			),
		);

		foreach ( $projects as $p ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => Ofnoa_Portfolio_CPT::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $p['title'],
					'post_content' => $p['content'],
					'post_excerpt' => wp_trim_words( $p['content'], 22 ),
					'menu_order'   => $p['order'],
				)
			);

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_ofnoa_url', esc_url_raw( $p['url'] ) );
			update_post_meta( $post_id, '_ofnoa_target', '_blank' );
			update_post_meta( $post_id, '_ofnoa_client', $p['client'] );
			update_post_meta( $post_id, '_ofnoa_year', $p['year'] );
			update_post_meta( $post_id, '_ofnoa_accent', $p['accent'] );
			update_post_meta( $post_id, '_ofnoa_tech', $p['tech'] );
			update_post_meta( $post_id, '_ofnoa_challenge', $p['challenge'] );
			update_post_meta( $post_id, '_ofnoa_featured', $p['featured'] );
			update_post_meta( $post_id, '_ofnoa_shot_desktop', esc_url_raw( $p['desktop'] ) );
			if ( $p['mobile'] ) {
				update_post_meta( $post_id, '_ofnoa_shot_mobile', esc_url_raw( $p['mobile'] ) );
			}

			if ( ! empty( $p['stack'] ) ) {
				wp_set_object_terms( $post_id, $p['stack'], Ofnoa_Portfolio_CPT::TAXONOMY, false );
			}
		}

		update_option( self::FLAG, 1 );
	}
}
