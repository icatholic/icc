Ext.define('icc.view.common.Combobox.Boolean', {
	extend : 'Ext.form.field.ComboBox',
	alias : 'widget.commonComboboxBoolean',
	store : 'common.Boolean',
	displayField : 'name',
	valueField : 'value',
	queryMode : 'local',
	pageSize : 0,
	editable : false,
	typeAhead : false
});
