( function () {
	'use strict';

	function initializeMobileMenuDrawer() {
		const toggle = document.querySelector( '[data-lightning-child-menu-toggle]' );
		const drawer = document.getElementById( 'lightning-child-mobile-menu-drawer' );
		const closeButton = drawer ? drawer.querySelector( '[data-lightning-child-menu-close]' ) : null;
		const backdrop = document.querySelector( '[data-lightning-child-menu-backdrop]' );
		const mobileQuery = window.matchMedia( '(max-width: 991px)' );
		let isReady = false;
		let isOpen = false;
		let previousFocus = null;
		let closeTimer = null;

		if ( ! toggle || ! drawer || ! closeButton || ! backdrop ) {
			return;
		}

		function getFocusableElements() {
			return Array.prototype.filter.call(
				drawer.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])' ),
				function ( element ) {
					return null !== element.offsetParent;
				}
			);
		}

		function finishClose( restoreFocus ) {
			if ( closeTimer ) {
				window.clearTimeout( closeTimer );
				closeTimer = null;
			}

			drawer.classList.remove( 'is-open', 'is-closing' );
			drawer.removeAttribute( 'role' );
			drawer.removeAttribute( 'aria-modal' );
			drawer.removeAttribute( 'aria-label' );
			document.body.classList.remove( 'lightning-child-mobile-menu-drawer-open' );
			if ( restoreFocus && previousFocus && document.contains( previousFocus ) ) {
				previousFocus.focus();
			}
			previousFocus = null;
		}

		function setOpen( nextOpen, restoreFocus ) {
			if ( ! isReady || nextOpen === isOpen ) {
				return;
			}

			isOpen = nextOpen;
			toggle.classList.toggle( 'is-open', isOpen );
			toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			backdrop.classList.toggle( 'is-open', isOpen );

			if ( isOpen ) {
				if ( closeTimer ) {
					window.clearTimeout( closeTimer );
					closeTimer = null;
				}
				previousFocus = document.activeElement;
				drawer.classList.remove( 'is-closing' );
				drawer.setAttribute( 'role', 'dialog' );
				drawer.setAttribute( 'aria-modal', 'true' );
				drawer.setAttribute( 'aria-label', toggle.textContent.trim() || 'Menu' );
				document.body.classList.add( 'lightning-child-mobile-menu-drawer-open' );
				drawer.classList.add( 'is-open' );
				window.requestAnimationFrame( function () {
					closeButton.focus();
				} );
				return;
			}

			drawer.classList.remove( 'is-open' );
			drawer.classList.add( 'is-closing' );
			closeTimer = window.setTimeout( function () {
				finishClose( restoreFocus );
			}, 350 );
		}

		function activateDrawer() {
			if ( isReady ) {
				return;
			}

			isReady = true;
			backdrop.hidden = false;
			document.body.classList.add( 'lightning-child-mobile-menu-drawer-ready' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		}

		function deactivateDrawer() {
			if ( ! isReady ) {
				return;
			}

			isOpen = false;
			finishClose( false );
			isReady = false;
			backdrop.hidden = true;
			backdrop.classList.remove( 'is-open' );
			toggle.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'lightning-child-mobile-menu-drawer-ready' );
		}

		function syncViewport() {
			if ( mobileQuery.matches ) {
				activateDrawer();
			} else {
				deactivateDrawer();
			}
		}

		toggle.addEventListener( 'click', function ( event ) {
			if ( ! isReady ) {
				return;
			}

			event.preventDefault();
			setOpen( ! isOpen, true );
		} );

		closeButton.addEventListener( 'click', function () {
			setOpen( false, true );
		} );

		backdrop.addEventListener( 'click', function () {
			setOpen( false, true );
		} );

		drawer.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( 'a[href]' ) ) {
				setOpen( false, true );
			}
		} );

		drawer.addEventListener( 'transitionend', function ( event ) {
			if ( ! isOpen && drawer.classList.contains( 'is-closing' ) && 'transform' === event.propertyName ) {
				finishClose( true );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( ! isOpen ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				event.preventDefault();
				setOpen( false, true );
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			const focusableElements = getFocusableElements();
			if ( ! focusableElements.length ) {
				event.preventDefault();
				closeButton.focus();
				return;
			}

			const firstElement = focusableElements[ 0 ];
			const lastElement = focusableElements[ focusableElements.length - 1 ];
			if ( event.shiftKey && document.activeElement === firstElement ) {
				event.preventDefault();
				lastElement.focus();
			} else if ( ! event.shiftKey && document.activeElement === lastElement ) {
				event.preventDefault();
				firstElement.focus();
			}
		} );

		if ( 'function' === typeof mobileQuery.addEventListener ) {
			mobileQuery.addEventListener( 'change', syncViewport );
		} else {
			mobileQuery.addListener( syncViewport );
		}

		syncViewport();
	}

	function initializeFloatingMobileNav() {
		const nav = document.querySelector( '.lightning-child-mobile-fixed-nav--floating' );
		let activeItem = nav ? nav.querySelector( '.lightning-child-mobile-fixed-nav__item.is-current' ) : null;
		let feedbackItem = null;
		let feedbackTimer = null;
		let readyScheduled = false;

		if ( ! nav ) {
			return;
		}

		function requestNextFrame( callback ) {
			if ( 'function' === typeof window.requestAnimationFrame ) {
				window.requestAnimationFrame( callback );
				return;
			}

			window.setTimeout( callback, 0 );
		}

		function scheduleReadyState() {
			if ( readyScheduled || nav.classList.contains( 'is-ready' ) ) {
				return;
			}

			readyScheduled = true;
			requestNextFrame( function () {
				requestNextFrame( function () {
					nav.classList.add( 'is-ready' );
					readyScheduled = false;
				} );
			} );
		}

		function activateItem( item ) {
			nav.querySelectorAll( '.lightning-child-mobile-fixed-nav__item.is-indicator-active' ).forEach( function ( currentItem ) {
				currentItem.classList.remove( 'is-indicator-active' );
			} );

			if ( ! item ) {
				activeItem = null;
				return;
			}

			item.classList.add( 'is-indicator-active' );
			activeItem = item;
			scheduleReadyState();
		}

		function getLinkAction( link ) {
			const href = ( link.getAttribute( 'href' ) || '' ).trim();
			if ( ! href || '#' === href.charAt( 0 ) || link.classList.contains( 'lightning-child-mobile-fixed-nav__menu-button' ) ) {
				return 'none';
			}

			if ( /^(?:tel|mailto|sms):/i.test( href ) ) {
				return 'feedback';
			}

			let targetUrl;
			try {
				targetUrl = new window.URL( link.href, window.location.href );
			} catch ( error ) {
				return 'none';
			}

			if ( ! /^https?:$/.test( targetUrl.protocol ) ) {
				return 'none';
			}

			return targetUrl.origin === window.location.origin ? 'internal' : 'feedback';
		}

		function showActionFeedback( item ) {
			if ( feedbackTimer ) {
				window.clearTimeout( feedbackTimer );
			}
			if ( feedbackItem ) {
				feedbackItem.classList.remove( 'is-action-feedback' );
			}

			feedbackItem = item;
			feedbackItem.classList.add( 'is-action-feedback' );
			feedbackTimer = window.setTimeout( function () {
				if ( feedbackItem ) {
					feedbackItem.classList.remove( 'is-action-feedback' );
				}
				feedbackItem = null;
				feedbackTimer = null;
			}, 1000 );
		}

		nav.addEventListener( 'click', function ( event ) {
			const link = event.target.closest( '.lightning-child-mobile-fixed-nav__link' );
			if ( ! link || ! nav.contains( link ) ) {
				return;
			}

			const item = link.closest( '.lightning-child-mobile-fixed-nav__item' );
			const action = getLinkAction( link );
			if ( 'internal' === action ) {
				activateItem( item );
			} else if ( 'feedback' === action ) {
				showActionFeedback( item );
			}
		} );

		activateItem( activeItem );
	}

	function initializeMobileFixedNav() {
		initializeMobileMenuDrawer();
		initializeFloatingMobileNav();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initializeMobileFixedNav );
	} else {
		initializeMobileFixedNav();
	}
}() );
