import { FormLabel, Input, Stack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import SingleComponentsWrapper from '../../../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';

interface Props {
	data: {
		allowed_extensions: string;
	};
}

const ScormSettings: React.FC<Props> = (props) => {
	const { data } = props;
	const { register } = useFormContext();

	return (
		<SingleComponentsWrapper title={__('SCORM', 'learning-management-system')}>
			<Stack direction="column" spacing="6" width={'full'}>
				{/* Slug */}
				<FormControlTwoCol>
					<FormLabel minW="3xs">
						{__('Allowed Extensions', 'learning-management-system')}
						<ToolTip label="Enter file extensions separating with commas without spaces. Example: wav,woff2. Already added extensions are: css,js,woff,ttf,otf,jpg,jpeg,png,gif,html,json,xml,pdf,mp3,mp4,xsd,dtd,ico,swf,svg,txt,mpga,wav,woff2" />
					</FormLabel>
					<Input
						type="text"
						{...register('advance.scorm.allowed_extensions')}
						defaultValue={data?.allowed_extensions}
						placeholder="Example: wav,woff2,mpga,jpeg"
					/>
				</FormControlTwoCol>
			</Stack>
		</SingleComponentsWrapper>
	);
};

export default ScormSettings;
