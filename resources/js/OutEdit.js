/** global: Craft, Garnish, $ */
import "babel-polyfill";

/**
 * Create Element
 *
 * @param {string} tag
 * @param {Object} attributes
 * @param {string|array} children
 * @returns {HTMLElement}
 */
const h = function (tag = "div", attributes = {}, children = []) {
	const elem = document.createElement(tag);

	for (let [key, value] of Object.entries(attributes)) {
		if (!value) continue;

		if (typeof value === "function") {
			if (key === "ref") value(elem);
			else elem.addEventListener(key, value);
			continue;
		}

		if (key === "style")
			value = value.replace(/[\t\r\n]/g, " ").trim();

		elem.setAttribute(key, value);
	}

	if (!Array.isArray(children))
		children = [children];

	children.forEach(child => {
		if (!child) return;

		try {
			elem.appendChild(child);
		} catch (_) {
			elem.appendChild(document.createTextNode(child));
		}
	});

	return elem;
};

/**
 * Create Craft Form Input
 *
 * @param config
 * @returns {HTMLElement}
 */
const f = function (config) {
	return h("div", { class: "field" }, [
		h("div", { class: "heading" }, [
			h("label", { for: config.id }, config.label),
			config.hasOwnProperty("instructions")
				? h("div", { class: "instructions" }, h("p", {}, config.instructions))
				: null
		]),
		h("div", { class: "input" }, [
			h(config.tag, { id: config.id, ...config.attr }, config.children || null)
		]),
	]);
};

class Out {

	// Properties
	// =========================================================================

	static i = null;

	activeType = "";
	createModal = null;
	editModal = null;

	twigInput = null;
	twigField = null;
	splitInput = null;
	splitField = null;

	resolveCreate = null;
	rejectCreate = null;

	constructor () {
		this.initElementTypeSwitcher();
		this.initCreateModal();
		this.initEditModal();
		this.initFieldTable();

		Out.i = this;
	}

	// Initializers
	// =========================================================================

	initElementTypeSwitcher () {
		const elementType = document.getElementById("elementType")
			, typeSource = document.querySelectorAll("[data-source-type]")
			, sourcesByType = {};

		for (let i = 0, l = typeSource.length; i < l; ++i)
		{
			const f = typeSource[i];
			const t = f.dataset.sourceType;

			const comment = document.createComment(t)
				, input = f;

			input.removeAttribute("style");

			sourcesByType[t] = { comment, input };

			if (elementType.value !== t)
				input.parentNode.replaceChild(comment, input);
		}

		this.activeType = sourcesByType[elementType.value];

		elementType.addEventListener("change", e => {
			this.activeType.input.parentNode.replaceChild(
				this.activeType.comment,
				this.activeType.input
			);

			this.activeType = sourcesByType[e.target.value];

			this.activeType.comment.parentNode.replaceChild(
				this.activeType.input,
				this.activeType.comment
			);

			// TODO: Update available fields
		});
	}

	initFieldTable () {
		const table = new Out.EditableTable(
			"out_fields",
			"fieldSettings",
			{
				"name": {
					"heading": "Column Name",
					"handle": "name",
					"width": "",
					"type": "singleline"
				}
			},
			{
				defaultValues: {},
				minRows: null,
				maxRows: null,
			}
		);

		const rows = table.$tbody[0].getElementsByTagName("tr");

		for (let i = 0, l = rows.length; i < l; ++i) {
			const row = rows[i];
			const settingsBtn = row.getElementsByClassName('settings')[0]
				, twig = row.querySelectorAll("[name*='twig']")
				, split = row.querySelectorAll("[name*='split']");

			settingsBtn.addEventListener("click", e => {
				e.preventDefault();
				this.edit(twig, split);
			});
		}
	}

	initCreateModal () {
		this.createModal = new Garnish.Modal(
			h("div", { class: "modal out--modal" }, [
				h("div", { class: "body" }, [
					f({
						label: "Field",
						id: "out_createField",
						tag: "div",
						attr: {
							class: "select",
							id: null,
						},
						instructions: "Select a field to base the column on.",
						children: h("select", { id: "out_createField" }, [
							h("option", {}, "Hello"),
						]),
					})
				]),
				h("div", { class: "footer" }, [
					h("div", { class: "secondary-buttons left" }, [
						h("button", {
							class: "btn submit",
							click: this.createCustom.bind(this),
						}, "Create Custom"),
					]),
					h("div", { class: "buttons right" }, [
						h("button", {
							class: "btn",
							click: this.cancelCreate.bind(this),
						}, "Cancel"),
						h("button", {
							class: "btn submit",
							click: this.createCreate.bind(this),
						}, "Create"),
					]),
				]),
			]),
			{ autoShow: false }
		);
	}

	initEditModal () {
		this.editModal = new Garnish.Modal(
			h("div", { class: "modal out--modal" }, [
				h("div", { class: "body" }, [
					f({
						label: "Twig",
						id: "out_twig",
						tag: "textarea",
						attr: {
							rows: 5,
							class: "text fullwidth",
							ref: el => { this.twigInput = el; }
						},
						instructions: [
							"Code to be executed in place of the fields output. You have access to the ",
							h("code", {}, "element"),
							" variable as well as all global & Craft variables.",
						],
					}),
					f({
						label: "Split",
						id: "out_split",
						tag: "input",
						attr: {
							type: "checkbox",
							checked: true,
							ref: el => { this.splitInput = el; }
						},
						instructions: "If checked the output of the column will be split into additional columns at every comma.",
					})
				]),
				h("div", { class: "footer" }, [
					h("div", { class: "buttons right" }, [
						h("button", {
							class: "btn",
							click: this.cancelEdit.bind(this),
						}, "Cancel"),
						h("button", {
							class: "btn submit",
							click: this.updateEdit.bind(this),
						}, "Update"),
					]),
				]),
			]),
			{ autoShow: false }
		);
	}

	// Events
	// =========================================================================

	create = async () => {
		return new Promise((resolve, reject) => {
			this.createModal.show();
			this.resolveCreate = resolve;
			this.rejectCreate = reject;
		});
	};

	cancelCreate () {
		this.rejectCreate();
		this.createModal.hide();
	}

	createCreate () {
		// TODO: Get fields from modal

		this.resolveCreate({
			name: "Test",
			twig: "{{ element.fieldHandle }}",
			split: 0,
			type: "custom",
		});

		this.createModal.hide();
	}

	createCustom () {
		this.resolveCreate({
			name: "",
			twig: "Add some twig code",
			split: 0,
			type: "custom",
		});

		this.createModal.hide();
	}

	edit ($twig, $split) {
		this.twigField = $twig[0];
		this.splitField = $split[0];

		this.twigInput.value = $twig[0].value;
		this.splitInput.checked = $split[0].value === '1';

		this.editModal.show();
	}

	cancelEdit () {
		this.editModal.hide();
	}

	updateEdit () {
		this.twigField.value = this.twigInput.value;
		this.splitField.value = this.splitInput.checked ? '1' : '0';

		this.editModal.hide();
	}

	// Misc
	// =========================================================================

	static EditableTable = Craft.EditableTable.extend({
		addRow: async function () {
			if (!this.canAddRow())
				return;

			let values = null;

			try {
				values = await Out.i.create();
			} catch (e) { return; }

			const rowId = this.settings.rowIdPrefix + (this.biggestId + 1);
			const $tr = this.createRow(
				rowId,
				this.columns,
				this.baseName,
				$.extend(values, this.settings.defaultValues)
			);

			$tr.appendTo(this.$tbody);
			new Craft.EditableTable.Row(this, $tr);
			this.sorter.addItems($tr);

			$tr.find('textarea').first().trigger('focus');

			this.rowCount++;
			this.updateAddRowButton();

			this.settings.onAddRow($tr);
		},

		createRow: function (rowId, columns, baseName, values) {
			const $tr = this.base(rowId, columns, baseName, values);

			$tr.children().first().after(
				$('<td />', {
					class: 'out--type'
				}).append(
					$('<span />').text(values.type)
				)
			);

			const $editTd = $('<td />', {
				class: 'thin action'
			});

			const $twig = $('<input />', {
				type: "hidden",
				name: `${baseName}[${rowId}][twig]`,
				value: values.twig,
			});

			const $split = $('<input />', {
				type: "hidden",
				name: `${baseName}[${rowId}][split]`,
				value: values.split,
			});

			$editTd.click(() => {
				Out.i.edit($twig, $split);
			});

			$editTd.append(
				$('<a />', {
					class: 'settings icon',
					title: Craft.t('app', "Settings"),
				}),
				$twig,
				$split,
				$('<input />', {
					type: "hidden",
					name: `${baseName}[${rowId}][type]`,
					value: values.type,
				})
			).prependTo($tr);

			return $tr;
		}
	});

}

window.Out = Out;