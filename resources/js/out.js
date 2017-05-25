/* global Garnish */
import MappableField from "./MappableField";

class Out { // eslint-disable-line no-unused-vars
	
	constructor (types, fields, mapping) {
		this.types = types;
		this.fields = fields;
		this.mapping = mapping ? mapping : [];
	
		this.channelField = document.getElementById("channel");
		this.typeField = document.getElementById("type");
		this.mappingField = document.getElementById("mappingField");
		this.mappingContainer = document.getElementById("mapping");
		
		this.sortField = document.getElementById("sortField");
		this.sortDir = document.getElementById("sortDir");
		this.sortingField = document.getElementById("sorting");
		
		this.sort = null;
		this.modal = null;
		
		this.bindEvents();
		this.initializeOrUpdateMapping();
	}
	
	// Mapping
	// =========================================================================
	
	initializeOrUpdateMapping () {
		if (this.sort)
			this.sort.destroy();
		
		while (this.mappingContainer.firstElementChild)
			this.mappingContainer.removeChild(
				this.mappingContainer.firstElementChild
			);
		
		const fieldsOfType = [
			...this.fields[-1],
			...this.fields[this.typeField.value]
		];
		const validFieldHandles = fieldsOfType.map(field => field.handle);
		
		const handlesFromMapping = [];
		this.mapping = fieldsOfType.reduce((a, b) => {
			if (handlesFromMapping.indexOf(b.handle) > -1)
				return a;
			
			a.push(new MappableField(
				b.handle,
				b.name,
				b.type,
				b.name,
				"",
				true
			));
			
			return a;
		}, this.mapping.reduce((a, b) => {
			if (validFieldHandles.indexOf(b.handle) === -1)
				return a;
			
			handlesFromMapping.push(b.handle);
			a.push(new MappableField(b));
			return a;
		}, []));
		
		const rows = this.mapping.map(field => {
			const enabledAttrs = {
				"type": "checkbox",
				"change": this.onEnabledChange.bind(this),
			};
			
			if (field.enabled)
				enabledAttrs.checked = true;
			
			const row = Out.createElement("li", {
				"class": "out--mapping-field",
				"data-handle": field.handle,
			}, [
				Out.createElement("span", {}, [
					
					Out.createElement("span", {
						"class": "name",
					}, field.name),
					
					Out.createElement("span", {
						"class": "type",
					}, `(${field.type})`),
					
					Out.createElement("span", {
						"class": "type",
					}, field.label !== field.name ? field.label : ""),
				
				]),
				
				Out.createElement("span", {}, [
				
					Out.createElement("label", {}, [
						"Enabled",
						Out.createElement("input", enabledAttrs),
					]),
					
					Out.createElement("a", {
						"class": "icon settings",
						"href": "#",
						"click": this.onSettingsClick.bind(this, field),
					}),
					
				]),
			]);
			
			this.mappingContainer.appendChild(row);
			
			return row;
		});
		
		this.sort = new Garnish.DragSort(rows, {
			axis: Garnish.Y_AXIS,
			onSortChange: this.onMappingChange.bind(this),
		});
		
		this.onMappingChange();
		this.initializeSorting(fieldsOfType);
	}
	
	initializeSorting (fields) {
		while (this.sortField.firstElementChild)
			this.sortField.removeChild(this.sortField.firstElementChild);
		
		const [selectedField, selectedDir] = this.sortingField.value.split(" ");
		
		fields.forEach(field => {
			const attrs = {
				"value": field.handle,
			};
			
			if (selectedField && selectedField === field.handle)
				attrs["selected"] = true;
			
			this.sortField.appendChild(
				Out.createElement("option", attrs, field.name)
			);
		});
		
		if (selectedDir)
			this.sortDir.value = selectedDir;
		
		this.onSortChange();
	}
	
	// Events
	// =========================================================================
	
	bindEvents () {
		this.channelField.addEventListener(
			"change",
			this.onChannelChange.bind(this)
		);
		this.typeField.addEventListener(
			"change",
			this.onTypeChange.bind(this)
		);
		
		this.sortField.addEventListener("change", this.onSortChange.bind(this));
		this.sortDir.addEventListener("change", this.onSortChange.bind(this));
	}
	
	onChannelChange () {
		while (this.typeField.firstElementChild)
			this.typeField.removeChild(this.typeField.firstElementChild);
		
		const types = this.types[this.channelField.value];
		
		for (let [value, label] of Object.entries(types)) {
			const opts = { value };
			if (Object.keys(types).indexOf(value) === 0)
				opts["selected"] = true;
			
			this.typeField.appendChild(
				Out.createElement("option", opts, label)
			);
		}
		
		this.initializeOrUpdateMapping();
	}
	
	onTypeChange () {
		this.initializeOrUpdateMapping();
	}
	
	onEnabledChange () {
		this.onMappingChange();
	}
	
	onSettingsClick (field, e) {
		e.preventDefault();
		
		if (this.modal)
			this.modal.destroy();
		
		this.modal = new Garnish.Modal(
			Out.createElement("div", {
				"class": "modal out--modal"
			}, [
				Out.createElement("div", { "class": "body" }, [
					Out.createField("Label", "outModalLabel", "input", {
						"class": "text fullwidth",
						"value": field.label,
					}, "How the input will be labeled in the report"),
					Out.createField(
						"Twig",
						"outModalTwig",
						"textarea",
						{
							"class": "text fullwidth",
							"rows": 5
						},
						[
							"Code to be executed in place of the fields output. You have access to the ",
							Out.createElement("code", {}, field.handle),
							" variable as well as all global & Craft variables."
						],
						field.twig
					),
				]),
				Out.createElement("div", { "class": "footer" }, [
					Out.createElement("div", { "class": "buttons right" }, [
						Out.createElement("button", {
							"class": "btn",
							"click": this.onCancelModal.bind(this)
						}, "Cancel"),
						Out.createElement("button", {
							"class": "btn submit",
							"click": this.onSubmitModal.bind(this, field)
						}, "Update"),
					])
				]),
			])
		);
	}
	
	onCancelModal () {
		if (this.modal)
			this.modal.hide();
	}
	
	onSubmitModal (field) {
		this.mapping = this.mapping.map(f => {
			if (f.handle !== field.handle)
				return f;
			
			return new MappableField({
				...f,
				label: document.getElementById("outModalLabel").value,
				twig: document.getElementById("outModalTwig").value,
			});
		});
		
		if (this.modal)
			this.modal.hide();
		
		this.initializeOrUpdateMapping();
	}
	
	onMappingChange () {
		const mapByHandle = this.mapping.reduce((a, b) => {
			a[b.handle] = b;
			return a;
		}, {});
		
		this.mapping = [].slice.call(this.mappingContainer.children).map(child => {
			const ret = mapByHandle[child.dataset.handle];
			ret.enabled = child.getElementsByTagName("input")[0].checked;
			
			return ret;
		});
		
		this.mappingField.value = JSON.stringify(this.mapping);
	}
	
	onSortChange () {
		this.sortingField.value = `${this.sortField.value} ${this.sortDir.value}`;
	}
	
	// Helpers
	// =========================================================================
	
	static createElement (tag = "div", attributes = {}, children = []) {
		const elem = document.createElement(tag);
		
		for (let [key, value] of Object.entries(attributes)) {
			if (typeof value === typeof (() => {})) {
				elem.addEventListener(key, value);
				continue;
			}
			
			elem.setAttribute(key, value);
		}
		
		if (!Array.isArray(children))
			children = [children];
		
		children.map(child => {
			try {
				elem.appendChild(child);
			} catch (_) {
				elem.appendChild(document.createTextNode(child));
			}
		});
		
		return elem;
	}
	
	static createField (label, id, tag = "input", attributes = {}, instructions = null, children = null) {
		return Out.createElement("div", { "class": "field" }, [
			Out.createElement("div", { "class": "heading" }, [
				Out.createElement("label", { "for": id }, label),
				instructions ? Out.createElement("div", {
					"class": "instructions"
				}, Out.createElement("p", {}, instructions)) : ""
			]),
			Out.createElement("div", { "class": "input" }, [
				Out.createElement(tag, {
					id,
					...attributes,
				}, children)
			])
		]);
	}
	
}