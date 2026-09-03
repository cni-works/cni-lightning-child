( function () {
	'use strict';

	var config = window.cniLightningChildSiteFunctionsGuidance;
	var maximumAttempts = 20;

	if ( ! config || typeof config !== 'object' ) {
		return;
	}

	function makeElement( tagName, className, text ) {
		var element = document.createElement( tagName );
		element.className = className;
		if ( typeof text === 'string' ) {
			element.textContent = text;
		}
		return element;
	}

	function findCodeMirror( textarea ) {
		if ( textarea.previousElementSibling && textarea.previousElementSibling.classList && textarea.previousElementSibling.classList.contains( 'CodeMirror' ) ) {
			return textarea.previousElementSibling;
		}

		if ( textarea.parentNode && typeof textarea.parentNode.querySelector === 'function' ) {
			return textarea.parentNode.querySelector( '.CodeMirror' );
		}

		return null;
	}

	function createFallbackHost( textarea ) {
		var parent = textarea.parentNode;
		if ( ! parent || typeof parent.insertBefore !== 'function' ) {
			return null;
		}

		var wrapper = makeElement( 'div', 'cni-site-functions-editor-host cni-site-functions-editor-host--fallback' );
		parent.insertBefore( wrapper, textarea );
		wrapper.appendChild( textarea );
		return wrapper;
	}

	function addOverlay( host ) {
		if ( ! host || typeof host.querySelector !== 'function' || host.querySelector( '[data-cni-site-functions-overlay]' ) ) {
			return;
		}

		host.classList.add( 'cni-site-functions-editor-host' );

		var overlay = makeElement( 'div', 'cni-site-functions-editor-overlay' );
		overlay.setAttribute( 'data-cni-site-functions-overlay', '' );
		overlay.setAttribute( 'role', 'region' );
		overlay.setAttribute( 'aria-label', config.heading || '' );

		var content = makeElement( 'div', 'cni-site-functions-editor-overlay__content' );
		var heading = makeElement( 'h2', 'cni-site-functions-editor-overlay__heading', config.heading || '' );
		var description = makeElement( 'p', 'cni-site-functions-editor-overlay__description', config.description || '' );
		var primary = makeElement( 'a', 'button button-primary', config.primaryLabel || '' );
		primary.href = config.primaryUrl || '#';

		var reveal = makeElement( 'button', 'cni-site-functions-editor-overlay__reveal', config.revealLabel || '' );
		reveal.type = 'button';
		reveal.addEventListener( 'click', function () {
			overlay.remove();
		} );

		content.appendChild( heading );
		content.appendChild( description );
		content.appendChild( primary );
		content.appendChild( reveal );
		overlay.appendChild( content );
		host.appendChild( overlay );
	}

	function mountOverlay( attempt ) {
		var textarea = document.getElementById( 'newcontent' );
		if ( ! textarea ) {
			return;
		}

		var codeMirror = findCodeMirror( textarea );
		if ( codeMirror ) {
			addOverlay( codeMirror );
			return;
		}

		if ( attempt < maximumAttempts ) {
			window.setTimeout( function () {
				mountOverlay( attempt + 1 );
			}, 100 );
			return;
		}

		addOverlay( createFallbackHost( textarea ) );
	}

	function initialize() {
		mountOverlay( 0 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
}() );
