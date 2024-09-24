import { Box, Button, ButtonGroup } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { DragDropContext, DropResult, Droppable } from 'react-beautiful-dnd';
import { BiPlusCircle } from 'react-icons/bi';
import UiPlacement from './UiPlacement';
import { reorderPlacements } from './reorder';

interface Props {
	data?: UiPlacementData[];
	onChange: (newData: UiPlacementData[]) => void;
	rewardTypes?: GamiPressRewardTypes;
	rewardType?: string;
}

const UiPlacementManagement: React.FC<Props> = (props) => {
	const { data = [], onChange, rewardTypes = {}, rewardType = '' } = props;

	const setUiPlacements = (uiPlacements: UiPlacementData[]) => {
		onChange(uiPlacements);
	};

	const onDragEnd = (result: DropResult) => {
		const reordered = reorderPlacements(result, data);

		if (reordered) {
			setUiPlacements(reordered);
		}
	};

	const deletePlacement = (placementId: any) => {
		setUiPlacements(data.filter((old) => old.id !== placementId));
	};

	const updatePlacement = (placementId: any, newData: any) => {
		setUiPlacements(
			data.map((old) =>
				old.id === placementId ? { ...old, ...newData, id: old.id } : old,
			),
		);
	};

	return (
		<DragDropContext onDragEnd={onDragEnd}>
			<Droppable droppableId="section" type="section">
				{(droppableProvided) => (
					<Box
						ref={droppableProvided.innerRef}
						{...droppableProvided.droppableProps}
						style={{ width: '100%' }}
					>
						{data.map((item, index) => (
							<UiPlacement
								key={item.id}
								index={index}
								data={item}
								onChange={(newData) => updatePlacement(item.id, newData)}
								onDeletePress={() => deletePlacement(item.id)}
								rewardTypes={rewardTypes}
							/>
						))}

						<Box py="1" px="8" textAlign="center">
							<ButtonGroup justifyContent="center">
								<Button
									colorScheme="primary"
									variant="outline"
									leftIcon={<BiPlusCircle />}
									onClick={() =>
										setUiPlacements([
											...data,
											{ id: Date.now(), reward_type: rewardType },
										])
									}
								>
									{__('Add New Placement', 'learning-management-system')}
								</Button>
							</ButtonGroup>
						</Box>

						{droppableProvided.placeholder}
					</Box>
				)}
			</Droppable>
		</DragDropContext>
	);
};

export default UiPlacementManagement;
