/**
 * External dependencies
 */
import React from 'react';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RangeControl, SelectControl } from '../';
import CategorySelect from './category-select';

const DEFAULT_MIN_ITEMS = 1;
const DEFAULT_MAX_ITEMS = 100;

const options = [
	{
		label: __( 'Newest to oldest' ),
		value: 'date/desc',
	},
	{
		label: __( 'Oldest to newest' ),
		value: 'date/asc',
	},
	{
		/* translators: label for ordering posts by title in ascending order */
		label: __( 'A → Z' ),
		value: 'title/asc',
	},
	{
		/* translators: label for ordering posts by title in descending order */
		label: __( 'Z → A' ),
		value: 'title/desc',
	},
];

const QueryControls = React.memo(
	( {
		categoriesList,
		selectedCategoryId,
		numberOfItems,
		order,
		orderBy,
		maxItems = DEFAULT_MAX_ITEMS,
		minItems = DEFAULT_MIN_ITEMS,
		onCategoryChange,
		onNumberOfItemsChange,
		onOrderChange,
		onOrderByChange,
	} ) => {
		const onChange = useCallback( ( value ) => {
			const [ newOrderBy, newOrder ] = value.split( '/' );
			if ( newOrder !== order ) {
				onOrderChange( newOrder );
			}
			if ( newOrderBy !== orderBy ) {
				onOrderByChange( newOrderBy );
			}
		}, [] );

		return [
			onOrderChange && onOrderByChange && (
				<SelectControl
					label={ __( 'Order by' ) }
					value={ `${ orderBy }/${ order }` }
					options={ options }
					onChange={ onChange }
				/>
			),
			onCategoryChange && (
				<CategorySelect
					categoriesList={ categoriesList }
					label={ __( 'Category' ) }
					noOptionLabel={ __( 'All' ) }
					selectedCategoryId={ selectedCategoryId }
					onChange={ onCategoryChange }
				/>
			),
			onNumberOfItemsChange && (
				<RangeControl
					label={ __( 'Number of items' ) }
					value={ numberOfItems }
					onChange={ onNumberOfItemsChange }
					min={ minItems }
					max={ maxItems }
					required
				/>
			),
		];
	}
);

export default QueryControls;
