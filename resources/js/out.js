/** global: Craft, Garnish, $ */
const prevLoad = window.onload;

window.onload = function () {
	prevLoad && prevLoad();

	const elementType = document.getElementById("elementType");

	const typeSource = document.querySelectorAll("[data-source-type]");
	const sourcesByType = {};

	for (let i = 0, l = typeSource.length; i < l; ++i) {
		const f = typeSource[i];
		const t = f.dataset.sourceType;

		const comment = document.createComment(t)
			, input   = f;

		input.removeAttribute("style");

		sourcesByType[t] = { comment, input };

		if (elementType.value !== t)
			input.parentNode.replaceChild(comment, input);
	}

	let currentActive = sourcesByType[elementType.value];

	elementType.addEventListener("change", e => {
		currentActive.input.parentNode.replaceChild(
			currentActive.comment,
			currentActive.input
		);

		currentActive = sourcesByType[e.target.value];

		currentActive.comment.parentNode.replaceChild(
			currentActive.input,
			currentActive.comment
		);
	});
};

{
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

	window.Out = {

		// Properties
		// =====================================================================

		fieldMap: {},
		fields: {},
		activeFieldId: null,

		modal: null,

		headingInput: null,
		twigInput: null,
		escapeInput: null,

		fieldSettings: null,

		// Functions
		// =====================================================================

		init (fieldMap) {
			this.fieldMap = fieldMap;

			this.fieldSettings = document.getElementById("fieldSettings");
			this.fields = JSON.parse(JSON.parse(this.fieldSettings.value));
			if (Array.isArray(this.fields)) this.fields = {};

			this.modal = new Garnish.Modal(
				h("div", { class: "modal out--modal" }, [
					h("div", { class: "body" }, [
						f({
							label: "Column Heading",
							id: "out_columnHeading",
							tag: "input",
							attr: {
								class: "text fullwidth",
								ref: el => { this.headingInput = el; }
							},
						}),
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
							label: "Escape Value",
							id: "out_escape",
							tag: "input",
							attr: {
								type: "checkbox",
								checked: true,
								ref: el => { this.escapeInput = el; }
							},
							instructions: "If checked the output of the column will be escaped",
						})
					]),
					h("div", { class: "footer" }, [
						h("div", { class: "buttons right" }, [
							h("button", {
								class: "btn",
								click: this.cancel.bind(this),
							}, "Cancel"),
							h("button", {
								class: "btn submit",
								click: this.update.bind(this),
							}, "Update"),
						]),
					]),
				]),
				{ autoShow: false }
			);
		},

		edit (field) {
			this.setValues(field);
			this.modal.show();
		},

		// Actions
		// =====================================================================

		setValues (field) {
			const fieldId = field.dataset.id;

			this.activeFieldId = fieldId;

			if (this.fields.hasOwnProperty(fieldId)) {
				const p = this.fields[fieldId];

				this.headingInput.value = p.heading;
				this.twigInput.value = p.twig;
				this.escapeInput.checked = p.escape;

				return;
			}

			this.headingInput.value = field.textContent.trim();
			this.twigInput.value = `{{ element.${this.fieldMap[fieldId]} }}`;
		},

		update () {
			this.fields[this.activeFieldId] = {
				heading: this.headingInput.value,
				twig: this.twigInput.value,
				escape: this.escapeInput.checked,
			};

			this.fieldSettings.value = JSON.stringify(this.fields);

			this.modal.hide();
		},

		cancel () {
			this.modal.hide();
		},

	};
}