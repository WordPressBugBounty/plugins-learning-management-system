/**
 * Badge color map keyed by H5P library machine name.
 * Unknown types fall back to 'gray' at the call site.
 */
const H5P_BADGE_COLOR: Record<string, string> = {
	'H5P.QuestionSet': 'blue',
	'H5P.MultiChoice': 'blue',
	'H5P.TrueFalse': 'cyan',
	'H5P.Blanks': 'purple',
	'H5P.DragQuestion': 'teal',
	'H5P.MarkTheWords': 'green',
	'H5P.DragText': 'teal',
	'H5P.Essay': 'orange',
	'H5P.SingleChoiceSet': 'blue',
	'H5P.Summary': 'green',
	'H5P.ImageHotspotQuestion': 'pink',
	'H5P.Flashcards': 'yellow',
	'H5P.ArithmeticQuiz': 'red',
	'H5P.MemoryGame': 'purple',
	'H5P.Dictation': 'cyan',
	'H5P.FindMultipleHotspots': 'pink',
	'H5P.ImageHotspots': 'pink',
	'H5P.Agamotto': 'teal',
	'H5P.Crossword': 'green',
	'H5P.SpeakTheWords': 'red',
	'H5P.SpeakTheWordsSet': 'red',
};

export default H5P_BADGE_COLOR;
