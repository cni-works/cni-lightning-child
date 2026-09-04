'use strict';

const fs = require( 'fs' );
const path = require( 'path' );
const vm = require( 'vm' );

const source = fs.readFileSync(
	path.join( __dirname, '..', 'assets', 'js', 'mobile-fixed-nav.js' ),
	'utf8'
);

function assert( condition, message ) {
	if ( ! condition ) {
		throw new Error( message );
	}
}

class FakeClassList {
	constructor( names = [] ) {
		this.values = new Set( names );
	}

	add( ...names ) {
		names.forEach( ( name ) => this.values.add( name ) );
	}

	remove( ...names ) {
		names.forEach( ( name ) => this.values.delete( name ) );
	}

	contains( name ) {
		return this.values.has( name );
	}

	[ Symbol.iterator ]() {
		return this.values[ Symbol.iterator ]();
	}
}

class FakeElement {
	constructor( classes, rect = {} ) {
		this.classList = new FakeClassList( classes );
		this.rect = Object.assign( { left: 0, width: 0 }, rect );
		this.children = [];
		this.parentItem = null;
		this.events = {};
		this.attributes = {};
		this.href = '';
		this.style = {};
		this.hidden = false;
		this.textContent = '';
	}

	addEventListener( name, callback ) {
		this.events[ name ] = callback;
	}

	getBoundingClientRect() {
		return this.rect;
	}

	getAttribute( name ) {
		return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[ name ] : null;
	}

	contains( element ) {
		return this === element || this.children.includes( element ) || this.children.some( ( child ) => child.children.includes( element ) );
	}

	closest( selector ) {
		if ( '.lightning-child-mobile-fixed-nav__link' === selector && this.classList.contains( 'lightning-child-mobile-fixed-nav__link' ) ) {
			return this;
		}
		if ( '.lightning-child-mobile-fixed-nav__item' === selector ) {
			return this.classList.contains( 'lightning-child-mobile-fixed-nav__item' ) ? this : this.parentItem;
		}
		return null;
	}

	querySelector( selector ) {
		if ( '.lightning-child-mobile-fixed-nav__item.is-current' === selector ) {
			return this.children.find( ( child ) => child.classList.contains( 'lightning-child-mobile-fixed-nav__item' ) && child.classList.contains( 'is-current' ) ) || null;
		}
		if ( '.' === selector.charAt( 0 ) ) {
			const className = selector.slice( 1 );
			for ( const child of this.children ) {
				if ( child.classList.contains( className ) ) {
					return child;
				}
				const match = child.querySelector( selector );
				if ( match ) {
					return match;
				}
			}
		}
		return null;
	}

	querySelectorAll( selector ) {
		if ( '.lightning-child-mobile-fixed-nav__item.is-indicator-active' === selector ) {
			return this.children.filter( ( child ) => child.classList.contains( 'lightning-child-mobile-fixed-nav__item' ) && child.classList.contains( 'is-indicator-active' ) );
		}
		return [];
	}
}

function createLink( href, extraClasses = [], iconClass = 'fa-circle', labelText = 'Label' ) {
	const link = new FakeElement( [ 'lightning-child-mobile-fixed-nav__link', ...extraClasses ] );
	const icon = new FakeElement( [ 'lightning-child-mobile-fixed-nav__icon', 'fa-solid', iconClass ] );
	const label = new FakeElement( [ 'lightning-child-mobile-fixed-nav__label' ] );
	label.textContent = labelText;
	link.attributes.href = href;
	link.href = href.startsWith( 'http' ) ? href : new URL( href, 'https://example.test/current/' ).href;
	link.children.push( icon );
	link.children.push( label );
	return link;
}

function runScript() {
	const nav = new FakeElement( [ 'lightning-child-mobile-fixed-nav--floating' ], { left: 0, width: 400 } );

	const links = [
		createLink( '/first/', [], 'fa-one', 'FIRST' ),
		createLink( '/current/', [], 'fa-two', 'CURRENT' ),
		createLink( '/third/', [], 'fa-three', 'THIRD' ),
		createLink( 'tel:0000000000', [], 'fa-phone', 'TEL' ),
		createLink( 'https://external.test/', [], 'fa-external-link', 'EXTERNAL' ),
		createLink( '#drawer', [ 'lightning-child-mobile-fixed-nav__menu-button' ], 'fa-bars', 'MENU' ),
	];
	const items = links.map( ( link, index ) => {
		const classes = [ 'lightning-child-mobile-fixed-nav__item' ];
		if ( 1 === index ) {
			classes.push( 'is-current' );
		}
		const item = new FakeElement( classes, { left: index * 100, width: 100 } );
		link.parentItem = item;
		item.children.push( link );
		nav.children.push( item );
		return item;
	} );

	const frames = [];
	const timeouts = [];
	const windowEvents = {};
	const window = {
		URL,
		location: {
			href: 'https://example.test/current/',
			origin: 'https://example.test',
		},
		matchMedia: () => ( { matches: true, addEventListener: () => {} } ),
		requestAnimationFrame: ( callback ) => {
			frames.push( callback );
			return frames.length;
		},
		setTimeout: ( callback ) => {
			timeouts.push( callback );
			return timeouts.length;
		},
		clearTimeout: () => {},
		addEventListener: ( name, callback ) => {
			windowEvents[ name ] = callback;
		},
	};
	const document = {
		readyState: 'complete',
		querySelector: ( selector ) => '.lightning-child-mobile-fixed-nav--floating' === selector ? nav : null,
		getElementById: () => null,
		addEventListener: () => {},
	};

	vm.runInNewContext( source, { window, document } );

	function flushFrames() {
		while ( frames.length ) {
			const callbacks = frames.splice( 0 );
			callbacks.forEach( ( callback ) => callback() );
		}
	}

	return { nav, items, links, timeouts, windowEvents, flushFrames };
}

const test = runScript();
assert( test.items[ 1 ].classList.contains( 'is-indicator-active' ), 'The PHP-provided is-current item must become the visual indicator item.' );
assert( ! test.nav.classList.contains( 'is-ready' ), 'The navigation must not animate before the initial placement frames finish.' );
test.flushFrames();
assert( test.nav.classList.contains( 'is-ready' ), 'The navigation must become ready only after its initial position is applied.' );

test.nav.events.click( { target: test.links[ 2 ] } );
assert( ! test.items[ 1 ].classList.contains( 'is-indicator-active' ), 'The former active item must return to its inactive state.' );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'An internal page link must activate its own stationary indicator.' );

test.nav.events.click( { target: test.links[ 3 ] } );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'A telephone link must not change the active page item.' );
assert( test.items[ 3 ].classList.contains( 'is-action-feedback' ), 'A telephone link must receive temporary action feedback.' );

test.links[ 3 ].attributes.href = 'mailto:test@example.test';
test.links[ 3 ].href = 'mailto:test@example.test';
test.nav.events.click( { target: test.links[ 3 ] } );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'An email link must not change the active page item.' );
assert( test.items[ 3 ].classList.contains( 'is-action-feedback' ), 'An email link must receive temporary action feedback.' );

test.links[ 3 ].attributes.href = 'sms:0000000000';
test.links[ 3 ].href = 'sms:0000000000';
test.nav.events.click( { target: test.links[ 3 ] } );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'An SMS link must not change the active page item.' );
assert( test.items[ 3 ].classList.contains( 'is-action-feedback' ), 'An SMS link must receive temporary action feedback.' );

test.nav.events.click( { target: test.links[ 4 ] } );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'An external link must not change the active page item.' );
assert( test.items[ 4 ].classList.contains( 'is-action-feedback' ), 'An external link must receive temporary action feedback.' );

test.nav.events.click( { target: test.links[ 5 ] } );
assert( test.items[ 2 ].classList.contains( 'is-indicator-active' ), 'The hamburger button must not change the active page item.' );
assert( ! test.items[ 5 ].classList.contains( 'is-action-feedback' ), 'The hamburger button must keep its existing open-state feedback.' );

const latestFeedbackTimeout = test.timeouts[ test.timeouts.length - 1 ];
latestFeedbackTimeout();
assert( ! test.items[ 4 ].classList.contains( 'is-action-feedback' ), 'Temporary action feedback must be removed automatically.' );

assert( ! /preventDefault\(\)/.test( source.slice( source.indexOf( 'function initializeFloatingMobileNav' ) ) ), 'Floating navigation must not prevent the normal link action.' );
assert( ! /getBoundingClientRect/.test( source.slice( source.indexOf( 'function initializeFloatingMobileNav' ) ) ), 'Floating navigation must not calculate horizontal indicator positions.' );

console.log( 'Mobile fixed navigation JavaScript tests passed.' );
