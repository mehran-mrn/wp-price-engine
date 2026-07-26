( function () {
	'use strict';

	const config = window.mrnpePublic || {};
	const locale = config.locale || document.documentElement.lang || 'fa-IR';

	function formatNumber( value, decimals ) {
		return new Intl.NumberFormat( locale, {
			minimumFractionDigits: decimals,
			maximumFractionDigits: decimals
		} ).format( value );
	}

	function relativeTime( timestamp ) {
		if ( ! timestamp ) {
			return '—';
		}
		const seconds = Math.max( 0, Math.round( Date.now() / 1000 - timestamp ) );
		const formatter = new Intl.RelativeTimeFormat( locale, { numeric: 'auto' } );
		if ( seconds < 60 ) {
			return formatter.format( -seconds, 'second' );
		}
		if ( seconds < 3600 ) {
			return formatter.format( -Math.round( seconds / 60 ), 'minute' );
		}
		if ( seconds < 86400 ) {
			return formatter.format( -Math.round( seconds / 3600 ), 'hour' );
		}
		return formatter.format( -Math.round( seconds / 86400 ), 'day' );
	}

	function updateBoard( board, rates ) {
		const decimals = Number.parseInt( board.dataset.decimals || '0', 10 );
		rates.forEach( function ( rate ) {
			const row = board.querySelector( '[data-mrnpe-asset="' + window.CSS.escape( rate.symbol ) + '"]' );
			if ( ! row ) {
				return;
			}

			const value = row.querySelector( '[data-mrnpe-value]' );
			const change = row.querySelector( '[data-mrnpe-change]' );
			const time = row.querySelector( '[data-mrnpe-time]' );
			if ( value && rate.value !== null ) {
				value.textContent = formatNumber( rate.value, decimals );
			}
			if ( change ) {
				const direction = rate.change_percent > 0 ? 'up' : rate.change_percent < 0 ? 'down' : 'flat';
				change.className = 'mrnpe-change mrnpe-change--' + direction;
				change.textContent = formatNumber( Math.abs( rate.change_percent ), 2 ) + '%';
			}
			if ( time ) {
				time.textContent = relativeTime( rate.updated_at );
				time.dateTime = rate.updated_at ? new Date( rate.updated_at * 1000 ).toISOString() : '';
			}
			row.classList.toggle( 'is-stale', rate.status === 'stale' || rate.status === 'error' );
		} );

		const status = board.querySelector( '[data-mrnpe-status]' );
		if ( status ) {
			status.textContent = config.labels && config.labels.updated ? config.labels.updated : 'Updated';
		}
	}

	async function refresh( board ) {
		const symbols = board.dataset.symbols || '';
		try {
			const response = await window.fetch( config.endpoint + '?symbols=' + encodeURIComponent( symbols ), {
				headers: { Accept: 'application/json' },
				credentials: 'same-origin'
			} );
			if ( ! response.ok ) {
				throw new Error( String( response.status ) );
			}
			const payload = await response.json();
			updateBoard( board, payload.data || [] );
		} catch ( error ) {
			board.classList.add( 'has-refresh-error' );
			const status = board.querySelector( '[data-mrnpe-status]' );
			if ( status ) {
				status.textContent = config.labels && config.labels.error ? config.labels.error : 'Could not refresh';
			}
		}
	}

	document.querySelectorAll( '[data-mrnpe-board]' ).forEach( function ( board ) {
		const interval = Number.parseInt( board.dataset.refresh || '0', 10 );
		if ( interval >= 20 ) {
			window.setInterval( function () {
				if ( ! document.hidden ) {
					refresh( board );
				}
			}, interval * 1000 );
		}
	} );
} )();
