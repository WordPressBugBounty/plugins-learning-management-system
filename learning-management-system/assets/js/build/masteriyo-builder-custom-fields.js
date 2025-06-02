
class CustomFieldRegistry {
	constructor() {
		this.fields = {}; // Structure: { [section: string]: { [placement: string]: Array<config> } }
	}
 
	registerField(section, placement, config) {
		const missingProperties = [];
		const fields = [
			'text',
			'number',
			'textarea',
			'password',
			'date',
			'time',
			'checkbox',
			'radio',
			'select',
			'switch',
		];

		if (!config.name) {
			missingProperties.push('name');
		}

		if (!config?.type) {
			missingProperties.push('type');
		}

		if (!config?.label) {
			missingProperties.push('label');
		}

		if (missingProperties.length > 0) {
			console.error(
				`Field registration error: Missing required properties (${missingProperties.join(', ')}).`,
				config,
			);
			return;
		}

		if (!fields?.includes(config.type)) {
			console.error(
				`Field registration error: Invalid field type '${config.type}'. Expected one of ${fields.join(', ')}.`,
				config,
			);
			return;
		}

		if (!this.fields[section]) {
			this.fields[section] = {};
		}
		const sectionPlacements = this.fields[section];
		if (!sectionPlacements[placement]) {
			sectionPlacements[placement] = [];
		}
		sectionPlacements[placement].push(config);
		sectionPlacements[placement].sort((a, b) => a.priority - b.priority);
	}

	getFields(section, placement) {
		if (placement === 'all') {
			return Object.values(this.fields[section]).flat() || [];
		}
		return this.fields[section]?.[placement] || [];
	}
}

window.customFieldRegistry = new CustomFieldRegistry();

const Masteriyo_Course_Builder = {
	CourseBuilder: {
		Basic: {
			registerField: (placement, config) => {
				customFieldRegistry.registerField('Basic', placement, config);
			},
		},
		Curriculum: {
			Lesson: {
				registerField: (placement, config) => {
					customFieldRegistry.registerField(
						'Curriculum.Lesson',
						placement,
						config,
					);
				},
			},
		},
		Additional: {
			registerField: (placement, config) => {
				customFieldRegistry.registerField('Additional', placement, config);
			},
		},
	},
};

window.registerMasteriyoField = function (
	fieldKey,
	fieldOptions,
	section = 'Basic',
) {
	if (
		typeof Masteriyo_Course_Builder !== 'undefined' &&
		Masteriyo_Course_Builder.CourseBuilder
	) {
		const parts = section.split('.');
		let current = Masteriyo_Course_Builder.CourseBuilder;
		for (const part of parts) {
			current = current[part];
			if (!current) {
				console.error(`Section '${section}' not found.`);
				return;
			}
		}

		if (typeof current.registerField === 'function') {
			current.registerField(fieldKey, fieldOptions);
		} else {
			console.error(`registerField not found in section '${section}'.`);
		}
	} else {
		window.pendingMasteriyoFields = window.pendingMasteriyoFields || [];
		window.pendingMasteriyoFields.push({
			key: fieldKey,
			options: fieldOptions,
			section,
		});
	}
};

document.addEventListener('MasteriyoReady', function () {
	if (window.pendingMasteriyoFields) {
		window.pendingMasteriyoFields.forEach(({ key, options, section }) => {
			window.registerMasteriyoField(key, options, section);
		});
		window.pendingMasteriyoFields = [];
	}
});

document.addEventListener('DOMContentLoaded', function () {
	document.dispatchEvent(new Event('MasteriyoReady'));
});
