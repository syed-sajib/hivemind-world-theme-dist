window.HVM_MIXER_JS = '1.3.0'; /* execution marker */
/* Hivemind Mixer — navbar anchor smooth-scroll.
 * Registered LATE (after Elementor's frontend inits) so it reliably wins;
 * scrollIntoView is scroll-container-agnostic and honors CSS scroll-margin-top. */
( function () {
	function onClick( e ) {
		var a = e.target && e.target.closest ? e.target.closest( 'a[href*="#hvm-"]' ) : null;
		if ( ! a ) { return; }
		var hash = ( a.getAttribute( 'href' ) || '' ).split( '#' )[1];
		if ( ! hash ) { return; }
		var el = document.getElementById( hash );
		if ( ! el ) { return; }
		e.preventDefault();
		e.stopPropagation();
		el.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		if ( history.replaceState ) { history.replaceState( null, '', '#' + hash ); }
	}
	function bind() { document.addEventListener( 'click', onClick, true ); }
	// Defer so it registers after Elementor's own handlers are wired.
	window.addEventListener( 'load', function () { setTimeout( bind, 300 ); } );
	if ( document.readyState === 'complete' ) { setTimeout( bind, 300 ); }
} )();

/* Hivemind Mixer — hero countdown. */
( function () {
	function pad( n ) { return ( n < 10 ? '0' : '' ) + n; }
	function tick( el ) {
		var ts = parseInt( el.getAttribute( 'data-ts' ), 10 ) * 1000;
		var diff = Math.max( 0, ts - Date.now() );
		var s = Math.floor( diff / 1000 );
		var d = Math.floor( s / 86400 ); s -= d * 86400;
		var h = Math.floor( s / 3600 );  s -= h * 3600;
		var m = Math.floor( s / 60 );    s -= m * 60;
		var set = function ( u, v ) {
			var b = el.querySelector( '[data-u="' + u + '"]' );
			if ( b ) { b.textContent = pad( v ); }
		};
		set( 'd', d ); set( 'h', h ); set( 'm', m ); set( 's', s );
	}
	function init() {
		var els = document.querySelectorAll( '.hvm-countdown' );
		els.forEach( function ( el ) {
			tick( el );
			setInterval( function () { tick( el ); }, 1000 );
		} );
	}
	if ( document.readyState !== 'loading' ) { init(); }
	else { document.addEventListener( 'DOMContentLoaded', init ); }
} )();

/* Hivemind Mixer — schedule tabs (desktop) / accordion (mobile).
 * Click a tab → activate it + its panel (CSS fade). On mobile the panel is moved
 * to sit right after its tab so tab/content/tab/content reads as an accordion. */
( function () {
	function initSched( root ) {
		var tabs   = [].slice.call( root.querySelectorAll( '.hvm-sched-tab' ) );
		var panels = [].slice.call( root.querySelectorAll( '.hvm-sched-panel' ) );
		var stage  = root.querySelector( '.hvm-sched-stage' );
		if ( ! tabs.length || ! panels.length ) { return; }

		function activate( i ) {
			tabs.forEach( function ( t, j ) { t.setAttribute( 'aria-selected', j === i ? 'true' : 'false' ); } );
			panels.forEach( function ( p, j ) { p.classList.toggle( 'is-active', j === i ); } );
		}
		tabs.forEach( function ( t, i ) { t.addEventListener( 'click', function () { activate( i ); } ); } );

		var mq = window.matchMedia( '(max-width: 900px)' );
		function layout() {
			if ( mq.matches ) {
				// accordion: move each panel to directly after its tab
				tabs.forEach( function ( t, i ) { if ( panels[ i ] ) { t.insertAdjacentElement( 'afterend', panels[ i ] ); } } );
			} else if ( stage ) {
				// tabs: panels live (overlapping) in the stage column
				panels.forEach( function ( p ) { stage.appendChild( p ); } );
			}
		}
		layout();
		if ( mq.addEventListener ) { mq.addEventListener( 'change', layout ); }
		else if ( mq.addListener ) { mq.addListener( layout ); }
		activate( 0 );
	}
	function boot() {
		[].slice.call( document.querySelectorAll( '[data-hvm-sched]' ) ).forEach( initSched );
	}
	if ( document.readyState !== 'loading' ) { boot(); }
	else { document.addEventListener( 'DOMContentLoaded', boot ); }
} )();

/* Hivemind — schedule "Next Day": cycle through day blocks (Summit multi-day). */
( function () {
	function initDays( wrap ) {
		var blocks = [].slice.call( wrap.querySelectorAll( '.hvm-sched-dayblock' ) );
		if ( blocks.length < 2 ) { return; }
		var cur = 0;
		function show( i ) {
			blocks.forEach( function ( b, j ) {
				b.style.display = ( j === i ) ? '' : 'none';
				b.classList.toggle( 'is-active', j === i );
			} );
		}
		[].slice.call( wrap.querySelectorAll( '.hvm-sched-nextday' ) ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault(); // the button is an <a href="#"> now
				cur = ( cur + 1 ) % blocks.length;
				show( cur );
			} );
		} );
		show( 0 );
	}
	function boot() {
		[].slice.call( document.querySelectorAll( '[data-hvm-sched-days]' ) ).forEach( initDays );
	}
	if ( document.readyState !== 'loading' ) { boot(); }
	else { document.addEventListener( 'DOMContentLoaded', boot ); }
} )();

/* Hivemind — Sticky Probar: dock to bottom + slide up past a scroll threshold.
 * Stays inline in the Elementor editor (skips when the editor body class is present). */
( function () {
	function init() {
		if ( document.body.classList.contains( 'elementor-editor-active' ) ) { return; }
		var docks = [].slice.call( document.querySelectorAll( '[data-hvm-sticky-dock]' ) );
		docks.forEach( function ( d ) {
			var th = parseInt( d.getAttribute( 'data-threshold' ), 10 );
			if ( isNaN( th ) ) { th = 1000; }
			d.classList.add( 'is-fixed' );
			var ticking = false;
			function apply() {
				var y = window.pageYOffset || document.documentElement.scrollTop || 0;
				d.classList.toggle( 'is-docked', y > th );
				ticking = false;
			}
			function onScroll() { if ( ! ticking ) { ticking = true; window.requestAnimationFrame( apply ); } }
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			window.addEventListener( 'resize', onScroll, { passive: true } );
			apply();
		} );
	}
	if ( document.readyState !== 'loading' ) { init(); }
	else { document.addEventListener( 'DOMContentLoaded', init ); }
} )();

/* Hivemind Mixer — hotel promo code: click (or Enter/Space) to copy. */
( function () {
	function fallbackCopy( text ) {
		var t = document.createElement( 'textarea' );
		t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
		document.body.appendChild( t ); t.focus(); t.select();
		try { document.execCommand( 'copy' ); } catch ( e ) {}
		document.body.removeChild( t );
	}
	function copyFrom( el ) {
		var code = el.getAttribute( 'data-code' ) || el.textContent;
		var done = function () {
			el.classList.add( 'is-copied' );   // shows the "Copied" tooltip; code text unchanged
			clearTimeout( el._hvmT );
			el._hvmT = setTimeout( function () { el.classList.remove( 'is-copied' ); }, 1300 );
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( code ).then( done ).catch( function () { fallbackCopy( code ); done(); } );
		} else { fallbackCopy( code ); done(); }
	}
	document.addEventListener( 'click', function ( e ) {
		var el = e.target && e.target.closest ? e.target.closest( '.hvm-hotel-promo-code' ) : null;
		if ( el ) { copyFrom( el ); }
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Enter' && e.key !== ' ' ) { return; }
		var el = e.target && e.target.closest ? e.target.closest( '.hvm-hotel-promo-code' ) : null;
		if ( el ) { e.preventDefault(); copyFrom( el ); }
	} );
} )();

/* Hivemind Mixer — image carousel (.hvm-gal).
 * Slides-per-view comes from the CSS var --hvm-gal-per (responsive, set by the
 * Elementor panel). Track translateX by page; progress shows "highest visible
 * slide / total" (e.g. 03/07 = up to slide 3 of 7 in view). Loop + autoplay opt. */
( function () {
	function initGal( root ) {
		var viewport = root.querySelector( '.hvm-gal-viewport' );
		var track    = root.querySelector( '.hvm-gal-track' );
		var slides   = [].slice.call( root.querySelectorAll( '.hvm-gal-slide' ) );
		var prevBtn  = root.querySelector( '.hvm-gal-prev' );
		var nextBtn  = root.querySelector( '.hvm-gal-next' );
		var curEl    = root.querySelector( '.hvm-gal-cur' );
		var totEl    = root.querySelector( '.hvm-gal-tot' );
		if ( ! track || slides.length === 0 ) { return; }

		var total   = slides.length;
		var loop    = root.getAttribute( 'data-loop' ) === 'yes';
		var autoAtt = root.getAttribute( 'data-autoplay' ) === 'yes';
		var delay   = parseInt( root.getAttribute( 'data-delay' ), 10 ) || 4000;
		var index   = 0; // index of the first (left-most) visible slide
		var timer   = null;

		function perView() {
			var v = getComputedStyle( root ).getPropertyValue( '--hvm-gal-per' );
			var n = parseInt( v, 10 );
			if ( ! n || n < 1 ) { n = 1; }
			return Math.min( n, total );
		}
		function maxIndex() { return Math.max( 0, total - perView() ); }

		function render() {
			var pv    = perView();
			var maxI  = maxIndex();
			if ( index > maxI ) { index = maxI; }
			if ( index < 0 ) { index = 0; }
			// each slide's flex-basis includes the shared gap; translate by whole slides
			var slide = slides[0];
			var sw    = slide ? slide.getBoundingClientRect().width : 0;
			var gap   = parseFloat( getComputedStyle( track ).columnGap || getComputedStyle( track ).gap || 0 ) || 0;
			var shift = index * ( sw + gap );
			track.style.transform = 'translateX(' + ( -shift ) + 'px)';

			if ( curEl ) {
				var shown = Math.min( total, index + pv );
				curEl.textContent = ( shown < 10 ? '0' : '' ) + shown;
			}
			if ( totEl ) { totEl.textContent = ( total < 10 ? '0' : '' ) + total; }

			if ( ! loop ) {
				if ( prevBtn ) { prevBtn.disabled = ( index <= 0 ); }
				if ( nextBtn ) { nextBtn.disabled = ( index >= maxI ); }
			}
		}

		function go( dir ) {
			var maxI = maxIndex();
			index += dir;
			if ( index > maxI ) { index = loop ? 0 : maxI; }
			if ( index < 0 )    { index = loop ? maxI : 0; }
			render();
		}

		function stopAuto() { if ( timer ) { clearInterval( timer ); timer = null; } }
		function startAuto() {
			if ( ! autoAtt || total <= perView() ) { return; }
			stopAuto();
			timer = setInterval( function () { go( 1 ); }, delay );
		}

		if ( prevBtn ) { prevBtn.addEventListener( 'click', function () { go( -1 ); startAuto(); } ); }
		if ( nextBtn ) { nextBtn.addEventListener( 'click', function () { go( 1 );  startAuto(); } ); }
		if ( viewport ) {
			viewport.addEventListener( 'mouseenter', stopAuto );
			viewport.addEventListener( 'mouseleave', startAuto );
		}

		var rt;
		window.addEventListener( 'resize', function () {
			clearTimeout( rt );
			rt = setTimeout( render, 150 );
		} );

		render();
		startAuto();
		// re-render once images/fonts settle (flex widths can shift)
		window.addEventListener( 'load', render );
	}
	function boot() {
		[].slice.call( document.querySelectorAll( '[data-hvm-gal]' ) ).forEach( initGal );
	}
	if ( document.readyState !== 'loading' ) { boot(); }
	else { document.addEventListener( 'DOMContentLoaded', boot ); }
} )();

/* Hivemind Mixer — Team widget: category tabs (Layout 2) + mobile carousel (Layout 1). */
( function () {
	function initTabs( root ) {
		var tabs    = [].slice.call( root.querySelectorAll( '.hvm-team-tab' ) );
		var section = root.closest( '.hvm-team' ) || document;
		var panels  = [].slice.call( section.querySelectorAll( '.hvm-team-panel' ) );
		tabs.forEach( function ( t ) {
			t.addEventListener( 'click', function () {
				var key = t.getAttribute( 'data-tab' );
				tabs.forEach( function ( x ) { x.classList.toggle( 'is-active', x === t ); } );
				panels.forEach( function ( p ) { p.classList.toggle( 'is-active', p.getAttribute( 'data-panel' ) === key ); } );
			} );
		} );
	}

	function initCarousel( root ) {
		var track = root.querySelector( '.hvm-team-track' );
		var cards = [].slice.call( root.querySelectorAll( '.hvm-team-card' ) );
		var prev  = root.querySelector( '.hvm-team-prev' );
		var next  = root.querySelector( '.hvm-team-next' );
		if ( ! track || ! cards.length ) { return; }
		var index = 0;
		var mq = window.matchMedia( '(max-width: 767px)' );

		function perView() {
			var v = parseInt( getComputedStyle( root ).getPropertyValue( '--hvm-team-per' ), 10 );
			if ( ! v || v < 1 ) { v = 1; }
			return Math.min( v, cards.length );
		}
		function maxIndex() { return Math.max( 0, cards.length - perView() ); }
		function render() {
			if ( ! mq.matches ) { track.style.transform = ''; return; } // desktop = grid
			var maxI = maxIndex();
			if ( index > maxI ) { index = maxI; }
			if ( index < 0 ) { index = 0; }
			var w   = cards[0] ? cards[0].getBoundingClientRect().width : 0;
			var gap = parseFloat( getComputedStyle( track ).columnGap || getComputedStyle( track ).gap || 0 ) || 0;
			track.style.transform = 'translateX(' + ( -( index * ( w + gap ) ) ) + 'px)';
			if ( prev ) { prev.disabled = index <= 0; }
			if ( next ) { next.disabled = index >= maxI; }
		}
		function go( d ) { index += d; render(); }
		if ( prev ) { prev.addEventListener( 'click', function () { go( -1 ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1 ); } ); }
		var rt;
		window.addEventListener( 'resize', function () { clearTimeout( rt ); rt = setTimeout( render, 150 ); } );
		if ( mq.addEventListener ) { mq.addEventListener( 'change', render ); }
		else if ( mq.addListener ) { mq.addListener( render ); }
		render();
		window.addEventListener( 'load', render );
	}

	/* Layout 4 — full carousel with bottom dot navigation (no arrows). Slides-to-show
	   comes from the Columns control (--hvm-team-cols); Slides-to-scroll (data-slide-by)
	   sets how many cards advance per step. When Loop is on it runs as a true infinite
	   carousel: the whole set is duplicated on each side, and after any step that crosses
	   the edge we snap by one set-width with the transition off — content is identical
	   across the seam, so it slides forever with no visible rewind. */
	function initSlider( root ) {
		var track = root.querySelector( '.hvm-team-track' );
		var view  = root.querySelector( '.hvm-team-viewport' );
		var dots  = root.querySelector( '.hvm-team-dots' );
		var cards = [].slice.call( root.querySelectorAll( '.hvm-team-card' ) ); // real cards only
		if ( ! track || ! cards.length || ! dots ) { return; }

		var editor   = document.body.classList.contains( 'elementor-editor-active' );
		var autoplay = root.getAttribute( 'data-autoplay' ) === '1' && ! editor;
		var duration = parseInt( root.getAttribute( 'data-duration' ), 10 ) || 4000;
		var loop     = root.getAttribute( 'data-loop' ) === '1';
		var by       = Math.max( 1, parseInt( root.getAttribute( 'data-slide-by' ), 10 ) || 1 );
		var TRANS    = 'transform .45s cubic-bezier(.4,0,.2,1)';

		var n = cards.length, per = 1, steps = 1, pos = 0, LEFT = 0, animating = false, timer = null;

		function perView() {
			var v = parseInt( getComputedStyle( root ).getPropertyValue( '--hvm-team-cols' ), 10 );
			if ( ! v || v < 1 ) { v = 1; }
			return Math.min( v, n );
		}
		function unit() {
			var first = cards[0];
			var w   = first ? first.getBoundingClientRect().width : 0;
			var gap = parseFloat( getComputedStyle( track ).columnGap || getComputedStyle( track ).gap || 0 ) || 0;
			return w + gap;
		}
		function clearClones() {
			[].slice.call( track.querySelectorAll( '.hvm-team-card.is-clone' ) ).forEach( function ( c ) { c.parentNode.removeChild( c ); } );
		}
		function place( animate ) {
			track.style.transition = animate ? TRANS : 'none';
			track.style.transform  = 'translateX(' + ( -( pos * unit() ) ) + 'px)';
			if ( ! animate ) { void track.offsetWidth; track.style.transition = TRANS; } // reflow so the next move animates
		}
		function activeStep() {
			var s = loop ? Math.round( ( pos - LEFT ) / by ) : Math.round( pos / by );
			return ( ( s % steps ) + steps ) % steps;
		}
		function markDot() {
			var a = activeStep();
			[].slice.call( dots.children ).forEach( function ( d, i ) { d.classList.toggle( 'is-active', i === a ); } );
		}

		// After a move settles, snap back into the middle (real) set — invisibly, since
		// the clone on each side is an exact copy of the real set.
		track.addEventListener( 'transitionend', function ( e ) {
			if ( e.propertyName !== 'transform' ) { return; }
			animating = false;
			if ( ! loop ) { return; }
			var moved = false;
			while ( pos >= LEFT + n ) { pos -= n; moved = true; }
			while ( pos < LEFT )      { pos += n; moved = true; }
			if ( moved ) { place( false ); markDot(); }
		} );

		function nudge( cardsDelta, restartTimer ) {
			if ( animating && loop ) { return; }
			pos += cardsDelta;
			if ( loop ) { animating = true; }
			else { pos = Math.max( 0, Math.min( pos, Math.max( 0, n - per ) ) ); }
			place( true ); markDot();
			if ( restartTimer ) { restart(); }
		}
		function toStep( k, restartTimer ) {
			if ( animating && loop ) { return; }
			if ( loop ) { pos = LEFT + ( ( ( k % steps ) + steps ) % steps ) * by; animating = true; }
			else { k = Math.max( 0, Math.min( steps - 1, k ) ); pos = Math.min( k * by, Math.max( 0, n - per ) ); }
			place( true ); markDot();
			if ( restartTimer ) { restart(); }
		}

		function build() {
			clearClones();
			n   = cards.length;
			per = perView();
			steps = loop
				? Math.max( 1, Math.ceil( n / by ) )
				: Math.max( 1, Math.ceil( Math.max( 0, n - per ) / by ) + 1 );

			// dots
			dots.innerHTML = '';
			for ( var i = 0; i < steps; i++ ) {
				var b = document.createElement( 'button' );
				b.type = 'button'; b.setAttribute( 'role', 'tab' );
				b.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
				( function ( idx ) { b.addEventListener( 'click', function () { toStep( idx, true ); } ); } )( i );
				dots.appendChild( b );
			}
			dots.style.display = steps > 1 ? '' : 'none';

			var infinite = loop && n > per;
			if ( infinite ) {
				LEFT = n;
				var leftF = document.createDocumentFragment(), rightF = document.createDocumentFragment();
				cards.forEach( function ( c ) { var cl = c.cloneNode( true ); cl.classList.add( 'is-clone' ); cl.setAttribute( 'aria-hidden', 'true' ); leftF.appendChild( cl ); } );
				cards.forEach( function ( c ) { var cl = c.cloneNode( true ); cl.classList.add( 'is-clone' ); cl.setAttribute( 'aria-hidden', 'true' ); rightF.appendChild( cl ); } );
				track.insertBefore( leftF, track.firstChild );
				track.appendChild( rightF );
				pos = LEFT; // first real card sits right after the left clone set
			} else {
				LEFT = 0; pos = Math.min( pos, Math.max( 0, n - per ) );
			}
			place( false ); markDot();
		}

		function stop()  { if ( timer ) { clearInterval( timer ); timer = null; } }
		function start() { if ( autoplay && steps > 1 ) { stop(); timer = setInterval( function () { nudge( by, false ); }, duration ); } }
		function restart() { stop(); start(); }

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		document.addEventListener( 'visibilitychange', function () { if ( document.hidden ) { stop(); } else { start(); } } );

		// Swipe (touch) support.
		var sx = 0, dx = 0, dragging = false;
		view.addEventListener( 'touchstart', function ( e ) { sx = e.touches[0].clientX; dx = 0; dragging = true; stop(); }, { passive: true } );
		view.addEventListener( 'touchmove', function ( e ) { if ( dragging ) { dx = e.touches[0].clientX - sx; } }, { passive: true } );
		view.addEventListener( 'touchend', function () {
			if ( dragging && Math.abs( dx ) > 40 ) { nudge( ( dx < 0 ? 1 : -1 ) * by, true ); }
			dragging = false; start();
		} );

		var rt;
		window.addEventListener( 'resize', function () { clearTimeout( rt ); rt = setTimeout( function () { animating = false; build(); }, 150 ); } );
		build();
		start();
		window.addEventListener( 'load', build );
	}

	function boot() {
		[].slice.call( document.querySelectorAll( '[data-hvm-team-tabs]' ) ).forEach( initTabs );
		[].slice.call( document.querySelectorAll( '[data-hvm-team]' ) ).forEach( initCarousel );
		[].slice.call( document.querySelectorAll( '[data-hvm-team-slider]' ) ).forEach( initSlider );
	}
	if ( document.readyState !== 'loading' ) { boot(); }
	else { document.addEventListener( 'DOMContentLoaded', boot ); }
} )();
