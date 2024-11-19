import { FormLabel, Input, Stack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { CourseDataMap } from '../../../../../../assets/js/back-end/types/course';

interface Props {
	courseData?: CourseDataMap;
}

const LemonSqueezyIntegrationCourseSetting: React.FC<Props> = (props) => {
	const { courseData } = props;
	const { register } = useFormContext();

	return (
		<Stack direction="column" spacing="8">
			<FormControlTwoCol>
				<FormLabel minW="160px">
					{__('Product Variant ID', 'learning-management-system')}
					<ToolTip
						label={__(
							'Retrieve the variant ID for the Lemon Squeezy product. Please verify that the variant ID exists and that this course is paid.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Input
					type="text"
					{...register('lemon_squeezy_integration.product_id')}
					defaultValue={courseData?.lemon_squeezy_integration?.product_id || ''}
				/>
			</FormControlTwoCol>
		</Stack>
	);
};

export default LemonSqueezyIntegrationCourseSetting;
