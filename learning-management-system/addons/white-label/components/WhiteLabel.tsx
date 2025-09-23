import { FormControl, Grid } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	ProTextInputForSettings,
	VerticallyStackProShowcase,
} from '../../../assets/js/back-end/components/common/pro/ProShowcaseComponent';
import SingleComponentsWrapper from '../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';

const WhiteLabel: React.FC = (props) => {
	return (
		<SingleComponentsWrapper
			title={__('White Label', 'learning-management-system')}
		>
			<FormControl>
				<ProTextInputForSettings
					label={__('Title', 'learning-management-system')}
				/>
			</FormControl>
			<Grid
				templateColumns={{ base: '1fr', sm: '1fr 1fr', md: '1fr 1fr 1fr' }}
				gap={8}
				mx={{ base: 'auto', md: 'unset' }}
			>
				<VerticallyStackProShowcase
					label={__('Active Menu Icon', 'learning-management-system')}
				/>

				<VerticallyStackProShowcase
					label={__('Inactive Menu Icon', 'learning-management-system')}
				/>

				<VerticallyStackProShowcase
					label={__('Logo', 'learning-management-system')}
				/>
			</Grid>

			<ProTextInputForSettings
				label={__('Student Role Name', 'learning-management-system')}
			/>

			<ProTextInputForSettings
				label={__('Instructor Role Name', 'learning-management-system')}
			/>
		</SingleComponentsWrapper>
	);
};

export default WhiteLabel;
