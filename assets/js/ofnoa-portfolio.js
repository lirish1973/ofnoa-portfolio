/* =========================================================================
   Ofnoa Portfolio — front-end behaviour (vanilla, multi-instance safe)
   ========================================================================= */
( function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var COARSE = window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches;

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) { fn(); }
		else { document.addEventListener( 'DOMContentLoaded', fn ); }
	}

	function initSection( root ) {
		if ( root.__ofnoaInit ) { return; }
		root.__ofnoaInit = true;

		var config = {};
		try { config = JSON.parse( root.getAttribute( 'data-ofnoa-config' ) || '{}' ); } catch ( e ) {}

		revealCards( root, config );
		if ( config.tilt && ! COARSE ) { initTilt( root ); }
		initParallax( root );
		initFilters( root );
		initModal( root );
		initCarouselDrag( root );
		if ( config.sparkles && ! REDUCED ) { initFx( root, config ); }
	}

	/* ---------- Reveal on scroll ---------- */
	function revealCards( root, config ) {
		var cards = root.querySelectorAll( '.ofnoa-card' );
		if ( ! config.animate || REDUCED || ! ( 'IntersectionObserver' in window ) ) {
			cards.forEach( function ( c ) { c.classList.add( 'is-in' ); } );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( en.isIntersecting ) {
					var card = en.target;
					var delay = parseInt( card.getAttribute( 'data-delay' ) || '0', 10 );
					setTimeout( function () { card.classList.add( 'is-in' ); }, delay );
					io.unobserve( card );
				}
			} );
		}, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' } );
		cards.forEach( function ( c ) { io.observe( c ); } );
	}

	/* ---------- 3D tilt ---------- */
	function initTilt( root ) {
		var targets = root.querySelectorAll( '[data-tilt]' );
		targets.forEach( function ( elm ) {
			var raf = null, rect = null;
			function onMove( e ) {
				if ( ! rect ) { rect = elm.getBoundingClientRect(); }
				var px = ( e.clientX - rect.left ) / rect.width;
				var py = ( e.clientY - rect.top ) / rect.height;
				var rx = ( py - 0.5 ) * -14;
				var ry = ( px - 0.5 ) * 16;
				if ( raf ) { cancelAnimationFrame( raf ); }
				raf = requestAnimationFrame( function () {
					elm.style.transform = 'rotateX(' + rx.toFixed( 2 ) + 'deg) rotateY(' + ry.toFixed( 2 ) + 'deg)';
					var shine = elm.querySelector( '.ofnoa-tilt-shine' );
					if ( shine ) {
						shine.style.setProperty( '--mx', ( px * 100 ).toFixed( 1 ) + '%' );
						shine.style.setProperty( '--my', ( py * 100 ).toFixed( 1 ) + '%' );
					}
					var layers = elm.querySelectorAll( '[data-depth]' );
					layers.forEach( function ( ly ) {
						var d = parseFloat( ly.getAttribute( 'data-depth' ) ) || 0;
						ly.style.transform = 'translate3d(' + ( ( px - 0.5 ) * d ).toFixed( 1 ) + 'px,' + ( ( py - 0.5 ) * d ).toFixed( 1 ) + 'px,0)';
					} );
				} );
			}
			function reset() {
				rect = null;
				if ( raf ) { cancelAnimationFrame( raf ); }
				elm.style.transform = '';
				elm.querySelectorAll( '[data-depth]' ).forEach( function ( ly ) { ly.style.transform = ''; } );
			}
			elm.addEventListener( 'mouseenter', function () { rect = elm.getBoundingClientRect(); } );
			elm.addEventListener( 'mousemove', onMove );
			elm.addEventListener( 'mouseleave', reset );
		} );
	}

	/* ---------- Parallax (cinematic media) ---------- */
	function initParallax( root ) {
		if ( REDUCED ) { return; }
		var items = root.querySelectorAll( '[data-parallax]' );
		if ( ! items.length ) { return; }
		var ticking = false;
		function update() {
			ticking = false;
			var vh = window.innerHeight;
			items.forEach( function ( it ) {
				var r = it.getBoundingClientRect();
				if ( r.bottom < 0 || r.top > vh ) { return; }
				var speed = parseFloat( it.getAttribute( 'data-parallax' ) ) || 0.1;
				var offset = ( ( r.top + r.height / 2 ) - vh / 2 ) * speed;
				it.style.transform = 'translateY(' + offset.toFixed( 1 ) + 'px)';
			} );
		}
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) { requestAnimationFrame( update ); ticking = true; }
		}, { passive: true } );
		update();
	}

	/* ---------- Filters ---------- */
	function initFilters( root ) {
		var bar = root.querySelector( '.ofnoa-filters' );
		if ( ! bar ) { return; }
		var buttons = bar.querySelectorAll( '.ofnoa-filter' );
		var cards = root.querySelectorAll( '.ofnoa-card' );
		bar.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.ofnoa-filter' );
			if ( ! btn ) { return; }
			var f = btn.getAttribute( 'data-filter' );
			buttons.forEach( function ( b ) {
				b.classList.toggle( 'is-active', b === btn );
				b.setAttribute( 'aria-selected', b === btn ? 'true' : 'false' );
			} );
			cards.forEach( function ( card ) {
				var stacks = ( card.getAttribute( 'data-stack' ) || '' ).split( ' ' );
				var show = ( f === '*' ) || stacks.indexOf( f ) !== -1;
				card.style.display = show ? '' : 'none';
			} );
		} );
	}

	/* ---------- Modal ---------- */
	function initModal( root ) {
		var modal = root.querySelector( '.ofnoa-modal' );
		if ( ! modal ) { return; }
		var body = modal.querySelector( '.ofnoa-modal-body' );
		var lastFocus = null;

		function open( tplId ) {
			var tpl = root.querySelector( '#' + CSS.escape( tplId ) );
			if ( ! tpl ) { return; }
			body.innerHTML = '';
			body.appendChild( tpl.content.cloneNode( true ) );
			modal.hidden = false;
			modal.setAttribute( 'aria-hidden', 'false' );
			requestAnimationFrame( function () { modal.classList.add( 'is-open' ); } );
			document.documentElement.style.overflow = 'hidden';
			var closeBtn = modal.querySelector( '.ofnoa-modal-close' );
			if ( closeBtn ) { closeBtn.focus(); }
		}
		function close() {
			modal.classList.remove( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'true' );
			document.documentElement.style.overflow = '';
			setTimeout( function () { modal.hidden = true; body.innerHTML = ''; }, 380 );
			if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
		}

		root.addEventListener( 'click', function ( e ) {
			var opener = e.target.closest( '.ofnoa-open-detail' );
			if ( opener ) {
				lastFocus = opener;
				open( opener.getAttribute( 'data-detail' ) );
			}
		} );
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '[data-close]' ) ) { close(); }
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! modal.hidden ) { close(); }
		} );
	}

	/* ---------- Carousel drag-to-scroll ---------- */
	function initCarouselDrag( root ) {
		if ( ! root.classList.contains( 'ofnoa-layout-carousel' ) ) { return; }
		var track = root.querySelector( '.ofnoa-grid' );
		if ( ! track ) { return; }
		var down = false, startX = 0, scroll = 0;
		track.addEventListener( 'pointerdown', function ( e ) {
			down = true; startX = e.clientX; scroll = track.scrollLeft; track.setPointerCapture( e.pointerId );
		} );
		track.addEventListener( 'pointermove', function ( e ) {
			if ( ! down ) { return; }
			track.scrollLeft = scroll - ( e.clientX - startX );
		} );
		track.addEventListener( 'pointerup', function () { down = false; } );
		track.addEventListener( 'pointercancel', function () { down = false; } );
	}

	/* =====================================================================
	   FX canvas: sparkles / fireworks / constellation / aurora
	   ===================================================================== */
	function initFx( root, config ) {
		var canvas = root.querySelector( '.ofnoa-fx' );
		if ( ! canvas ) { return; }
		var ctx = canvas.getContext( '2d' );
		var style = canvas.getAttribute( 'data-style' ) || 'sparkles';
		var accent = config.accent || '#40E0FF';
		var accent2 = config.accent2 || '#B9A7FF';
		var dpr = Math.min( window.devicePixelRatio || 1, 2 );
		var W = 0, H = 0, particles = [], rockets = [], running = true, raf = null, t = 0;

		function hexToRgb( hex ) {
			var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec( ( hex || '' ).replace( /^#/, '#' ) );
			return m ? [ parseInt( m[ 1 ], 16 ), parseInt( m[ 2 ], 16 ), parseInt( m[ 3 ], 16 ) ] : [ 64, 224, 255 ];
		}
		var cA = hexToRgb( accent ), cB = hexToRgb( accent2 );
		function rgba( c, a ) { return 'rgba(' + c[ 0 ] + ',' + c[ 1 ] + ',' + c[ 2 ] + ',' + a + ')'; }

		function resize() {
			W = canvas.clientWidth; H = canvas.clientHeight;
			canvas.width = W * dpr; canvas.height = H * dpr;
			ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
			seed();
		}

		function seed() {
			particles = [];
			var count = Math.round( Math.min( 90, ( W * H ) / 16000 ) );
			if ( style === 'sparkles' || style === 'constellation' || style === 'aurora' ) {
				for ( var i = 0; i < count; i++ ) {
					particles.push( {
						x: Math.random() * W, y: Math.random() * H,
						r: Math.random() * 1.8 + 0.4,
						vx: ( Math.random() - 0.5 ) * 0.25, vy: ( Math.random() - 0.5 ) * 0.25,
						tw: Math.random() * Math.PI * 2, sp: Math.random() * 0.04 + 0.008,
						c: Math.random() > 0.5 ? cA : cB
					} );
				}
			}
		}

		function spawnRocket() {
			rockets.push( {
				x: Math.random() * W, y: H + 10,
				tx: W * ( 0.15 + Math.random() * 0.7 ), ty: H * ( 0.15 + Math.random() * 0.4 ),
				vx: 0, vy: 0, done: false, c: Math.random() > 0.5 ? cA : cB
			} );
		}
		function burst( x, y, c ) {
			var n = 26 + Math.floor( Math.random() * 20 );
			for ( var i = 0; i < n; i++ ) {
				var ang = ( Math.PI * 2 * i ) / n;
				var sp = Math.random() * 2.6 + 0.8;
				particles.push( {
					x: x, y: y, vx: Math.cos( ang ) * sp, vy: Math.sin( ang ) * sp,
					r: Math.random() * 2 + 0.8, life: 1, decay: Math.random() * 0.012 + 0.008, c: c, spark: true
				} );
			}
		}

		function step() {
			if ( ! running ) { return; }
			t++;
			ctx.clearRect( 0, 0, W, H );

			if ( style === 'aurora' ) {
				var g = ctx.createLinearGradient( 0, 0, W, H );
				var pulse = 0.06 + Math.sin( t * 0.01 ) * 0.03;
				g.addColorStop( 0, rgba( cA, pulse ) );
				g.addColorStop( 0.5, rgba( cB, pulse * 0.8 ) );
				g.addColorStop( 1, rgba( cA, pulse * 0.5 ) );
				ctx.fillStyle = g; ctx.fillRect( 0, 0, W, H );
			}

			if ( style === 'constellation' ) {
				for ( var a = 0; a < particles.length; a++ ) {
					for ( var b = a + 1; b < particles.length; b++ ) {
						var dx = particles[ a ].x - particles[ b ].x, dy = particles[ a ].y - particles[ b ].y;
						var dist = dx * dx + dy * dy;
						if ( dist < 12000 ) {
							ctx.strokeStyle = rgba( cA, ( 1 - dist / 12000 ) * 0.18 );
							ctx.lineWidth = 0.6;
							ctx.beginPath(); ctx.moveTo( particles[ a ].x, particles[ a ].y ); ctx.lineTo( particles[ b ].x, particles[ b ].y ); ctx.stroke();
						}
					}
				}
			}

			if ( style === 'fireworks' ) {
				if ( t % 55 === 0 || rockets.length === 0 && Math.random() < 0.04 ) { spawnRocket(); }
				for ( var k = rockets.length - 1; k >= 0; k-- ) {
					var rk = rockets[ k ];
					rk.x += ( rk.tx - rk.x ) * 0.06;
					rk.y += ( rk.ty - rk.y ) * 0.06;
					ctx.fillStyle = rgba( rk.c, 0.9 );
					ctx.beginPath(); ctx.arc( rk.x, rk.y, 2, 0, Math.PI * 2 ); ctx.fill();
					if ( Math.abs( rk.x - rk.tx ) < 6 && Math.abs( rk.y - rk.ty ) < 6 ) { burst( rk.x, rk.y, rk.c ); rockets.splice( k, 1 ); }
				}
			}

			for ( var i = particles.length - 1; i >= 0; i-- ) {
				var p = particles[ i ];
				if ( p.spark ) {
					p.x += p.vx; p.y += p.vy; p.vy += 0.03; p.vx *= 0.99; p.life -= p.decay;
					if ( p.life <= 0 ) { particles.splice( i, 1 ); continue; }
					ctx.fillStyle = rgba( p.c, Math.max( 0, p.life ) );
					ctx.beginPath(); ctx.arc( p.x, p.y, p.r, 0, Math.PI * 2 ); ctx.fill();
				} else {
					p.x += p.vx; p.y += p.vy; p.tw += p.sp;
					if ( p.x < 0 ) { p.x = W; } if ( p.x > W ) { p.x = 0; }
					if ( p.y < 0 ) { p.y = H; } if ( p.y > H ) { p.y = 0; }
					var a2 = 0.35 + Math.sin( p.tw ) * 0.35;
					ctx.fillStyle = rgba( p.c, a2 );
					ctx.beginPath(); ctx.arc( p.x, p.y, p.r, 0, Math.PI * 2 ); ctx.fill();
				}
			}
			raf = requestAnimationFrame( step );
		}

		function start() { if ( ! raf ) { running = true; raf = requestAnimationFrame( step ); } }
		function stop() { running = false; if ( raf ) { cancelAnimationFrame( raf ); raf = null; } }

		var ro = ( 'ResizeObserver' in window ) ? new ResizeObserver( resize ) : null;
		if ( ro ) { ro.observe( canvas ); } else { window.addEventListener( 'resize', resize ); }
		resize();

		// Pause when off-screen or tab hidden (battery/perf).
		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( ents ) {
				ents.forEach( function ( en ) { if ( en.isIntersecting ) { start(); } else { stop(); } } );
			}, { threshold: 0 } ).observe( root );
		} else { start(); }
		document.addEventListener( 'visibilitychange', function () { if ( document.hidden ) { stop(); } else { start(); } } );
	}

	ready( function () {
		document.querySelectorAll( '.ofnoa-portfolio' ).forEach( initSection );
		// Support blocks injected later (editor / AJAX).
		if ( 'MutationObserver' in window ) {
			new MutationObserver( function ( muts ) {
				muts.forEach( function ( m ) {
					Array.prototype.forEach.call( m.addedNodes || [], function ( n ) {
						if ( n.nodeType === 1 ) {
							if ( n.classList && n.classList.contains( 'ofnoa-portfolio' ) ) { initSection( n ); }
							if ( n.querySelectorAll ) { n.querySelectorAll( '.ofnoa-portfolio' ).forEach( initSection ); }
						}
					} );
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	} );
} )();
