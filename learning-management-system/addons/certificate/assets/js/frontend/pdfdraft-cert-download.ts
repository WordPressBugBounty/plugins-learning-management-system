import { PDFExporter } from '@pdfdraft/designer';

interface CertJson {
	pages: unknown;
	settings: unknown;
	fonts?: unknown;
}

interface CertData {
	filename: string;
	json: CertJson;
}

declare global {
	interface Window {
		masteriyo_cert_download: CertData;
	}
}

async function run() {
	const data = window.masteriyo_cert_download;
	const statusEl = document.getElementById('masteriyo-cert-status');

	if (!data?.json?.pages) {
		if (statusEl)
			statusEl.textContent = 'Certificate not found. Please try again.';
		return;
	}

	try {

		const fontsRaw = data.json.fonts;
		const fontsMap: Map<string, unknown> =
			fontsRaw && typeof fontsRaw === 'object' && !Array.isArray(fontsRaw)
				? new Map(Object.entries(fontsRaw as Record<string, unknown>))
				: new Map();

		const fontPreloadPromises: Promise<void>[] = [];
		for (const fontObj of Array.from(fontsMap.values())) {
			const family =
				typeof (fontObj as Record<string, unknown>)?.family === 'string'
					? ((fontObj as Record<string, unknown>).family as string)
					: '';
			if (!family) continue;

			const link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href =
				`https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}` +
				`:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap`;

			fontPreloadPromises.push(
				new Promise<void>((resolve) => {
					link.onload = () => {
						Promise.all([
							document.fonts.load(`400 16px "${family}"`),
							document.fonts.load(`700 16px "${family}"`),
						])
							.catch(() => {})
							.then(() => resolve());
					};
					link.onerror = () => resolve();
				}),
			);
			document.head.appendChild(link);
		}

		await Promise.all(fontPreloadPromises);

		const exporter: PDFExporter = new (PDFExporter as any)({ renderDelay: 2000 });
		const url = await exporter.getPreviewUrl({
			pages: data.json.pages,
			settings: data.json.settings,
			fonts: fontsMap as any,
		});

		const a = document.createElement('a');
		a.href = url;
		a.download = data.filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);

		URL.revokeObjectURL(url);

		if (statusEl)
			statusEl.textContent = 'Your certificate has been downloaded!';

		setTimeout(() => window.close(), 1500);
	} catch (err) {
		const msg = err instanceof Error ? err.message : String(err);
		if (statusEl)
			statusEl.textContent = `Certificate generation failed: ${msg}`;
		console.error('[masteriyo-cert] generation error:', err);
	}
}

document.addEventListener('DOMContentLoaded', run);
