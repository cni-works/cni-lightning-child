'use strict';

const fs = require( 'fs' );
const vm = require( 'vm' );
const path = require( 'path' );

const source = fs.readFileSync(
	path.join( __dirname, '..', 'assets', 'js', 'admin-site-functions-guidance.js' ),
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

	contains( name ) {
		return this.values.has( name );
	}
}

class FakeElement {
	constructor( tagName ) {
		this.tagName = tagName;
		this.children = [];
		this.parentNode = null;
		this.previousElementSibling = null;
		this.attributes = {};
		this.events = {};
		this.classList = new FakeClassList();
		this.textContent = '';
	}

	set className( value ) {
		this._className = value;
		this.classList = new FakeClassList();
		String( value ).split( /\s+/ ).filter( Boolean ).forEach( ( name ) => this.classList.add( name ) );
	}

	get className() {
		return this._className || '';
	}

	appendChild( child ) {
		if ( child.parentNode ) {
			const oldIndex = child.parentNode.children.indexOf( child );
			if ( oldIndex >= 0 ) {
				child.parentNode.children.splice( oldIndex, 1 );
			}
		}
		child.previousElementSibling = this.children.length ? this.children[ this.children.length - 1 ] : null;
		child.parentNode = this;
		this.children.push( child );
		return child;
	}

	insertBefore( child, reference ) {
		const index = this.children.indexOf( reference );
		if ( index < 0 ) {
			return this.appendChild( child );
		}
		child.parentNode = this;
		child.previousElementSibling = index ? this.children[ index - 1 ] : null;
		this.children.splice( index, 0, child );
		reference.previousElementSibling = child;
		return child;
	}

	setAttribute( name, value ) {
		this.attributes[ name ] = value;
	}

	addEventListener( name, callback ) {
		this.events[ name ] = callback;
	}

	remove() {
		if ( ! this.parentNode ) {
			return;
		}
		const index = this.parentNode.children.indexOf( this );
		if ( index >= 0 ) {
			this.parentNode.children.splice( index, 1 );
		}
		this.parentNode = null;
	}

	querySelector( selector ) {
		for ( const child of this.children ) {
			if ( selector === '.CodeMirror' && child.classList.contains( 'CodeMirror' ) ) {
				return child;
			}
			if ( selector === '[data-cni-site-functions-overlay]' && Object.prototype.hasOwnProperty.call( child.attributes, 'data-cni-site-functions-overlay' ) ) {
				return child;
			}
			const nested = child.querySelector( selector );
			if ( nested ) {
				return nested;
			}
		}
		return null;
	}

	findByClass( className ) {
		if ( this.classList.contains( className ) ) {
			return this;
		}
		for ( const child of this.children ) {
			const nested = child.findByClass( className );
			if ( nested ) {
				return nested;
			}
		}
		return null;
	}
}

function runGuidance( options = {} ) {
	const parent = new FakeElement( 'div' );
	const textarea = options.missingEditor ? null : new FakeElement( 'textarea' );
	const codeMirror = options.withCodeMirror === false ? null : new FakeElement( 'div' );

	if ( codeMirror ) {
		codeMirror.classList.add( 'CodeMirror' );
		parent.appendChild( codeMirror );
	}
	if ( textarea ) {
		parent.appendChild( textarea );
	}

	const document = {
		readyState: 'complete',
		getElementById: ( id ) => id === 'newcontent' ? textarea : null,
		createElement: ( tagName ) => new FakeElement( tagName ),
		addEventListener: () => {},
	};
	const window = {
		cniLightningChildSiteFunctionsGuidance: {
			heading: 'Site Functions',
			description: 'Use the plugin.',
			primaryLabel: 'Open',
			primaryUrl: 'https://example.test/wp-admin/options-general.php?page=cni-site-functions',
			revealLabel: 'Reveal functions.php',
		},
		setTimeout: ( callback ) => callback(),
	};

	vm.runInNewContext( source, { window, document } );
	return { parent, textarea, codeMirror };
}

const firstLoad = runGuidance();
const firstOverlay = firstLoad.codeMirror.querySelector( '[data-cni-site-functions-overlay]' );
assert( firstOverlay, 'The overlay must cover the available CodeMirror host.' );
const revealButton = firstOverlay.findByClass( 'cni-site-functions-editor-overlay__reveal' );
assert( revealButton && revealButton.events.click, 'The reveal control must be clickable.' );
revealButton.events.click();
assert( ! firstLoad.codeMirror.querySelector( '[data-cni-site-functions-overlay]' ), 'The reveal control must remove only the overlay.' );

const reload = runGuidance();
assert( reload.codeMirror.querySelector( '[data-cni-site-functions-overlay]' ), 'A fresh page load must show the overlay again.' );

const fallback = runGuidance( { withCodeMirror: false } );
assert( fallback.parent.querySelector( '[data-cni-site-functions-overlay]' ), 'A textarea fallback must work when CodeMirror is unavailable.' );

runGuidance( { missingEditor: true } );
assert( ! /localStorage|sessionStorage/.test( source ), 'Overlay dismissal must not be persisted.' );

console.log( 'CNI Site Functions guidance JavaScript tests passed.' );
