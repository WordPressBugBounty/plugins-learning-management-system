const base = '/masteriyo/v1/';
export const urls = {
	create_wc_product: base + 'courses/create-wc-product',
	create_bundle_wc_product: base + 'course-bundles/create-wc-product',
	link_wc_product: (courseId: number) =>
		`${base}courses/${courseId}/link-wc-product`,
	list_wc_products: base + 'courses/list-wc-products',
	unified_orders: base + 'orders/unified',
	wc_orders: base + 'orders/wc',
};
