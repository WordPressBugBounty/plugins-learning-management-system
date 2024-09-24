export type PriceZoneSchema = {
	id: number;
	title: string;
	currency: {
		value: string;
		label: string;
	};
	exchange_rate: number;
	countries: {
		value: string;
		label: string;
	}[];
	status: string;
	date_created: string;
	date_modified: string;
};

export type MultipleCurrencySettingsSchema = {
	test_mode: {
		enabled: boolean;
		country: string;
	};
	maxmind: {
		enabled: boolean;
		license_key: string;
	};
};

export type MultipleCurrencyCourseSettingsSchema = {
	enabled: boolean;
	pricing_method: 'exchange_rate' | 'manual';
	regular_price: number;
	sale_price: number;
};

export type ActivePricingZone = {
	id: number;
	title: string;
	enabled: boolean;
	pricing_method: string;
	regular_price: number;
	sale_price: number;
	currency_code: string;
	currency_symbol: string;
};
