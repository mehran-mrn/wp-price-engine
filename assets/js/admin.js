( function () {
	'use strict';

	const config = window.mrnpe_admin || {};
	const $ = function ( selector, root ) {
		return ( root || document ).querySelector( selector );
	};
	const $$ = function ( selector, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( selector ) );
	};

	function openAssetModal() {
		const modal = $( '[data-mrnpe-asset-modal]' );
		if ( modal ) {
			modal.classList.add( 'is-open' );
			document.body.classList.add( 'mrnpe-modal-open' );
		}
	}

	function closeAssetModal() {
		const modal = $( '[data-mrnpe-asset-modal]' );
		if ( modal ) {
			modal.classList.remove( 'is-open' );
			document.body.classList.remove( 'mrnpe-modal-open' );
		}
	}

	$$ ( '[data-mrnpe-open-asset]' ).forEach( function ( button ) {
		button.addEventListener( 'click', openAssetModal );
	} );
	$$ ( '[data-mrnpe-close-asset]' ).forEach( function ( button ) {
		button.addEventListener( 'click', closeAssetModal );
	} );
	if ( $( '[data-mrnpe-asset-modal].is-open' ) ) {
		document.body.classList.add( 'mrnpe-modal-open' );
	}

	$$ ( '[data-mrnpe-delete]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			if ( ! window.confirm( config.confirmDelete || 'Delete?' ) ) {
				event.preventDefault();
			}
		} );
	} );

	const tableSearch = $( '[data-mrnpe-table-search]' );
	if ( tableSearch ) {
		tableSearch.addEventListener( 'input', function () {
			const query = tableSearch.value.trim().toLocaleLowerCase();
			$$ ( '[data-mrnpe-search]' ).forEach( function ( row ) {
				row.hidden = query && ! row.dataset.mrnpeSearch.includes( query );
			} );
		} );
	}

	$$ ( '.mrnpe-source .mrnpe-switch input' ).forEach( function ( input ) {
		input.addEventListener( 'change', function () {
			input.closest( '.mrnpe-source' ).classList.toggle( 'is-enabled', input.checked );
		} );
	} );

	const sourceSearch = $( '[data-mrnpe-source-search]' );
	const sourceFilters = $( '[data-mrnpe-source-filters]' );
	if ( sourceSearch && sourceFilters ) {
		let activeTier = 'all';
		const filterSources = function () {
			const query = sourceSearch.value.trim().toLocaleLowerCase();
			$$ ( '.mrnpe-source[data-provider-tier]' ).forEach( function ( card ) {
				const matchesTier = activeTier === 'all' || card.dataset.providerTier === activeTier;
				const haystack = ( card.dataset.providerSearch || '' ).toLocaleLowerCase();
				card.hidden = ! matchesTier || ( query && ! haystack.includes( query ) );
			} );
		};
		sourceSearch.addEventListener( 'input', filterSources );
		$$ ( '[data-tier]', sourceFilters ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				activeTier = button.dataset.tier;
				$$ ( '[data-tier]', sourceFilters ).forEach( function ( item ) {
					item.classList.toggle( 'is-active', item === button );
				} );
				filterSources();
			} );
		} );
	}

	const builder = $( '.mrnpe-builder' );
	if ( builder ) {
		const output = $( '[data-shortcode-output]', builder );
		const update = function () {
			const pairs = $$ ( '[data-shortcode-field]', builder ).map( function ( field ) {
				return field.dataset.shortcodeField + '="' + field.value.replace( /"/g, '&quot;' ) + '"';
			} );
			output.textContent = '[mrnpe_price_board ' + pairs.join( ' ' ) + ']';
		};
		$$ ( '[data-shortcode-field]', builder ).forEach( function ( field ) {
			field.addEventListener( 'input', update );
			field.addEventListener( 'change', update );
		} );
		const copy = $( '[data-copy-shortcode]', builder );
		copy.addEventListener( 'click', async function () {
			await navigator.clipboard.writeText( output.textContent );
			const original = copy.textContent;
			copy.textContent = config.copied || 'Copied';
			window.setTimeout( function () { copy.textContent = original; }, 1400 );
		} );
		update();
	}

	function updateFormulaPreview( root ) {
		const preview = $( '[data-mrnpe-preview]', root );
		if ( ! preview ) {
			return;
		}
		const labels = config.previewLabels || {};
		const caption = $( 'span', preview );
		const output = $( 'strong', preview );
		const assetId = $( '#_mrnpe_asset_id', root );
		const quantity = $( '#_mrnpe_quantity', root );
		const multiplier = $( '#_mrnpe_multiplier', root );
		const percentage = $( '#_mrnpe_percentage', root );
		const fixed = $( '#_mrnpe_fixed', root );
		const asset = assetId && config.assets ? config.assets[ assetId.value ] : null;
		if ( ! asset || asset.value === null ) {
			caption.textContent = labels.converted || 'پیش‌نمایش فرمول';
			output.textContent = labels.missing || 'نرخ معتبر انتخاب نشده است';
			return;
		}
		if ( asset.localValue === null ) {
			caption.textContent = asset.currency + ' → ' + ( config.currencyCode || '' );
			output.textContent = asset.conversionError || labels.missing || 'نرخ تبدیل در دسترس نیست';
			return;
		}
		const material = Number( asset.localValue ) * Number( quantity.value || 0 ) * Number( multiplier.value || 0 );
		const price = material + material * Number( percentage.value || 0 ) / 100 + Number( fixed.value || 0 );
		caption.textContent = ( labels.path || 'مسیر تبدیل' ) + ': ' + asset.conversionPath;
		output.textContent = new Intl.NumberFormat( config.locale || 'fa-IR', { maximumFractionDigits: 0 } ).format( price ) + ' ' + config.currency;
	}

	const productPanel = $( '#mrnpe_product_data' );
	if ( productPanel ) {
		$$ ( 'input, select', productPanel ).forEach( function ( field ) {
			field.addEventListener( 'input', function () { updateFormulaPreview( productPanel ); } );
			field.addEventListener( 'change', function () { updateFormulaPreview( productPanel ); } );
		} );
		updateFormulaPreview( productPanel );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) {
			closeAssetModal();
		}
	} );
} )();
