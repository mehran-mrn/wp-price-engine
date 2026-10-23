( function ( blocks, element, components, blockEditor, i18n, serverSideRender ) {
	'use strict';

	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const SelectControl = components.SelectControl;
	const RangeControl = components.RangeControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'mrnpe/price-board', {
		icon: el( 'img', { src: window.mrnpe_block.logoUrl, alt: '', width: 20, height: 20 } ),
		edit: function ( props ) {
			const a = props.attributes;
			const controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: i18n.__( 'تنظیمات تابلو', 'mrn-gold-currency-pricing' ), initialOpen: true },
					el( TextControl, { label: i18n.__( 'عنوان', 'mrn-gold-currency-pricing' ), value: a.title, onChange: function ( value ) { props.setAttributes( { title: value } ); } } ),
					el( TextControl, { label: i18n.__( 'نمادها', 'mrn-gold-currency-pricing' ), help: 'GOLD_18K_IRT,USD_IRT,BTC_USD', value: a.assets, onChange: function ( value ) { props.setAttributes( { assets: value } ); } } ),
					el( SelectControl, { label: i18n.__( 'چیدمان', 'mrn-gold-currency-pricing' ), value: a.layout, options: [ { label: 'کارت', value: 'cards' }, { label: 'جدول', value: 'table' }, { label: 'نوار روان', value: 'ticker' } ], onChange: function ( value ) { props.setAttributes( { layout: value } ); } } ),
					el( SelectControl, { label: i18n.__( 'پوسته', 'mrn-gold-currency-pricing' ), value: a.theme, options: [ { label: 'خودکار', value: 'auto' }, { label: 'روشن', value: 'light' }, { label: 'تیره', value: 'dark' } ], onChange: function ( value ) { props.setAttributes( { theme: value } ); } } ),
					el( RangeControl, { label: i18n.__( 'تعداد ستون', 'mrn-gold-currency-pricing' ), value: a.columns, min: 1, max: 6, onChange: function ( value ) { props.setAttributes( { columns: value } ); } } ),
					el( ToggleControl, { label: i18n.__( 'نمایش تغییرات', 'mrn-gold-currency-pricing' ), checked: a.showChange, onChange: function ( value ) { props.setAttributes( { showChange: value } ); } } ),
					el( ToggleControl, { label: i18n.__( 'نمایش زمان', 'mrn-gold-currency-pricing' ), checked: a.showUpdated, onChange: function ( value ) { props.setAttributes( { showUpdated: value } ); } } )
				)
			);

			return el( element.Fragment, {}, controls, el( ServerSideRender, { block: 'mrnpe/price-board', attributes: a } ) );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n, window.wp.serverSideRender );
