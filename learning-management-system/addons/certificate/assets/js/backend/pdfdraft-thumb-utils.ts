export const PX_PER_UNIT: Record<string, number> = {
	in: 96,
	cm: 37.795,
	mm: 3.7795,
	px: 1,
};

/** Minimal Tailwind utilities used by PDFDraft canvas elements. */
export const PDFDRAFT_CSS = `
*,::after,::before{box-sizing:border-box}
html,body{margin:0;padding:0;overflow:hidden}
.relative{position:relative}.absolute{position:absolute}.fixed{position:fixed}
.inset-0{top:0;right:0;bottom:0;left:0}
.top-0{top:0}.left-0{left:0}.right-0{right:0}.bottom-0{bottom:0}
.bg-white{background-color:#fff}.overflow-hidden{overflow:hidden}
.w-full{width:100%}.h-full{height:100%}
.flex{display:flex}.items-center{align-items:center}.justify-center{justify-content:center}
.text-center{text-align:center}.font-bold{font-weight:700}
`.trim();
