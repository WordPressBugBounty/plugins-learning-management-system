import {
	Box,
	Button,
	Grid,
	HStack,
	Input,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Tab,
	TabList,
	Tabs,
	Text,
	VStack,
} from '@chakra-ui/react';
import { useMutation } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API from '../../../../../assets/js/back-end/utils/api';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';

interface PaperSize {
	id: string;
	name: string;
	in: { w: number; h: number };
}

const PAPER_SIZES: PaperSize[] = [
	{ id: 'LE', name: 'Letter', in: { w: 8.5, h: 11 } },
	{ id: 'TA', name: 'Tabloid', in: { w: 11, h: 17 } },
	{ id: 'A0', name: 'A0', in: { w: 33.1, h: 46.8 } },
	{ id: 'A1', name: 'A1', in: { w: 23.4, h: 33.1 } },
	{ id: 'A2', name: 'A2', in: { w: 16.5, h: 23.4 } },
	{ id: 'A3', name: 'A3', in: { w: 11.7, h: 16.5 } },
	{ id: 'A4', name: 'A4', in: { w: 8.3, h: 11.7 } },
	{ id: 'A5', name: 'A5', in: { w: 5.8, h: 8.3 } },
	{ id: 'A6', name: 'A6', in: { w: 4.1, h: 5.8 } },
	{ id: 'B4', name: 'B4', in: { w: 10.1, h: 14.3 } },
	{ id: 'B5', name: 'B5', in: { w: 7.2, h: 10.1 } },
	{ id: 'EX', name: 'Executive', in: { w: 7.3, h: 10.5 } },
	{ id: 'FO', name: 'Folio', in: { w: 8.5, h: 13 } },
];

type Unit = 'in' | 'cm' | 'px';
type Orientation = 'portrait' | 'landscape';

const CM_PER_IN = 2.54;
const PX_PER_IN = 96;
const MAX_DIMENSION_IN = 100;

function toIn(value: number, unit: Unit): number {
	if (unit === 'in') return value;
	if (unit === 'cm') return value / CM_PER_IN;
	return value / PX_PER_IN;
}

function fromIn(value: number, unit: Unit): number {
	if (unit === 'in') return parseFloat(value.toFixed(2));
	if (unit === 'cm') return parseFloat((value * CM_PER_IN).toFixed(2));
	return Math.round(value * PX_PER_IN);
}

interface Props {
	isOpen: boolean;
	onClose: () => void;
}

const NewCertificateDialog: React.FC<Props> = ({ isOpen, onClose }) => {
	const [orientation, setOrientation] = useState<Orientation>('portrait');
	const [unit, setUnit] = useState<Unit>('in');
	const [width, setWidth] = useState(8.3);
	const [height, setHeight] = useState(11.7);
	const [selectedPreset, setSelectedPreset] = useState<string | null>('A4');

	const navigate = useNavigate();
	const certificateAPI = new API(certificateAddonUrls.certificates);

	const wIn = toIn(width, unit);
	const hIn = toIn(height, unit);
	const dimensionError =
		wIn <= 0 || hIn <= 0
			? __(
					'Width and height must be greater than 0.',
					'learning-management-system',
				)
			: wIn > MAX_DIMENSION_IN || hIn > MAX_DIMENSION_IN
				? sprintf(
						/* translators: %d: maximum allowed dimension in inches */
						__(
							'Width and height cannot exceed %d in.',
							'learning-management-system',
						),
						MAX_DIMENSION_IN,
					)
				: '';

	const createCertificate = useMutation({
		mutationFn: (data: any) => certificateAPI.store(data),
		onSuccess: (newCert: any) => {
			navigate(
				certificateBackendRoutes.certificate.edit.replace(
					':certificateId',
					String(newCert.id),
				),
				{ state: { certificate: newCert } },
			);
		},
	});

	const applyPreset = (preset: PaperSize) => {
		setSelectedPreset(preset.id);
		let w = preset.in.w;
		let h = preset.in.h;
		if (orientation === 'landscape' && w < h) {
			[w, h] = [h, w];
		} else if (orientation === 'portrait' && w > h) {
			[w, h] = [h, w];
		}
		setWidth(fromIn(w, unit));
		setHeight(fromIn(h, unit));
	};

	const handleOrientationChange = (newOrientation: Orientation) => {
		if (newOrientation === orientation) return;
		setOrientation(newOrientation);
		setWidth(height);
		setHeight(width);
	};

	const handleUnitChange = (newUnit: Unit) => {
		const wIn = toIn(width, unit);
		const hIn = toIn(height, unit);
		setUnit(newUnit);
		setWidth(fromIn(wIn, newUnit));
		setHeight(fromIn(hIn, newUnit));
	};

	const handleCreate = () => {
		if (dimensionError) {
			return;
		}

		const initialState = {
			id: 0,
			slug: '',
			pages: { 'page-1': { name: '', children: [] } },
			pagesOrder: ['page-1'],
			settings: {
				name: __('New Certificate', 'learning-management-system'),
				layout: {
					width: parseFloat(wIn.toFixed(4)),
					height: parseFloat(hIn.toFixed(4)),
					unit: 'in',
					orientation,
				},
			},
			status: 'draft',
			versionHistory: [],
		};

		createCertificate.mutate({
			name: __('New Certificate', 'learning-management-system'),
			status: 'draft',
			content_format: 'pdfdraft',
			html_content: JSON.stringify(initialState),
		});
	};

	return (
		<Modal isOpen={isOpen} onClose={onClose} size="xl" isCentered>
			<ModalOverlay />
			<ModalContent>
				<ModalHeader>
					{__('New Project', 'learning-management-system')}
				</ModalHeader>
				<ModalCloseButton />
				<ModalBody>
					<VStack spacing={5} align="stretch">
						<Tabs
							index={orientation === 'portrait' ? 0 : 1}
							onChange={(i) =>
								handleOrientationChange(i === 0 ? 'portrait' : 'landscape')
							}
							variant="enclosed"
						>
							<TabList>
								<Tab>{__('Portrait', 'learning-management-system')}</Tab>
								<Tab>{__('Landscape', 'learning-management-system')}</Tab>
							</TabList>
						</Tabs>

						<HStack spacing={3}>
							<Box flex={1}>
								<Text fontSize="xs" mb={1} color="gray.600">
									{__('Width', 'learning-management-system')}
								</Text>
								<Input
									type="number"
									value={width}
									min={0}
									max={fromIn(MAX_DIMENSION_IN, unit)}
									step={unit === 'px' ? 1 : 0.1}
									onChange={(e) => {
										setWidth(parseFloat(e.target.value) || 0);
										setSelectedPreset(null);
									}}
									size="sm"
								/>
							</Box>
							<Box flex={1}>
								<Text fontSize="xs" mb={1} color="gray.600">
									{__('Height', 'learning-management-system')}
								</Text>
								<Input
									type="number"
									value={height}
									min={0}
									max={fromIn(MAX_DIMENSION_IN, unit)}
									step={unit === 'px' ? 1 : 0.1}
									onChange={(e) => {
										setHeight(parseFloat(e.target.value) || 0);
										setSelectedPreset(null);
									}}
									size="sm"
								/>
							</Box>
							<Box flex={1}>
								<Text fontSize="xs" mb={1} color="gray.600">
									{__('Unit', 'learning-management-system')}
								</Text>
								<select
									value={unit}
									onChange={(e) => handleUnitChange(e.target.value as Unit)}
									style={{
										width: '100%',
										height: '32px',
										padding: '0 8px',
										border: '1px solid #E2E8F0',
										borderRadius: '6px',
										fontSize: '14px',
										background: 'white',
										color: '#1A202C',
										outline: 'none',
										cursor: 'pointer',
									}}
								>
									<option value="in">in</option>
									<option value="cm">cm</option>
									<option value="px">px</option>
								</select>
							</Box>
						</HStack>

						{dimensionError && (
							<Text color="red.500" fontSize="xs">
								{dimensionError}
							</Text>
						)}

						<Box>
							<Text fontSize="xs" mb={2} color="gray.600" fontWeight="medium">
								{__('Paper Size', 'learning-management-system')}
							</Text>
							<Grid templateColumns="repeat(2, 1fr)" gap={2}>
								{PAPER_SIZES.map((preset) => {
									const wIn = preset.in.w;
									const hIn = preset.in.h;
									const [displayW, displayH] =
										orientation === 'landscape'
											? [Math.max(wIn, hIn), Math.min(wIn, hIn)]
											: [Math.min(wIn, hIn), Math.max(wIn, hIn)];
									const w = fromIn(displayW, unit);
									const h = fromIn(displayH, unit);
									const unitLabel = unit;
									const dimStr = `${w}×${h} ${unitLabel}`;
									return (
										<Button
											key={preset.id}
											size="sm"
											variant={
												selectedPreset === preset.id ? 'solid' : 'outline'
											}
											colorScheme={
												selectedPreset === preset.id ? 'blue' : 'gray'
											}
											onClick={() => applyPreset(preset)}
											justifyContent="flex-start"
											fontWeight="normal"
										>
											<Box
												as="span"
												fontWeight="bold"
												mr={1}
												fontSize="xs"
												opacity={0.7}
											>
												{preset.id}
											</Box>
											{dimStr}
										</Button>
									);
								})}
							</Grid>
						</Box>
					</VStack>
				</ModalBody>
				<ModalFooter>
					<Button variant="ghost" mr={3} onClick={onClose}>
						{__('Cancel', 'learning-management-system')}
					</Button>
					<Button
						colorScheme="blue"
						onClick={handleCreate}
						isLoading={createCertificate.isPending}
						isDisabled={Boolean(dimensionError)}
					>
						{__('Create', 'learning-management-system')}
					</Button>
				</ModalFooter>
			</ModalContent>
		</Modal>
	);
};

export default NewCertificateDialog;
