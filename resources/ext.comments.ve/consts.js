const TOOLBAR_CONFIG = [
	{
		'header': 'visualeditor-toolbar-text-style',
		'title': 'visualeditor-toolbar-style-tooltip',
		'include': [ 'bold', 'italic', 'moreTextStyle' ]
	}, {
		'include': [ 'link' ]
	}, {
		'header': 'visualeditor-toolbar-structure',
		'title': 'visualeditor-toolbar-structure',
		'type': 'list',
		'icon': 'listBullet',
		'include': { 'group': 'structure' },
		'demote': [ 'outdent', 'indent' ]
	}, {
		'header': 'visualeditor-toolbar-insert',
		'title': 'visualeditor-toolbar-insert',
		'type': 'list',
		'icon': 'add',
		'include': [ 'insertTable', 'specialCharacter' ]
	}
]

module.exports = {
	TOOLBAR_CONFIG
};
