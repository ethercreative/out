export default class MappableField {
	
	constructor (handle, name, type, label, twig, enabled) {
		if (typeof handle === typeof {}) {
			return MappableField.fromObject(handle);
		}
		
		this.handle = handle;
		this.name = name;
		this.type = type;
		this.label = label;
		this.twig = twig;
		this.enabled = enabled;
	}
	
	static fromObject (o) {
		return new MappableField(
			o.handle,
			o.name,
			o.type,
			o.label || o.name,
			o.twig || "",
			o.enabled || false
		);
	}
	
}