( function( customize ) {
	'use strict';

	customize.bind( 'ready', function() {
		const settingId = 'lightning_child_mega_menu_width';
		const control = customize.control( settingId );
		const setting = customize( settingId );

		if ( ! control || ! setting ) {
			return;
		}

		const input = control.container.find( 'input[type="range"]' ).get( 0 );
		if ( ! input ) {
			return;
		}

		const output = document.createElement( 'output' );
		output.className = 'lightning-child-customize-range-value';
		output.setAttribute( 'for', input.id );
		output.setAttribute( 'aria-live', 'polite' );
		input.insertAdjacentElement( 'afterend', output );

		const updateValue = function( value ) {
			output.textContent = value + '%';
		};

		input.addEventListener( 'input', function() {
			updateValue( input.value );
		} );
		setting.bind( updateValue );
		updateValue( setting.get() );
	} );
} )( wp.customize );
