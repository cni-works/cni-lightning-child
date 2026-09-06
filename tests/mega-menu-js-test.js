'use strict';

const fs = require( 'fs' );
const path = require( 'path' );
const vm = require( 'vm' );

const scriptSource = fs.readFileSync(
	path.join( __dirname, '..', 'assets', 'js', 'mega-menu.js' ),
	'utf8'
);
const cssSource = fs.readFileSync(
	path.join( __dirname, '..', 'assets', 'css', 'mega-menu.css' ),
	'utf8'
);

function assert( condition, message ) {
	if ( ! condition ) {
		throw new Error( message );
	}
}

class FakeClassList {
	constructor() {
		this.values = new Set();
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
}

class FakeElement {
	constructor() {
		this.classList = new FakeClassList();
		this.listeners = new Map();
	}

	addEventListener( name, callback ) {
		if ( ! this.listeners.has( name ) ) {
			this.listeners.set( name, [] );
		}
		this.listeners.get( name ).push( callback );
	}

	removeEventListener( name, callback ) {
		const listeners = this.listeners.get( name ) || [];
		this.listeners.set( name, listeners.filter( ( listener ) => listener !== callback ) );
	}

	dispatch( name, event = {} ) {
		( this.listeners.get( name ) || [] ).slice().forEach( ( listener ) => listener( event ) );
	}

	matches() {
		return false;
	}

	contains() {
		return false;
	}
}

const parent = new FakeElement();
const panel = new FakeElement();
const link = new FakeElement();
const documentEvents = {};
let parentQueries = 0;

panel.querySelectorAll = () => [];
parent.querySelector = ( selector ) => ':scope > .sub-menu' === selector ? panel : link;

const document = {
	activeElement: null,
	addEventListener: ( name, callback ) => {
		documentEvents[ name ] = callback;
	},
	querySelectorAll: () => {
		parentQueries++;
		return [ parent ];
	},
};

link.focus = () => {
	document.activeElement = link;
	parent.dispatch( 'focusin' );
};

const mediaListeners = [];
const desktopMedia = {
	matches: false,
	addEventListener: ( name, callback ) => {
		if ( 'change' === name ) {
			mediaListeners.push( callback );
		}
	},
};
const window = {
	addEventListener: ( name, callback ) => {
		if ( 'DOMContentLoaded' === name ) {
			documentEvents[ name ] = callback;
		}
	},
	clearTimeout: () => {},
	matchMedia: () => desktopMedia,
	removeEventListener: () => {},
	setTimeout: ( callback ) => {
		callback();
		return 1;
	},
};

vm.runInNewContext( scriptSource, { document, window } );
documentEvents.DOMContentLoaded();

assert( 0 === parentQueries, 'Touch/mobile conditions must not initialize or mutate the mega menu.' );

desktopMedia.matches = true;
mediaListeners[ 0 ]();
assert( 1 === parentQueries, 'Desktop fine-pointer conditions must initialize the mega menu.' );

parent.dispatch( 'focusin' );
assert( parent.classList.contains( 'is-mega-menu-open' ), 'Focus must open the desktop mega menu.' );

parent.dispatch( 'keydown', {
	key: 'Escape',
	preventDefault: () => {},
} );
assert( ! parent.classList.contains( 'is-mega-menu-open' ), 'Escape must keep the menu closed after focus returns to its parent link.' );

parent.dispatch( 'focusin' );
desktopMedia.matches = false;
mediaListeners[ 0 ]();
assert( ! parent.classList.contains( 'is-mega-menu-open' ), 'Leaving desktop conditions must remove the custom open state.' );
assert( 0 === ( parent.listeners.get( 'focusin' ) || [] ).length, 'Leaving desktop conditions must remove custom event listeners.' );

assert( ! scriptSource.includes( 'lightningButton.remove' ), 'The script must preserve Lightning accordion controls.' );
assert( ! scriptSource.includes( "classList.remove( 'acc-child-open'" ), 'The script must preserve Lightning accordion state classes.' );
assert( ! cssSource.includes( 'lightning-child-mega-menu-parent:hover > .sub-menu' ), 'Hover CSS must not bypass the JavaScript open state.' );
assert( ! cssSource.includes( 'lightning-child-mega-menu-parent:focus-within > .sub-menu' ), 'Focus-within CSS must not reopen a menu closed with Escape.' );

console.log( 'Mega menu JavaScript tests passed.' );
