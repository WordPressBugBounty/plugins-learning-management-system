import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { useLocation, useSearchParams } from 'react-router-dom';
import FilterTabs from '../../../../../assets/js/back-end/components/common/FilterTabs';
import {
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../assets/js/back-end/components/common/Header';
import { navLinkStyles } from '../../../../../assets/js/back-end/config/styles';
import { Gear } from '../../../../../assets/js/back-end/constants/images';
import API from '../../../../../assets/js/back-end/utils/api';
import { deepMerge } from '../../../../../assets/js/back-end/utils/utils';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const tabButtons: FilterTabs = [
	{
		status: 'any',
		name: __('All Pricing Zones', 'learning-management-system'),
		link: `${multipleCurrencyBackendRoutes.list}?status=any`,
	},
	{
		status: 'active',
		name: __('Active', 'learning-management-system'),
		link: `${multipleCurrencyBackendRoutes.list}?status=active`,
	},
	{
		status: 'inactive',
		name: __('Inactive', 'learning-management-system'),
		link: `${multipleCurrencyBackendRoutes.list}?status=inactive`,
	},
	{
		status: 'trash',
		name: __('Trash', 'learning-management-system'),
		link: `${multipleCurrencyBackendRoutes.list}?status=trash`,
	},
	{
		status: 'settings',
		name: __('Settings', 'learning-management-system'),
		link: multipleCurrencyBackendRoutes.settings,
		icon: <Gear height="20px" width="20px" fill="currentColor" />,
	},
];

interface Props {
	pricing_zones_count?: object;
}

const LeftHeader: React.FC<Props> = ({ pricing_zones_count }) => {
	const [active, setActive] = useState('any');

	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const [searchParams] = useSearchParams();
	const { pathname } = useLocation();
	const currentTab =
		'/multiple-currency/settings' === pathname
			? 'settings'
			: (searchParams.get('status') ?? 'any');

	const pricingZoneAPI = new API(urls.pricingZones);

	useEffect(() => {
		if (currentTab) {
			setFilterParams((prevState) => ({
				...prevState,
				status: currentTab,
			}));
		}
	}, [currentTab]);

	const pricingZoneQuery = useQuery({
		queryKey: ['pricingZonesList', filterParams],
		queryFn: () =>
			pricingZoneAPI.list({
				...filterParams,
				status: currentTab === 'settings' ? 'any' : filterParams?.status,
			}),
		...{
			keepPreviousData: true,
		},
	});

	const counts =
		pricingZoneQuery.data?.meta.pricing_zones_count ?? pricing_zones_count;

	const pricingZoneNavStyles = {
		...navLinkStyles,
		mr: '0px',
		borderBottom: '2px solid white',
	};
	const onChangeCourseStatus = (status: string) => {
		setActive(status);
		setFilterParams(
			deepMerge(filterParams, {
				status: status,
			}),
		);
	};
	return (
		<>
			<HeaderLeftSection gap={7}>
				<HeaderLogo />
				<FilterTabs
					tabs={tabButtons}
					defaultActive={active}
					onTabChange={onChangeCourseStatus}
					counts={counts}
					isCounting={pricingZoneQuery.isLoading}
				/>
			</HeaderLeftSection>
		</>
	);
};

export default LeftHeader;
