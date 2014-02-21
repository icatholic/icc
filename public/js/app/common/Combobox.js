Ext.define('icc.common.Combobox', {
	extend : 'Ext.form.field.ComboBox',
	queryMode : 'remote',
	forceSelection : true,
	editable : true,
	pageSize : 10,
	queryParam : 'search',
	typeAhead : true,
	allowBlank : true
});
