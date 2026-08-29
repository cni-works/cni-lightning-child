( function () {
	'use strict';

	window.addEventListener( 'DOMContentLoaded', function () {
		const logo = document.querySelector( '.site-header-logo img' );
		if ( ! logo || typeof lightningChildHeaderLogos === 'undefined' ) {
			return;
		}
		if ( logo.classList.contains( 'lightning-child-header-logo-switcher__reference' ) ) {
			return;
		}

		const defaultLogo = logo.getAttribute( 'data-src' ) || logo.getAttribute( 'src' );
		if ( ! defaultLogo ) {
			return;
		}
		const logoLink = logo.closest( 'a' );
		if ( ! logoLink ) {
			return;
		}
		if ( ! logoLink.getAttribute( 'aria-label' ) && logo.getAttribute( 'alt' ) ) {
			logoLink.setAttribute( 'aria-label', logo.getAttribute( 'alt' ) );
		}

		const escapeCssUrl = function ( url ) {
			return String( url ).replace( /["\\\n\r\f]/g, '\\$&' );
		};
		const switcher = document.createElement( 'span' );
		switcher.className = 'lightning-child-header-logo-switcher';
		logo.classList.add( 'lightning-child-header-logo-switcher__reference' );
		logo.parentNode.insertBefore( switcher, logo );
		switcher.appendChild( logo );

		const logoSources = {
			normal: defaultLogo,
			transparent: lightningChildHeaderLogos.transparent || defaultLogo,
			scrolled: lightningChildHeaderLogos.scrolled || defaultLogo,
		};

		Object.keys( logoSources ).forEach( function ( state ) {
			const layer = document.createElement( 'span' );
			layer.className = 'lightning-child-header-logo-switcher__layer lightning-child-header-logo-switcher__layer--' + state;
			layer.style.backgroundImage = 'url("' + escapeCssUrl( logoSources[ state ] ) + '")';
			layer.setAttribute( 'aria-hidden', 'true' );
			switcher.appendChild( layer );
		} );

		const updateLogo = function () {
			let nextState = 'normal';

			if ( document.body.classList.contains( 'header_scrolled' ) && lightningChildHeaderLogos.scrolled ) {
				nextState = 'scrolled';
			} else if (
				document.body.classList.contains( 'lightning-child-transparent-header' )
				&& lightningChildHeaderLogos.transparent
			) {
				nextState = 'transparent';
			}

			switcher.dataset.logoState = nextState;
		};

		new MutationObserver( updateLogo ).observe( document.body, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		updateLogo();
	} );
}() );
