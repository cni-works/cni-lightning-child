( function() {
	'use strict';

	window.addEventListener( 'DOMContentLoaded', function() {
		// Lightning inserts its accordion controls on DOMContentLoaded. Run after it.
		window.setTimeout( function() {
			const desktopMedia = window.matchMedia(
				'(min-width: 1200px) and (hover: hover) and (pointer: fine)'
			);
			const labels = window.lightningChildMegaMenuL10n || {
				openSubmenu: 'Open submenu',
				closeSubmenu: 'Close submenu',
			};
			let cleanupDesktop = function() {};

			const initializeDesktop = function() {
				if ( ! desktopMedia.matches ) {
					return function() {};
				}

				const parents = document.querySelectorAll(
					'.global-nav-list > .lightning-child-mega-menu-parent'
				);
				const cleanups = [];

				parents.forEach( function( parent, parentIndex ) {
					const panel = parent.querySelector( ':scope > .sub-menu' );
					const childMenus = [];
					let closeTimer = 0;
					let suppressFocusOpen = false;

					if ( ! panel ) {
						return;
					}

					const setChildMenuState = function( entry, isOpen ) {
						entry.item.classList.toggle( 'is-lightning-child-submenu-open', isOpen );
						entry.button.classList.toggle( 'is-open', isOpen );
						entry.button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
						entry.button.setAttribute( 'aria-label', isOpen ? labels.closeSubmenu : labels.openSubmenu );
						entry.submenu.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
					};

					const closeChildMenus = function( exceptEntry ) {
						childMenus.forEach( function( entry ) {
							if ( entry !== exceptEntry ) {
								setChildMenuState( entry, false );
							}
						} );
					};

					panel.querySelectorAll( ':scope > .lightning-child-mega-menu-item-has-children' ).forEach(
						function( item, itemIndex ) {
							const submenu = item.querySelector( ':scope > .sub-menu' );
							if ( ! submenu ) {
								return;
							}

							const originalId = submenu.getAttribute( 'id' );
							const originalAriaHidden = submenu.getAttribute( 'aria-hidden' );
							const submenuId = originalId || 'lightning-child-mega-submenu-' + parentIndex + '-' + itemIndex;
							const button = document.createElement( 'button' );
							button.type = 'button';
							button.className = 'lightning-child-mega-menu__submenu-toggle';
							button.setAttribute( 'aria-controls', submenuId );
							button.innerHTML = '<span aria-hidden="true"></span>';
							submenu.id = submenuId;
							item.insertBefore( button, submenu );

							const entry = {
								item: item,
								button: button,
								submenu: submenu,
							};
							childMenus.push( entry );
							setChildMenuState( entry, false );

							const positionButton = function() {
								const media = item.querySelector( ':scope > a .lightning-child-mega-menu__media' );
								const link = item.querySelector( ':scope > a' );
								const target = media || link;
								if ( ! target ) {
									return;
								}

								const itemRect = item.getBoundingClientRect();
								const targetRect = target.getBoundingClientRect();
								const inset = media ? 12 : 10;
								const top = media
									? targetRect.bottom - itemRect.top - button.offsetHeight - inset
									: targetRect.top - itemRect.top + ( targetRect.height - button.offsetHeight ) / 2;

								button.style.top = Math.max( inset, top ) + 'px';
								button.style.right = inset + 'px';
							};

							const onButtonClick = function( event ) {
								event.preventDefault();
								event.stopPropagation();
								const willOpen = button.getAttribute( 'aria-expanded' ) !== 'true';
								closeChildMenus( entry );
								setChildMenuState( entry, willOpen );
							};

							button.addEventListener( 'click', onButtonClick );
							positionButton();
							window.addEventListener( 'resize', positionButton );

							let observer = null;
							if ( 'ResizeObserver' in window ) {
								observer = new window.ResizeObserver( positionButton );
								observer.observe( item );
							}

							cleanups.push( function() {
								button.removeEventListener( 'click', onButtonClick );
								window.removeEventListener( 'resize', positionButton );
								if ( observer ) {
									observer.disconnect();
								}
								item.classList.remove( 'is-lightning-child-submenu-open' );
								button.remove();
								if ( null === originalId ) {
									submenu.removeAttribute( 'id' );
								} else {
									submenu.setAttribute( 'id', originalId );
								}
								if ( null === originalAriaHidden ) {
									submenu.removeAttribute( 'aria-hidden' );
								} else {
									submenu.setAttribute( 'aria-hidden', originalAriaHidden );
								}
							} );
						}
					);

					const cancelClose = function() {
						window.clearTimeout( closeTimer );
						closeTimer = 0;
					};

					const openMenu = function() {
						cancelClose();
						parents.forEach( function( otherParent ) {
							if ( otherParent !== parent ) {
								otherParent.classList.remove( 'is-mega-menu-open' );
							}
						} );
						parent.classList.add( 'is-mega-menu-open' );
					};

					const scheduleClose = function() {
						cancelClose();
						closeTimer = window.setTimeout( function() {
							if ( ! parent.matches( ':hover' ) && ! parent.contains( document.activeElement ) ) {
								parent.classList.remove( 'is-mega-menu-open' );
								closeChildMenus();
							}
						}, 320 );
					};

					const onFocusIn = function() {
						if ( ! suppressFocusOpen ) {
							openMenu();
						}
					};

					const onKeydown = function( event ) {
						if ( event.key !== 'Escape' ) {
							return;
						}

						event.preventDefault();
						const openChild = childMenus.find( function( entry ) {
							return entry.button.getAttribute( 'aria-expanded' ) === 'true';
						} );
						if ( openChild ) {
							setChildMenuState( openChild, false );
							openChild.button.focus();
							return;
						}

						cancelClose();
						closeChildMenus();
						suppressFocusOpen = true;
						const link = parent.querySelector( ':scope > a' );
						if ( link ) {
							link.focus();
						}
						parent.classList.remove( 'is-mega-menu-open' );
						suppressFocusOpen = false;
					};

					parent.addEventListener( 'pointerenter', openMenu );
					parent.addEventListener( 'pointerleave', scheduleClose );
					panel.addEventListener( 'pointerenter', openMenu );
					panel.addEventListener( 'pointerleave', scheduleClose );
					parent.addEventListener( 'focusin', onFocusIn );
					parent.addEventListener( 'focusout', scheduleClose );
					parent.addEventListener( 'keydown', onKeydown );

					cleanups.push( function() {
						cancelClose();
						parent.classList.remove( 'is-mega-menu-open' );
						parent.removeEventListener( 'pointerenter', openMenu );
						parent.removeEventListener( 'pointerleave', scheduleClose );
						panel.removeEventListener( 'pointerenter', openMenu );
						panel.removeEventListener( 'pointerleave', scheduleClose );
						parent.removeEventListener( 'focusin', onFocusIn );
						parent.removeEventListener( 'focusout', scheduleClose );
						parent.removeEventListener( 'keydown', onKeydown );
					} );
				} );

				return function() {
					cleanups.slice().reverse().forEach( function( cleanup ) {
						cleanup();
					} );
				};
			};

			const refreshForMedia = function() {
				cleanupDesktop();
				cleanupDesktop = initializeDesktop();
			};

			refreshForMedia();
			if ( 'addEventListener' in desktopMedia ) {
				desktopMedia.addEventListener( 'change', refreshForMedia );
			} else if ( 'addListener' in desktopMedia ) {
				desktopMedia.addListener( refreshForMedia );
			}
		}, 0 );
	} );
} )();
