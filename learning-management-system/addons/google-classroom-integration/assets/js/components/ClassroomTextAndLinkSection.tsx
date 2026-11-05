import { __ } from '@wordpress/i18n';
import React from 'react';
import GoogleOAuthTextAndLinkSection, {
	GoogleOAuthTextAndLinkSectionProps,
} from '../../../../../assets/js/back-end/components/common/GoogleOAuthTextAndLinkSection';

const ClassroomTextAndLinkSection: React.FC<
	GoogleOAuthTextAndLinkSectionProps
> = (props) => (
	<GoogleOAuthTextAndLinkSection
		{...props}
		service={__('Google Classroom', 'learning-management-system')}
		docsUrl="https://docs.masteriyo.com/free-addons/google-classroom-integration/"
	/>
);

export default ClassroomTextAndLinkSection;
