import { Box } from '@chakra-ui/react';
import React from 'react';
import MeetingSetupSection from './components/MeetingSetupSection';
import GoogleMeetHeader from './headers/GoogleMeetHeader';

const GoogleMeetSetAPI: React.FC = () => {
	return (
		<>
			<GoogleMeetHeader />
			<Box m={10}>
				<MeetingSetupSection />
			</Box>
		</>
	);
};

export default GoogleMeetSetAPI;
