import { extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Tab } from './../../../components';
import PaddingSetting from './../../../components/PaddingSetting';

const theme = extendTheme({});

const BlockSettings: React.FC<any> = ({
	attributes: { padding },
	setAttributes,
}) => {
	return (
		<Tab tabTitle={__('Settings', 'learning-management-system')}>
			<Panel title={__('Layout', 'learning-management-system')} initialOpen>
				<PaddingSetting
					value={padding}
					onChange={(val) => setAttributes({ padding: val })}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
