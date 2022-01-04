/**
 * Register wc-donation-platform/wcdp Gutenberg block
 */

import { registerBlockType } from '@wordpress/blocks';

const {__} = wp.i18n; //translation functions
const {
	InspectorControls,
} = wp.blockEditor;
const {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
} = wp.components;
const { Fragment } = wp.element;
import {useBlockProps} from "@wordpress/block-editor";

/**
 * Register Gutenberg Block
 */
registerBlockType( 'wc-donation-platform/wcdp', {
	attributes: {
		id: {
			default: 1,
		},
		style: {
			default: '1'
		},
		popup: {
			default: false
		},
		button: {
			default: true
		},
		title: {
			default: false
		},
		description: {
			default: false
		},
		short_description: {
			default: false
		},
		image: {
			default: false
		}
	},

	//Edit Function
	edit(props){
		const {
			attributes,
			className,
			setAttributes,
		} = props;
		const { id, style, popup, button, title, description, short_description, image} = attributes;

		function changeId( newId ) {
			setAttributes( { id: newId } );
		}
		function changeStyle( newStyle ) {
			setAttributes( { style: newStyle } );
		}
		function changePopup( newPopup ) {
			setAttributes( { popup: newPopup } );
		}
		function changeButton( newButton ) {
			setAttributes( { button: newButton } );
		}
		function changeTitle( newTitle ) {
			setAttributes( { title: newTitle } );
		}
		function changeDescription( newDescription ) {
			setAttributes( { description: newDescription } );
		}
		function changeShort_description( newShort_description ) {
			setAttributes( { short_description: newShort_description } );
		}
		function changeImage( newImage ) {
			setAttributes( { image: newImage } );
		}

		//Display block preview and UI
		return (
			<Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'General Settings', 'wc-donation-platform' ) }>
						<TextControl
							label="Product ID"
							value={ id }
							type="number"
							onChange={ changeId }
						/>
						<SelectControl
							label={ __( 'Donation Form Style', 'wc-donation-platform' ) }
							value={ style }
							options={ [
								{ label: __( 'Style 1 (3 steps, progress header)', 'wc-donation-platform' ), value: '1' },
								{ label: __( 'Style 2 (one page)', 'wc-donation-platform' ), value: '2' },
								{ label: __( 'Style 3 (3 steps, no header)', 'wc-donation-platform' ), value: '3' },
								{ label: __( 'Style 4 (just first step)', 'wc-donation-platform' ), value: '4' },
								{ label: __( 'Style 5 (Banner Header)', 'wc-donation-platform' ), value: '5' },
							] }
							onChange={ changeStyle }
						/>
						<ToggleControl
							label={ __( 'Display in a popup?', 'wc-donation-platform' ) }
							checked={ popup }
							onChange={ changePopup }
						/>
						<ToggleControl
							label={ __( 'Display a open popup button?', 'wc-donation-platform' ) }
							checked={ button }
							help={ __( 'Applies only if popup is enabled', 'wc-donation-platform' ) }
							onChange={ changeButton }
						/>
						<ToggleControl
							label={ __( 'Display the product title?', 'wc-donation-platform' ) }
							checked={ title }
							onChange={ changeTitle }
						/>
						<ToggleControl
							label={ __( 'Display description?', 'wc-donation-platform' ) }
							checked={ description }
							onChange={ changeDescription }
						/>
						<ToggleControl
							label={ __( 'Display short description?', 'wc-donation-platform' ) }
							checked={ short_description }
							onChange={ changeShort_description }
						/>
						<ToggleControl
							label={ __( 'Display product image?', 'wc-donation-platform' ) }
							checked={ image }
							onChange={ changeImage }
						/>
					</PanelBody>
				</InspectorControls>
				<p { ...useBlockProps() }>
					{ __( 'Your donation form will be displayed here.', 'wc-donation-platform' ) }
				</p>
			</Fragment>
		)
	},
	save(){
		return null; //Server Side Render -> Callback function
	}
} );
