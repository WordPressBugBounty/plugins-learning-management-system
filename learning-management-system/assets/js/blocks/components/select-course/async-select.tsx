import {
	AsyncProps,
	AsyncSelect as ChakraReactSelect,
	ChakraStylesConfig,
	GroupBase,
	SelectInstance,
} from 'chakra-react-select';
import React, { RefAttributes } from 'react';
import { reactSelectStyles } from '../../../back-end/config/styles';

const asyncSelect = <
	Option,
	IsMulti extends boolean = false,
	Group extends GroupBase<Option> = GroupBase<Option>,
>(
	{
		...rest
	}: AsyncProps<Option, IsMulti, Group> &
		RefAttributes<SelectInstance<Option, IsMulti, Group>>,
	ref: React.Ref<SelectInstance<Option, IsMulti, Group>>,
): React.ReactElement => {
	const defaultStyle: ChakraStylesConfig<Option, IsMulti, Group> = {
		...reactSelectStyles,
	};

	return (
		<ChakraReactSelect<Option, IsMulti, Group>
			chakraStyles={defaultStyle}
			colorScheme="primary"
			ref={ref}
			{...rest}
		/>
	);
};

export default React.forwardRef(asyncSelect);
