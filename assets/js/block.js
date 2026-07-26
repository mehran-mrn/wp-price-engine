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

	blocks.registerBlockType( 'mrn-price-engine/price-board', {
		edit: function ( props ) {
			const a = props.attributes;
			const controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: i18n.__( 'تنظیمات تابلو', 'mrn-price-engine' ), initialOpen: true },
					el( TextControl, { label: i18n.__( 'عنوان', 'mrn-price-engine' ), value: a.title, onChange: function ( value ) { props.setAttributes( { title: value } ); } } ),
					el( TextControl, { label: i18n.__( 'نمادها', 'mrn-price-engine' ), help: 'GOLD_18K_IRT,USD_IRT,BTC_USD', value: a.assets, onChange: function ( value ) { props.setAttributes( { assets: value } ); } } ),
					el( SelectControl, { label: i18n.__( 'چیدمان', 'mrn-price-engine' ), value: a.layout, options: [ { label: 'کارت', value: 'cards' }, { label: 'جدول', value: 'table' }, { label: 'نوار روان', value: 'ticker' } ], onChange: function ( value ) { props.setAttributes( { layout: value } ); } } ),
					el( SelectControl, { label: i18n.__( 'پوسته', 'mrn-price-engine' ), value: a.theme, options: [ { label: 'خودکار', value: 'auto' }, { label: 'روشن', value: 'light' }, { label: 'تیره', value: 'dark' } ], onChange: function ( value ) { props.setAttributes( { theme: value } ); } } ),
					el( RangeControl, { label: i18n.__( 'تعداد ستون', 'mrn-price-engine' ), value: a.columns, min: 1, max: 6, onChange: function ( value ) { props.setAttributes( { columns: value } ); } } ),
					el( ToggleControl, { label: i18n.__( 'نمایش تغییرات', 'mrn-price-engine' ), checked: a.showChange, onChange: function ( value ) { props.setAttributes( { showChange: value } ); } } ),
					el( ToggleControl, { label: i18n.__( 'نمایش زمان', 'mrn-price-engine' ), checked: a.showUpdated, onChange: function ( value ) { props.setAttributes( { showUpdated: value } ); } } )
				)
			);

			return el( element.Fragment, {}, controls, el( ServerSideRender, { block: 'mrn-price-engine/price-board', attributes: a } ) );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n, window.wp.serverSideRender );
