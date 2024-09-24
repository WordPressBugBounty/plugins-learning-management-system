import {
	Badge,
	Spinner,
	Step,
	StepIcon,
	StepIndicator,
	StepNumber,
	StepSeparator,
	StepStatus,
	StepTitle,
	Stepper,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import DisplayModal from '../../../../assets/js/back-end/components/common/DisplayModal';
import LargeAlert from '../../../../assets/js/back-end/components/common/LargeAlert';
import { PRIMARY_COLOR } from '../../../../assets/js/back-end/constants/general';
import {
	ALL_MIGRATION_STEPS,
	MIGRATION_STEPS_RELATIVE_TO_LMS,
} from '../constants/general';

interface MigrationStatusDisplayProps {
	isMigrationProcessCompleted: boolean;
	activeStep: number;
	lmsWatchedValue: string;
	currentlyActiveLms: { name: string; label: string };
	showMigrationStatus: boolean;
	onMigrationalStatusClose: () => void;
	migrationProcessInProgress: string | undefined;
}

const MigrationStatusDisplay: React.FC<MigrationStatusDisplayProps> = ({
	isMigrationProcessCompleted,
	activeStep,
	lmsWatchedValue,
	currentlyActiveLms,
	showMigrationStatus,
	onMigrationalStatusClose,
	migrationProcessInProgress,
}) => {
	return (
		<>
			<DisplayModal
				size={'sm'}
				isOpen={showMigrationStatus}
				onClose={onMigrationalStatusClose}
				title={!isMigrationProcessCompleted ? currentlyActiveLms?.label : ''}
				showCloseOption={isMigrationProcessCompleted}
				closeOnOverlayClick={isMigrationProcessCompleted}
				applyPadding={!isMigrationProcessCompleted}
				extraInfo={
					migrationProcessInProgress && (
						<Badge
							colorScheme="green"
							ml={2}
							py={1}
							px={2}
							variant={'outline'}
							borderRadius={'sm'}
						>
							In-Progress: {migrationProcessInProgress}
						</Badge>
					)
				}
			>
				{!isMigrationProcessCompleted ? (
					<Stepper
						index={activeStep}
						mt={2}
						colorScheme={'primary'}
						orientation={'vertical'}
					>
						{[
							...(MIGRATION_STEPS_RELATIVE_TO_LMS[lmsWatchedValue] ||
								ALL_MIGRATION_STEPS),
						].map((step, index) => (
							<Step key={index}>
								<StepIndicator>
									<StepStatus
										complete={<StepIcon />}
										incomplete={<StepNumber />}
										active={<Spinner fontSize={'sm'} color={'blue.500'} />}
									/>
								</StepIndicator>

								<StepTitle>
									<Text
										position={'relative'}
										bottom={3}
										left={1}
										fontSize={15}
										color={index === activeStep ? PRIMARY_COLOR : 'gray.800'}
										fontWeight={index === activeStep ? 'semibold' : 'normal'}
									>
										{step}
									</Text>
								</StepTitle>

								<StepSeparator />
							</Step>
						))}
					</Stepper>
				) : (
					<LargeAlert
						title={__(
							`${currentlyActiveLms?.label}'s data migrated successfully.`,
							'learning-management-system',
						)}
						height={'130px'}
						varient={'subtle'}
					/>
				)}
			</DisplayModal>
		</>
	);
};

export default React.memo(MigrationStatusDisplay);
