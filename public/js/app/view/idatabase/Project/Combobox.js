Ext.define('icc.view.idatabase.Project.Combobox', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseProjectCombobox',
	fieldLabel : '项目列表',
	name : '__PROJECT_ID__',
	store : 'idatabase.Project',
	valueField : '_id',
	displayField : 'name',
	queryMode : 'remote',
	pageSize : 0,
	editable : false,
	typeAhead : false
});
