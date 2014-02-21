Ext.define('icc.view.idatabase.Structure.FilterCombobox', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStructureFilterCombobox',
	fieldLabel : '选择过滤器',
	name : 'filter',
	store : 'idatabase.Structure.FilterType',
	valueField : 'val',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
