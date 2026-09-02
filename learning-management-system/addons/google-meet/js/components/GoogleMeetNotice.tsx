import { Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CustomAlert from '../../../../assets/js/back-end/components/common/CustomAlert';

const GoogleMeetNotice = () => {
	return (
		<CustomAlert status="error">
			<Text>
				{__(
					'This meet is created by instructor, You can not make any change in this.',
					'learning-management-system',
				)}
			</Text>
		</CustomAlert>
	);
};

export default GoogleMeetNotice;
