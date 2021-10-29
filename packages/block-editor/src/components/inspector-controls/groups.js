/**
 * WordPress dependencies
 */
import { createSlotFill } from '@wordpress/components';

const InspectorControlsDefault = createSlotFill( 'InspectorControls' );
const InspectorControlsAdvanced = createSlotFill( 'InspectorAdvancedControls' );
const InspectorControlsColor = createSlotFill( 'InspectorControlsColor' );
const InspectorControlsDimensions = createSlotFill(
	'InspectorControlsDimensions'
);
const InspectorControlsTypography = createSlotFill(
	'InspectorControlsTypography'
);

const groups = {
	default: InspectorControlsDefault,
	advanced: InspectorControlsAdvanced,
	color: InspectorControlsColor,
	dimensions: InspectorControlsDimensions,
	typography: InspectorControlsTypography,
};

export default groups;
