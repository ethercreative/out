module.exports = {
	manifest: null,
	sass: { run: false },
	critical: { run: false },
	less: {
		run: true,
		entry: ['resources/less/out.less'],
		output: ['src/web/assets/out.css'],
	},
	js: {
		run: true,
		entry: {
			OutEdit: './resources/js/OutEdit.js',
			OutIndex: './resources/js/OutIndex.js',
		},
		output: {
			path: process.cwd() + '/src/web/assets',
			filename: '[name].min.js',
		},
	},
};
