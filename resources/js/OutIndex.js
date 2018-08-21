/* global Craft, Garnish, $ */

Craft.Out = Craft.Out || {};

Craft.Out.ExportIndex = Craft.BaseElementIndex.extend({
	onUpdateElements: function () {
		this.base();

		const dls = this.$elements[0].querySelectorAll('a[data-out-dl]');

		for (let i = 0, l = dls.length; i < l; ++i)
			new Garnish.MenuBtn($(dls[i]));
	},
});

Craft.registerElementIndexClass(
	"ether\\out\\elements\\Export",
	Craft.Out.ExportIndex
);