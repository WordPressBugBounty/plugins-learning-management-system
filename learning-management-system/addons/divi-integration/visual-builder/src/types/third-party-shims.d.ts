// @divi/types ships raw .ts as its package "main" (no compiled .d.ts), so importing
// anything from it pulls its whole barrel — including ai-agent and TinyMCE-backed
// sub-modules we never use — into our type-check. Stub the named types those files
// import so tsc can resolve the barrel without us installing langchain/TinyMCE.
declare module '@langchain/core/language_models/base' {
	export type LanguageModelLike = any;
}
declare module '@langchain/core/messages' {
	export type AIMessage = any;
	export type AIMessageChunk = any;
	export type BaseMessage = any;
	export type ToolMessage = any;
}
declare module '@langchain/core/runnables' {
	export type RunnableConfig<T = any> = any;
}
declare module '@langchain/core/tools' {
	export type DynamicStructuredTool<
		T1 = any,
		T2 = any,
		T3 = any,
		T4 = any,
	> = any;
	export type DynamicStructuredToolInput<T1 = any, T2 = any, T3 = any> = any;
	export type StructuredToolInterface = any;
}
declare module '@langchain/langgraph/web' {
	export type AnnotationRoot<T = any> = any;
	export type BaseChannel<T1 = any, T2 = any> = any;
	export type Command = any;
	export type CompiledStateGraph<T1 = any, T2 = any, T3 = any> = any;
	export const MessagesAnnotation: any;
	export type OverwriteValue<T = any> = any;
}
declare module '@langchain/langgraph-checkpoint' {
	export type BaseCheckpointSaver = any;
}
declare module '@tinymce/tinymce-react' {
	export type Editor = any;
}
