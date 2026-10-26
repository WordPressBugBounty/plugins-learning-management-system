import React, { ReactElement, useEffect, useState } from 'react';
import { ModuleGroups } from '@divi/module';
import { type Module } from '@divi/types';

import { CourseListModuleAttrs } from './types';
import { COURSE_CATEGORIES_LIST } from '../../constants/urls';
import { fetchMasteriyoApi } from '../../utils/api';

interface DynamicOptionsField {
	attrName?: string;
	component?: {
		props?: {
			options?: unknown;
		};
	};
}

// Upper bound on category-list requests in flight at once.
const MAX_CONCURRENT_PAGES = 4;

interface CourseCategory {
	id: number;
	name: string;
}

interface CourseCategoriesResponse {
	data: CourseCategory[];
	meta?: {
		pages?: number;
	};
}

/**
 * Populates the Include/Exclude Categories multi-select with the site's real
 * course categories, fetched from Masteriyo's own REST endpoint (Divi's
 * built-in taxonomy helper doesn't work here since course_cat isn't exposed
 * via wp/v2/taxonomies).
 */
export const SettingsContent = ({
	groupConfiguration,
}: Module.Settings.Panel.Props<CourseListModuleAttrs>): ReactElement => {
	// divi/select (like the static order/orderBy fields) expects options as a
	// dict keyed by value, not an array like divi/checkboxes used.
	const [options, setOptions] = useState<Record<string, { label: string }>>({});

	useEffect(() => {
		const fetchPage = (page: number) =>
			fetchMasteriyoApi<CourseCategoriesResponse>(COURSE_CATEGORIES_LIST, {
				per_page: 100,
				page,
			});

		// The REST API hard-caps per_page at 100, so a site with more
		// categories than that needs every page merged, not just the first.
		// The rest are fetched in small batches: one request per page all at
		// once would be a burst proportional to the category count.
		const fetchRemaining = async (totalPages: number) => {
			const rest: CourseCategoriesResponse[] = [];
			for (let start = 2; start <= totalPages; start += MAX_CONCURRENT_PAGES) {
				const batch = Array.from(
					{ length: Math.min(MAX_CONCURRENT_PAGES, totalPages - start + 1) },
					(_, index) => fetchPage(start + index),
				);
				rest.push(...(await Promise.all(batch)));
			}
			return rest;
		};

		fetchPage(1)
			.then(async (firstPage) => [
				firstPage,
				...(await fetchRemaining(firstPage.meta?.pages ?? 1)),
			])
			.then((pages) => {
				const categoryOptions: Record<string, { label: string }> = {};
				pages.forEach((page) => {
					(page.data ?? []).forEach((category) => {
						categoryOptions[String(category.id)] = { label: category.name };
					});
				});
				setOptions(categoryOptions);
			})
			.catch(() => {
				// Leave options empty — the field just shows nothing to
				// pick, rather than breaking the whole settings panel.
				setOptions({});
			});
	}, []);

	const fields = groupConfiguration?.filterContent?.component?.props?.fields;

	if (fields) {
		Object.values(fields as Record<string, DynamicOptionsField>).forEach(
			(field) => {
				if (
					field?.attrName === 'include_categories.innerContent' ||
					field?.attrName === 'exclude_categories.innerContent'
				) {
					field.component = field.component ?? {};
					field.component.props = field.component.props ?? {};
					field.component.props.options = options;
				}
			},
		);
	}

	return <ModuleGroups groups={groupConfiguration} />;
};
